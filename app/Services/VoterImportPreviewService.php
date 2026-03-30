<?php

namespace App\Services;

use App\Models\VoterImportRun;
use App\Models\VoterImportError;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class VoterImportPreviewService
{
    public function __construct(
        protected CenterResolverService $centerResolver
    ) {}

    public function preview(UploadedFile $file, int $userId): array
    {
        $storedPath = $file->store('imports', 'local');

        $run = VoterImportRun::create([
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'status' => 'previewed',
            'created_by' => $userId,
        ]);

        $path = Storage::disk('local')->path($storedPath);

        $rows = Excel::toArray([], $path)[0] ?? [];

        if (empty($rows)) {
            return [
                'run' => $run,
                'headers' => [],
                'preview_rows' => [],
                'summary' => [
                    'total_rows' => 0,
                    'valid_rows' => 0,
                    'error_rows' => 0,
                ],
            ];
        }

        $headers = array_map(fn($h) => trim((string)$h), $rows[0]);
        $dataRows = array_slice($rows, 1);

        $requiredHeaders = ['voter_no', 'national_id', 'full_name'];
        $headerMap = array_flip($headers);

        $previewRows = [];
        $validRows = 0;
        $errorRows = 0;
        $seenNationalIds = [];

        foreach ($dataRows as $index => $row) {
            $rowNumber = $index + 2;

            $mapped = $this->mapRow($headers, $row);

            $mapped['support_status'] = $this->mapSupportStatus($mapped['support_status'] ?? null);

            $errors = [];

            foreach ($requiredHeaders as $required) {
                if (!array_key_exists($required, $mapped) || blank($mapped[$required])) {
                    $errors[] = "الحقل {$required} مطلوب";
                }
            }

            if (!blank($mapped['national_id'] ?? null)) {
                $nid = trim((string)$mapped['national_id']);

                if (isset($seenNationalIds[$nid])) {
                    $errors[] = 'رقم الهوية مكرر داخل الملف';
                } else {
                    $seenNationalIds[$nid] = true;
                }
            }

            $centerId = $this->centerResolver->resolve(
                $mapped['center_code'] ?? null,
                $mapped['location'] ?? null
            );

            if (!$centerId) {
                $errors[] = 'تعذر مطابقة المركز';
            }

            if ($errors) {
                $errorRows++;

                VoterImportError::create([
                    'voter_import_run_id' => $run->id,
                    'row_number' => $rowNumber,
                    'error_type' => 'preview_validation',
                    'message' => implode(' | ', $errors),
                    'row_data' => $mapped,
                ]);
            } else {
                $validRows++;
            }

            if (count($previewRows) < 25) {
                $previewRows[] = [
                    'row_number' => $rowNumber,
                    'data' => $mapped,
                    'errors' => $errors,
                ];
            }
        }

        $run->update([
            'total_rows' => count($dataRows),
            'valid_rows' => $validRows,
            'error_rows' => $errorRows,
            'meta' => [
                'headers' => $headers,
            ],
        ]);

        return [
            'run' => $run,
            'headers' => $headers,
            'preview_rows' => $previewRows,
            'summary' => [
                'total_rows' => count($dataRows),
                'valid_rows' => $validRows,
                'error_rows' => $errorRows,
            ],
        ];
    }

    private function mapRow(array $headers, array $row): array
    {
        $mapped = [];

        foreach ($headers as $i => $header) {
            $mapped[trim((string)$header)] = $row[$i] ?? null;
        }

        return $mapped;
    }
    private function mapSupportStatus($value): string
    {
        return match ((int) $value) {
            1 => 'supporter',
            2 => 'leaning',
            3 => 'undecided',
            4 => 'opposed',
            5 => 'unknown',
            6 => 'unknown',
            default => 'unknown',
        };
    }

}
