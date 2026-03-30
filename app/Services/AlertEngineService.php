<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\ElectionList;
use App\Models\PollingCenter;
use App\Models\SeatProjectionSnapshot;
use App\Models\Voter;
use App\Services\DecisionEngineService;

class AlertEngineService
{
    public function run(): void
    {
        $this->checkCenterSupporterTurnout();
        $this->checkCenterSupporterRemaining();
        $this->checkProjectionChanges();
        $this->checkPriorityAlerts(app(DecisionEngineService::class));
    }

    private function checkCenterSupporterTurnout(): void
    {
        $centers = PollingCenter::withCount([
            'voters as supporters_total' => fn($q) => $q->where('support_status', 'supporter'),
            'voters as supporters_voted' => fn($q) => $q->where('support_status', 'supporter')->where('is_voted', true),
        ])->get();

        foreach ($centers as $center) {
            $turnout = $center->supporters_total > 0
                ? round(($center->supporters_voted / $center->supporters_total) * 100)
                : 0;

            $existing = Alert::where('type', 'supporter_turnout_low')
                ->where('polling_center_id', $center->id)
                ->where('is_active', true)
                ->first();

            if ($turnout < 40) {
                if (! $existing) {
                    Alert::create([
                        'type' => 'supporter_turnout_low',
                        'severity' => 'critical',
                        'title' => 'انخفاض نسبة تصويت المضمونين',
                        'message' => "نسبة تصويت المضمونين في {$center->name} منخفضة جداً ({$turnout}%).",
                        'polling_center_id' => $center->id,
                        'meta' => [
                            'supporters_total' => $center->supporters_total,
                            'supporters_voted' => $center->supporters_voted,
                            'turnout_percent' => $turnout,
                        ],
                        'is_active' => true,
                        'detected_at' => now(),
                    ]);
                }
            } else {
                if ($existing) {
                    $existing->update([
                        'is_active' => false,
                        'resolved_at' => now(),
                    ]);
                }
            }
        }
    }

    private function checkCenterSupporterRemaining(): void
    {
        $centers = PollingCenter::withCount([
            'voters as supporters_total' => fn($q) => $q->where('support_status', 'supporter'),
            'voters as supporters_voted' => fn($q) => $q->where('support_status', 'supporter')->where('is_voted', true),
        ])->get();

        foreach ($centers as $center) {
            $remaining = $center->supporters_total - $center->supporters_voted;

            $existing = Alert::where('type', 'supporters_remaining_high')
                ->where('polling_center_id', $center->id)
                ->where('is_active', true)
                ->first();

            if ($remaining >= 100) {
                if (! $existing) {
                    Alert::create([
                        'type' => 'supporters_remaining_high',
                        'severity' => 'warning',
                        'title' => 'عدد كبير من المضمونين لم يصوتوا بعد',
                        'message' => "لا يزال هناك {$remaining} من المضمونين لم يصوتوا في {$center->name}.",
                        'polling_center_id' => $center->id,
                        'meta' => [
                            'supporters_remaining' => $remaining,
                        ],
                        'is_active' => true,
                        'detected_at' => now(),
                    ]);
                }
            } else {
                if ($existing) {
                    $existing->update([
                        'is_active' => false,
                        'resolved_at' => now(),
                    ]);
                }
            }
        }
    }

    private function checkProjectionChanges(): void
    {
        $latest = SeatProjectionSnapshot::latest()->first();
        $previous = SeatProjectionSnapshot::latest()->skip(1)->first();

        if (! $latest || ! $previous) {
            return;
        }

        $latestSeats = $latest->projected_seats ?? [];
        $previousSeats = $previous->projected_seats ?? [];

        $ourList = ElectionList::where('is_our_list', true)->first();

        if (! $ourList) {
            return;
        }

        $latestValue = $latestSeats[$ourList->name] ?? 0;
        $previousValue = $previousSeats[$ourList->name] ?? 0;

        $existing = Alert::where('type', 'projection_changed')
            ->where('is_active', true)
            ->first();

        if ($latestValue !== $previousValue) {
            if (! $existing) {
                $direction = $latestValue > $previousValue ? 'ارتفع' : 'انخفض';

                Alert::create([
                    'type' => 'projection_changed',
                    'severity' => $latestValue > $previousValue ? 'info' : 'danger',
                    'title' => 'تغير في التوقع الانتخابي',
                    'message' => "توقع مقاعد قائمتنا {$direction} من {$previousValue} إلى {$latestValue}.",
                    'meta' => [
                        'previous' => $previousValue,
                        'current' => $latestValue,
                    ],
                    'is_active' => true,
                    'detected_at' => now(),
                ]);
            }
        } else {
            if ($existing) {
                $existing->update([
                    'is_active' => false,
                    'resolved_at' => now(),
                ]);
            }
        }
    }

    private function checkPriorityAlerts(DecisionEngineService $decisionEngine): void
    {
        $centers = \App\Models\PollingCenter::withCount([
            'voters as supporters_total' => fn($q) => $q->where('support_status', 'supporter'),
            'voters as supporters_voted' => fn($q) => $q->where('support_status', 'supporter')->where('is_voted', true),
        ])->get();

        foreach ($centers as $center) {
            $supportersRemaining = $center->supporters_total - $center->supporters_voted;
            $supporterTurnout = $center->supporters_total > 0
                ? round(($center->supporters_voted / $center->supporters_total) * 100)
                : 0;

            $lastVoteAt = Voter::where('polling_center_id', $center->id)
                ->whereNotNull('voted_at')
                ->latest('voted_at')
                ->value('voted_at');

            $lastVoteMinutesAgo = $lastVoteAt
                ? now()->diffInMinutes(\Carbon\Carbon::parse($lastVoteAt))
                : 999;

            $decision = $decisionEngine->evaluateCenter([
                'supporters_remaining' => $supportersRemaining,
                'supporter_turnout' => $supporterTurnout,
                'trend' => 'stable',
                'last_vote_minutes_ago' => $lastVoteMinutesAgo,
            ]);

            $existing = Alert::where('type', 'center_priority_alert')
                ->where('polling_center_id', $center->id)
                ->where('is_active', true)
                ->first();

            if (in_array($decision['priority_level'], ['critical', 'high'])) {
                if (! $existing) {
                    Alert::create([
                        'type' => 'center_priority_alert',
                        'severity' => $decision['priority_level'] === 'critical' ? 'critical' : 'warning',
                        'title' => 'مركز يحتاج تدخلاً عاجلاً',
                        'message' => "{$center->name}: {$decision['decision_recommendation']}",
                        'polling_center_id' => $center->id,
                        'meta' => [
                            'priority_score' => $decision['priority_score'],
                            'priority_level' => $decision['priority_level'],
                            'supporters_remaining' => $supportersRemaining,
                            'supporter_turnout' => $supporterTurnout,
                            'last_vote_minutes_ago' => $lastVoteMinutesAgo,
                        ],
                        'is_active' => true,
                        'detected_at' => now(),
                    ]);
                } else {
                    $existing->update([
                        'message' => "{$center->name}: {$decision['decision_recommendation']}",
                        'meta' => [
                            'priority_score' => $decision['priority_score'],
                            'priority_level' => $decision['priority_level'],
                            'supporters_remaining' => $supportersRemaining,
                            'supporter_turnout' => $supporterTurnout,
                            'last_vote_minutes_ago' => $lastVoteMinutesAgo,
                        ],
                    ]);
                }
            } else {
                if ($existing) {
                    $existing->update([
                        'is_active' => false,
                        'resolved_at' => now(),
                    ]);
                }
            }
        }
    }
}
