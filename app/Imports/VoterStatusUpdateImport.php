<?php

namespace App\Imports;

use App\Models\Voter;
use App\Models\VoterImportError;
use App\Models\VoterImportRun;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class VoterStatusUpdateImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    public function __construct(protected VoterImportRun $run)
    {
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            try {
                $nationalId = trim((string) ($row['national_id'] ?? ''));
                $nationalId = ltrim($nationalId, '0'); // إزالة الأصفار بالبداية
                $rawStatus  = $row['support_status'] ?? null;

                if ($nationalId === '') {
                    $this->logError(
                        $rowNumber,
                        'missing_national_id',
                        'رقم الهوية مفقود',
                        $row->toArray()
                    );
                    continue;
                }

                $newStatus = $this->mapSupportStatus($rawStatus);

                $voter = Voter::whereRaw("TRIM(LEADING '0' FROM national_id) = ?", [$nationalId])->first();

                if (!$voter) {
                    $this->run->increment('not_found_rows');
                    $this->run->increment('skipped_rows');

                    $this->logError(
                        $rowNumber,
                        'voter_not_found',
                        'لا يوجد ناخب بهذا الرقم الوطني',
                        $row->toArray()
                    );
                    continue;
                }

                // نحمي التعديلات اليدوية: لا نحدث إلا إذا كانت الحالة الحالية unknown
                if ($voter->support_status !== 'unknown') {
                    $this->run->increment('skipped_already_updated_rows');
                    $this->run->increment('skipped_rows');

                    $this->logError(
                        $rowNumber,
                        'already_updated',
                        'تم تجاهل الصف لأن حالة الناخب محدثة مسبقًا داخل النظام',
                        array_merge($row->toArray(), [
                            'current_status' => $voter->support_status,
                        ])
                    );
                    continue;
                }

                // لا نحدث إذا الملف نفسه unknown
                if ($newStatus === 'unknown') {
                    $this->run->increment('skipped_no_change_rows');
                    $this->run->increment('skipped_rows');

                    $this->logError(
                        $rowNumber,
                        'no_change',
                        'تم تجاهل الصف لأن الحالة الجديدة unknown ولا يوجد تغيير فعلي',
                        $row->toArray()
                    );
                    continue;
                }

                $voter->update([
                    'support_status' => $newStatus,
                ]);

                $this->run->increment('updated_rows');
                $this->run->increment('imported_rows');

            } catch (\Throwable $e) {
                $this->run->increment('error_rows');
                $this->run->increment('skipped_rows');

                $this->logError(
                    $rowNumber,
                    'exception',
                    $e->getMessage(),
                    $row->toArray()
                );
            }
        }
    }

    public function chunkSize(): int
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
    }

    private function mapSupportStatus($value): string
    {
        $value = $this->normalizeArabicDigits((string) $value);

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

    private function normalizeArabicDigits(string $value): string
    {
        return strtr($value, [
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9',
        ]);
    }
}
