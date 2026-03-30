<?php

namespace App\Imports;

use App\Models\Voter;
use App\Models\VoterImportError;
use App\Models\VoterImportRun;
use App\Services\CenterResolverService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;

class VotersImport implements ToCollection, WithHeadingRow, WithChunkReading, WithBatchInserts
{
    public function __construct(
        protected VoterImportRun $run,
        protected CenterResolverService $centerResolver
    ) {}

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            try {
                $nationalId = trim((string) ($row['national_id'] ?? ''));
                $nationalId = ltrim($nationalId, '0'); // إزالة الأصفار بالبداية

                if (!$nationalId) {
                    $this->logError($rowNumber, 'missing_national_id', 'رقم الهوية مفقود', $row->toArray());
                    continue;
                }

                $centerId = $this->centerResolver->resolve(
                    $row['center_code'] ?? null,
                    $row['location'] ?? null
                );

                if (!$centerId) {
                    $this->logError($rowNumber, 'center_not_found', 'تعذر مطابقة المركز', $row->toArray());
                    continue;
                }

                Voter::updateOrCreate(
                    ['national_id' => $nationalId],
                    [
                        'voter_no' => $row['voter_no'] ?? null,
                        'full_name' => $row['full_name'] ?? null,
                        'location' => $row['location'] ?? null,
                        'polling_center_id' => $centerId,
                        'support_status' => $this->mapSupportStatus($row['support_status'] ?? null),
                        'priority_level' => $row['priority_level'] ?? 'low',
                        'assigned_delegate_id' => $row['assigned_delegate_id'] ?? null,
                    ]
                );

                $this->run->increment('imported_rows');

            } catch (\Throwable $e) {
                $this->logError($rowNumber, 'exception', $e->getMessage(), $row->toArray());
            }
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function batchSize(): int
    {
        return 500;
    }

    private function logError(int $rowNumber, string $type, string $message, array $rowData): void
    {
        VoterImportError::create([
            'voter_import_run_id' => $this->run->id,
            'row_number' => $rowNumber,
            'error_type' => $type,
            'message' => $message,
            'row_data' => $rowData,
        ]);

        $this->run->increment('error_rows');
        $this->run->increment('skipped_rows');
    }
    private function mapSupportStatus($value): string
    {
        return match ((int) $value) {
            1 => 'supporter',
            2 => 'leaning',
            3 => 'undecided',
            4 => 'opposed',
            5 => 'unknown',
            6 => 'unknown', // Travel → treated as unknown
            default => 'unknown',
        };
    }
}
