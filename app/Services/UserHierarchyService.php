<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class UserHierarchyService
{
    public function getHierarchyData(): array
    {
        $supervisors = User::role('supervisor')
            ->with('pollingCenter') // 🔥 ADD THIS
            ->with([
                'delegates' => function ($query) {
                    $query->role('delegate')
                        ->with('pollingCenter')
                        ->withCount('delegatedVoters')
                        ->orderBy('name');
                }
            ])
            ->orderBy('name')
            ->get();

        $unassignedDelegates = User::role('delegate')
            ->with('pollingCenter')
            ->whereNull('supervisor_id')
            ->orderBy('name')
            ->get();

        return [
            'supervisors' => $supervisors,
            'unassignedDelegates' => $unassignedDelegates,
        ];
    }

    public function assignDelegateToSupervisor(User $delegate, ?User $supervisor): void
    {
        if (!$delegate->isDelegate()) {
            throw new InvalidArgumentException('Selected user is not a delegate.');
        }

        if ($supervisor && !$supervisor->isSupervisor()) {
            throw new InvalidArgumentException('Selected supervisor is not a supervisor.');
        }

        // 🔥 Soft rule: same polling center
        if ($supervisor && $delegate->polling_center_id !== $supervisor->polling_center_id) {

            logger()->warning('Cross-center assignment', [
                'delegate_id' => $delegate->id,
                'delegate_center' => $delegate->polling_center_id,
                'supervisor_id' => $supervisor->id,
                'supervisor_center' => $supervisor->polling_center_id,
            ]);
        }

        DB::transaction(function () use ($delegate, $supervisor) {

            // ✅ 1. Update delegate
            $delegate->update([
                'supervisor_id' => $supervisor?->id,
            ]);

            // ✅ 2. Sync ALL voters of this delegate 🔥
            DB::table('voters')
                ->where('assigned_delegate_id', $delegate->id)
                ->update([
                    'supervisor_id' => $supervisor?->id,
                ]);
        });
    }

    public function bulkAssignDelegates(array $delegateIds, ?int $supervisorId): void
    {
        DB::transaction(function () use ($delegateIds, $supervisorId) {

            $supervisor = $supervisorId
                ? User::role('supervisor')->findOrFail($supervisorId)
                : null;

            // ✅ update delegates
            User::whereIn('id', $delegateIds)
                ->whereHas('roles', fn($q) => $q->where('name', 'delegate'))
                ->update([
                    'supervisor_id' => $supervisor?->id,
                ]);

            // ✅ update voters in ONE query 🔥
            DB::table('voters')
                ->whereIn('assigned_delegate_id', $delegateIds)
                ->update([
                    'supervisor_id' => $supervisor?->id,
                ]);
        });
    }

    public function moveDelegate(int $delegateId, ?int $supervisorId): void
    {
        $delegate = User::role('delegate')->findOrFail($delegateId);

        $supervisor = null;
        if ($supervisorId) {
            $supervisor = User::role('supervisor')->findOrFail($supervisorId);
        }

        $this->assignDelegateToSupervisor($delegate, $supervisor);
    }

    public function delegatesForSupervisor(User $supervisor): Collection
    {
        return $supervisor->delegates()
            ->whereHas('roles', fn($q) => $q->where('name', 'delegate'))
            ->orderBy('name')
            ->get();
    }


}
