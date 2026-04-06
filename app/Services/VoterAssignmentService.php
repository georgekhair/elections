<?php

namespace App\Services;

use App\Models\User;
use App\Models\Voter;
use Illuminate\Support\Collection;

class VoterAssignmentService
{
    public function assignBySelectionValue(Voter $voter, ?string $value): void
    {
        $payload = $this->buildPayloadFromSelectionValue($value);
        $voter->update($payload);
    }

    public function bulkAssignBySelectionValue(iterable $voterIds, ?string $value): void
    {
        $payload = $this->buildPayloadFromSelectionValue($value);

        if ($voterIds instanceof Collection) {
            $voterIds = $voterIds->all();
        }

        Voter::whereIn('id', $voterIds)->update($payload);
    }

    public function clear(Voter $voter): void
    {
        $voter->update([
            'assigned_user_id' => null,
            'assigned_delegate_id' => null,
            'supervisor_id' => null,
        ]);
    }

    public function buildPayloadFromSelectionValue(?string $value): array
    {
        $value = trim((string) $value);

        if ($value === '') {
            return [
                'assigned_user_id' => null,
                'assigned_delegate_id' => null,
                'supervisor_id' => null,
            ];
        }

        if (str_starts_with($value, 'supervisor_')) {
            $supervisorId = (int) str_replace('supervisor_', '', $value);

            return [
                'assigned_user_id' => $supervisorId,
                'assigned_delegate_id' => null,
                'supervisor_id' => $supervisorId,
            ];
        }

        // ✅ delegate assignment WITH supervisor awareness
        $delegate = User::findOrFail((int) $value);

        return [
            'assigned_user_id' => $delegate->id,
            'assigned_delegate_id' => $delegate->id,
            'supervisor_id' => $delegate->supervisor_id, // 🔥 KEY FIX
        ];
    }

    public function assignToUser(Voter $voter, User $user): void
    {
        if ($user->hasRole('delegate')) {

            if (!$user->supervisor_id) {
                throw new \Exception('Delegate must belong to supervisor');
            }

            $voter->update([
                'assigned_user_id' => $user->id,
                'assigned_delegate_id' => $user->id,
                'supervisor_id' => $user->supervisor_id,
            ]);

            return;
        }

        if ($user->hasRole('supervisor')) {
            $voter->update([
                'assigned_user_id' => $user->id,
                'assigned_delegate_id' => null,
                'supervisor_id' => $user->id,
            ]);

            return;
        }
    }

    public function buildPayloadFromUser(User $user): array
    {
        if ($user->hasRole('supervisor')) {
            return [
                'assigned_user_id' => $user->id,
                'assigned_delegate_id' => null,
                'supervisor_id' => $user->id,
            ];
        }

        if ($user->hasRole('delegate')) {

            if (!$user->supervisor_id) {
                throw new \Exception('Delegate must belong to a supervisor');
            }

            return [
                'assigned_user_id' => $user->id,
                'assigned_delegate_id' => $user->id,
                'supervisor_id' => $user->supervisor_id,
            ];
        }

        return [];
    }
    public function syncUserRoleAssignments(User $user): void
    {
        if ($user->hasRole('supervisor')) {

            // delegate → supervisor
            Voter::where('assigned_delegate_id', $user->id)
                ->update([
                    'assigned_delegate_id' => null,
                    'supervisor_id' => $user->id,
                    'assigned_user_id' => $user->id,
                ]);

        } elseif ($user->hasRole('delegate')) {

            // supervisor → delegate
            Voter::where('supervisor_id', $user->id)
                ->whereNull('assigned_delegate_id') // 🔒 safety
                ->update([
                    'assigned_delegate_id' => $user->id,
                    'supervisor_id' => $user->supervisor_id,
                    'assigned_user_id' => $user->id,
                ]);
        }
    }
}
