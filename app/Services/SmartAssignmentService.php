<?php

namespace App\Services;

use App\Models\FieldTask;
use App\Models\User;
use App\Models\Voter;

class SmartAssignmentService
{
    public function assignForCenterTask(array $center, string $taskType): ?User
    {
        $centerId = $center['id'] ?? null;

        if (! $centerId) {
            return null;
        }

        // 1) supervisor of same center is first choice
        $supervisor = User::role('supervisor')
            ->where('polling_center_id', $centerId)
            ->where('is_active', true)
            ->first();

        if ($supervisor) {
            return $supervisor;
        }

        // 2) best delegate in same center
        $delegate = $this->bestDelegateInCenter($centerId);

        if ($delegate) {
            return $delegate;
        }

        // 3) fallback to operations user
        return User::role('operations')
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
    }

    public function bestDelegateInCenter(int $centerId): ?User
    {
        $delegates = User::role('delegate')
            ->where('polling_center_id', $centerId)
            ->where('is_active', true)
            ->get();

        if ($delegates->isEmpty()) {
            return null;
        }

        $ranked = $delegates->map(function ($delegate) {
            $lastVoteAt = Voter::where('voted_by', $delegate->id)
                ->latest('voted_at')
                ->value('voted_at');

            $minutesAgo = $lastVoteAt
                ? now()->diffInMinutes($lastVoteAt)
                : 999;

            $votesCount = Voter::where('voted_by', $delegate->id)->count();

            $openTasks = FieldTask::where('user_id', $delegate->id)
                ->whereIn('status', ['pending', 'in_progress'])
                ->count();

            $score = 0;

            // activity
            if ($minutesAgo < 5) {
                $score += 30;
            } elseif ($minutesAgo < 15) {
                $score += 20;
            } elseif ($minutesAgo < 30) {
                $score += 10;
            }

            // productivity
            $score += min(40, $votesCount);

            // penalize overload
            $score -= ($openTasks * 10);

            return [
                'user' => $delegate,
                'score' => $score,
            ];
        })->sortByDesc('score')->values();

        return $ranked->first()['user'] ?? null;
    }
}
