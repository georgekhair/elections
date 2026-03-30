<?php

namespace App\Services;

class DecisionEngineService
{
    public function evaluateCenter(array $center): array
    {
        $supportersRemaining = (int) ($center['supporters_remaining'] ?? 0);
        $supporterTurnout = (int) ($center['supporter_turnout'] ?? 0);
        $trend = $center['trend'] ?? 'stable';
        $lastVoteMinutesAgo = (int) ($center['last_vote_minutes_ago'] ?? 0);

        $score = 0;

        // 1) Remaining supporters impact
        $score += min(40, round($supportersRemaining * 0.15));

        // 2) Turnout penalty
        if ($supporterTurnout < 25) {
            $score += 25;
        } elseif ($supporterTurnout < 40) {
            $score += 18;
        } elseif ($supporterTurnout < 55) {
            $score += 10;
        }

        // 3) Trend penalty
        if ($trend === 'down') {
            $score += 15;
        } elseif ($trend === 'stable') {
            $score += 5;
        }

        // 4) Staleness penalty
        if ($lastVoteMinutesAgo > 20) {
            $score += 15;
        } elseif ($lastVoteMinutesAgo > 10) {
            $score += 8;
        }

        // 5) Critical boost
        if ($supportersRemaining >= 150 && $supporterTurnout < 35) {
            $score += 15;
        }

        $level = 'stable';
        $recommendation = 'لا يوجد إجراء عاجل حالياً';

        if ($score >= 80) {
            $level = 'critical';
            $recommendation = 'إرسال تعبئة فورية والاتصال بالمشرف حالاً';
        } elseif ($score >= 60) {
            $level = 'high';
            $recommendation = 'تفعيل فريق الاتصال خلال 10 دقائق';
        } elseif ($score >= 40) {
            $level = 'medium';
            $recommendation = 'متابعة لصيقة وتحضير تعبئة احتياطية';
        }

        return [
            'priority_score' => $score,
            'priority_level' => $level,
            'decision_recommendation' => $recommendation,
        ];
    }
}
