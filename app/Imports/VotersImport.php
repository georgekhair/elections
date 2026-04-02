<?php

namespace App\Imports;

use App\Models\Voter;
use App\Models\VoterImportError;
use App\Models\VoterImportRun;
use App\Services\CenterResolverService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

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
                $nationalId = ltrim($nationalId, '0');

                if (!$nationalId) {
                    $this->logError($rowNumber, 'missing_national_id', 'رقم الهوية مفقود', $row->toArray());
                    continue;
                }

                $centerId = $this->centerResolver->resolve(
                    $row['center_code'] ?? null,
                    $row['location'] ?? null
                );

                if (!$centerId && $importType !== 'names_only') {
                    $this->logError($rowNumber, 'center_not_found', 'تعذر مطابقة المركز', $row->toArray());
                    continue;
                }

                $firstName = $this->normalizeNullableString($row['first_name'] ?? null);
                $fatherName = $this->normalizeNullableString($row['father_name'] ?? null);
                $grandfatherName = $this->normalizeNullableString($row['grandfather_name'] ?? null);
                $familyName = $this->normalizeNullableString($row['family_name'] ?? null);

                $fullNameFromColumns = trim(implode(' ', array_filter([
                    $firstName,
                    $fatherName,
                    $grandfatherName,
                    $familyName,
                ])));

                $fullName = $fullNameFromColumns !== ''
                    ? $fullNameFromColumns
                    : $this->normalizeNullableString($row['full_name'] ?? null);

                $importType = $this->getImportType();

                $voter = Voter::whereRaw("TRIM(LEADING '0' FROM national_id) = ?", [$nationalId])->first();

                if (!$voter) {
                    $voter = new Voter([
                        'national_id' => $nationalId,
                    ]);
                }

                $isNew = !$voter->exists;

                // fields always safe to update
                $voter->voter_no = $row['voter_no'] ?? $voter->voter_no;
                $voter->location = $row['location'] ?? $voter->location;
                $voter->polling_center_id = $centerId ?? $voter->polling_center_id;

                // name fields
                $voter->first_name = $firstName ?? $voter->first_name;
                $voter->father_name = $fatherName ?? $voter->father_name;
                $voter->grandfather_name = $grandfatherName ?? $voter->grandfather_name;
                $voter->family_name = $familyName ?? $voter->family_name;

                if ($fullName) {
                    $voter->full_name = $fullName;
                }

                if ($importType === 'full') {
                    $voter->support_status = $this->mapSupportStatus($row['support_status'] ?? null);
                    $voter->priority_level = $row['priority_level'] ?? 'low';
                    $voter->assigned_delegate_id = $row['assigned_delegate_id'] ?? null;
                } elseif ($importType === 'safe') {
                    if ($isNew || $voter->support_status === 'unknown' || blank($voter->support_status)) {
                        $voter->support_status = $this->mapSupportStatus($row['support_status'] ?? null);
                    }

                    if ($isNew || blank($voter->priority_level)) {
                        $voter->priority_level = $row['priority_level'] ?? 'low';
                    }

                    // deliberately do not overwrite delegate assignment in safe mode
                } elseif ($importType === 'names_only') {
                    // names/location/center already updated above
                    // do not touch support_status / priority_level / assigned_delegate_id
                }

                $original = $voter->getOriginal();

                $voter->save();

                $changes = $voter->getChanges();

                if ($isNew) {
                    $this->run->increment('imported_rows');

                } elseif (!empty($changes)) {
                    $this->run->increment('updated_rows');
                    $this->run->increment('imported_rows');

                } else {
                    $this->run->increment('skipped_no_change_rows');
                    $this->run->increment('skipped_rows');
                }

            } catch (\Throwable $e) {
                $this->logError($rowNumber, 'exception', $e->getMessage(), $row->toArray());
            }
        }
    }

    public function chunkSize(): int
    {
        return 200;
    }

    public function batchSize(): int
    {
        return 500;
    }

    private function getImportType(): string
    {
        return $this->run->import_type ?: 'safe';
    }

    private function normalizeNullableString($value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
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
            6 => 'traveling',
            default => 'unknown',
        };
    }
}
