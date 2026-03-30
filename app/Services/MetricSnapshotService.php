<?php

namespace App\Services;

use App\Models\CenterMetricSnapshot;
use App\Models\PollingCenter;

class MetricSnapshotService
{
    public function capture(): void
    {
        $centers = PollingCenter::withCount([
            'voters',
            'voters as voted_count' => fn($q) => $q->where('is_voted', true),
            'voters as supporters_total' => fn($q) => $q->where('support_status', 'supporter'),
            'voters as supporters_voted' => fn($q) => $q->where('support_status', 'supporter')->where('is_voted', true),
        ])->get();

        foreach ($centers as $center) {
            $supportersRemaining = $center->supporters_total - $center->supporters_voted;

            $supporterTurnout = $center->supporters_total > 0
                ? round(($center->supporters_voted / $center->supporters_total) * 100)
                : 0;

            CenterMetricSnapshot::create([
                'polling_center_id' => $center->id,
                'voters_total' => $center->voters_count,
                'voted_count' => $center->voted_count,
                'supporters_total' => $center->supporters_total,
                'supporters_voted' => $center->supporters_voted,
                'supporters_remaining' => $supportersRemaining,
                'supporter_turnout' => $supporterTurnout,
                'captured_at' => now(),
            ]);
        }
    }
}
