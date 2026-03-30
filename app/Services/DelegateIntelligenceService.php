<?php

namespace App\Services;

use App\Models\User;
use App\Models\Voter;

class DelegateIntelligenceService
{
    public function analyze()
    {
        $delegates = User::role('delegate')
            ->with('pollingCenter')
            ->get();

        return $delegates->map(function ($delegate) {

            $total = Voter::where('polling_center_id', $delegate->polling_center_id)->count();

            $voted = Voter::where('polling_center_id', $delegate->polling_center_id)
                ->where('voted_by', $delegate->id)
                ->count();

            $supporters = Voter::where('polling_center_id', $delegate->polling_center_id)
                ->where('support_status', 'supporter')
                ->count();

            $supportersVoted = Voter::where('polling_center_id', $delegate->polling_center_id)
                ->where('support_status', 'supporter')
                ->where('voted_by', $delegate->id)
                ->count();

            $lastVoteAt = Voter::where('voted_by', $delegate->id)
                ->latest('voted_at')
                ->value('voted_at');

            $minutesAgo = $lastVoteAt
                ? now()->diffInMinutes($lastVoteAt)
                : 999;

            $performance = $supporters > 0
                ? round(($supportersVoted / $supporters) * 100)
                : 0;

            /*
            |--------------------------------------------------------------------------
            | Scoring Engine
            |--------------------------------------------------------------------------
            */

            $score = 0;

            // Performance
            $score += $performance * 0.5;

            // Activity
            if ($minutesAgo < 5) $score += 30;
            elseif ($minutesAgo < 15) $score += 20;
            elseif ($minutesAgo < 30) $score += 10;

            // Inactivity penalty
            if ($minutesAgo > 30) $score -= 20;
            if ($minutesAgo > 60) $score -= 40;

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            $status = 'active';

            if ($minutesAgo > 30) {
                $status = 'inactive';
            } elseif ($minutesAgo > 10) {
                $status = 'idle';
            }

            /*
            |--------------------------------------------------------------------------
            | Recommendation
            |--------------------------------------------------------------------------
            */

            $recommendation = 'أداء جيد';

            if ($status === 'inactive') {
                $recommendation = 'اتصال فوري بالمندوب';
            } elseif ($status === 'idle') {
                $recommendation = 'متابعة وتنشيط';
            }

            return [
                'id' => $delegate->id,
                'name' => $delegate->name,
                'center' => $delegate->pollingCenter?->name,
                'performance' => $performance,
                'score' => round($score),
                'status' => $status,
                'last_activity_minutes' => $minutesAgo,
                'recommendation' => $recommendation,
                'supporters_voted' => $supportersVoted,
            ];
        })->sortByDesc('score')->values();
    }
}
