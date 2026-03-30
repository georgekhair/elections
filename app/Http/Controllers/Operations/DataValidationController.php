<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\PollingCenter;
use App\Models\Voter;

class DataValidationController extends Controller
{
    public function index()
    {
        /*
        |--------------------------------------------------------------------------
        | Global Totals
        |--------------------------------------------------------------------------
        */

        $totalVoters = Voter::count();

        $supporters = Voter::where('support_status', 'supporter')->count();
        $leaning = Voter::where('support_status', 'leaning')->count();
        $undecided = Voter::where('support_status', 'undecided')->count();
        $opposed = Voter::where('support_status', 'opposed')->count();
        $unknown = Voter::where('support_status', 'unknown')->count();

        $highPriority = Voter::where('priority_level', 'high')->count();
        $mediumPriority = Voter::where('priority_level', 'medium')->count();
        $lowPriority = Voter::where('priority_level', 'low')->count();

        $withoutDelegate = Voter::whereNull('assigned_delegate_id')->count();
        $withoutCenter = Voter::whereNull('polling_center_id')->count();

        $highPriorityUnknown = Voter::where('priority_level', 'high')
            ->where('support_status', 'unknown')
            ->count();

        $targetUnassigned = Voter::where(function ($q) {
                $q->whereIn('support_status', ['leaning', 'undecided'])
                  ->orWhere(function ($qq) {
                      $qq->where('support_status', 'supporter')
                         ->where('priority_level', 'high');
                  });
            })
            ->whereNull('assigned_delegate_id')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Center Analysis
        |--------------------------------------------------------------------------
        */

        $centers = PollingCenter::withCount([
            'voters',
            'voters as supporters_count' => fn($q) => $q->where('support_status', 'supporter'),
            'voters as leaning_count' => fn($q) => $q->where('support_status', 'leaning'),
            'voters as undecided_count' => fn($q) => $q->where('support_status', 'undecided'),
            'voters as opposed_count' => fn($q) => $q->where('support_status', 'opposed'),
            'voters as unknown_count' => fn($q) => $q->where('support_status', 'unknown'),
            'voters as assigned_count' => fn($q) => $q->whereNotNull('assigned_delegate_id'),
            'voters as high_priority_count' => fn($q) => $q->where('priority_level', 'high'),
            'voters as high_priority_unknown_count' => fn($q) => $q->where('priority_level', 'high')
                ->where('support_status', 'unknown'),
        ])->get()->map(function ($center) {
            $total = $center->voters_count ?: 0;
            $classified = $center->supporters_count
                + $center->leaning_count
                + $center->undecided_count
                + $center->opposed_count;

            $classificationRate = $total > 0
                ? round(($classified / $total) * 100)
                : 0;

            $assignmentRate = $total > 0
                ? round(($center->assigned_count / $total) * 100)
                : 0;

            $readinessScore = round(($classificationRate * 0.6) + ($assignmentRate * 0.4));

            return [
                'id' => $center->id,
                'name' => $center->name,
                'voters_count' => $total,
                'supporters_count' => $center->supporters_count,
                'leaning_count' => $center->leaning_count,
                'undecided_count' => $center->undecided_count,
                'opposed_count' => $center->opposed_count,
                'unknown_count' => $center->unknown_count,
                'assigned_count' => $center->assigned_count,
                'high_priority_count' => $center->high_priority_count,
                'high_priority_unknown_count' => $center->high_priority_unknown_count,
                'classification_rate' => $classificationRate,
                'assignment_rate' => $assignmentRate,
                'readiness_score' => $readinessScore,
            ];
        })->sortByDesc('readiness_score')->values();

        /*
        |--------------------------------------------------------------------------
        | Quality Alerts
        |--------------------------------------------------------------------------
        */

        $qualityAlerts = collect();

        foreach ($centers as $center) {
            if ($center['unknown_count'] > 0 && $center['classification_rate'] < 60) {
                $qualityAlerts->push([
                    'severity' => 'warning',
                    'center' => $center['name'],
                    'message' => 'نسبة التصنيف منخفضة ويوجد عدد كبير من unknown.',
                ]);
            }

            if ($center['assignment_rate'] < 50) {
                $qualityAlerts->push([
                    'severity' => 'critical',
                    'center' => $center['name'],
                    'message' => 'نسبة توزيع الناخبين على المندوبين منخفضة.',
                ]);
            }

            if ($center['high_priority_unknown_count'] > 20) {
                $qualityAlerts->push([
                    'severity' => 'warning',
                    'center' => $center['name'],
                    'message' => 'عدد كبير من high priority ما زال غير معروف التوجه.',
                ]);
            }
        }

        return view('operations.data-validation.index', [
            'metrics' => [
                'total_voters' => $totalVoters,
                'supporters' => $supporters,
                'leaning' => $leaning,
                'undecided' => $undecided,
                'opposed' => $opposed,
                'unknown' => $unknown,
                'high_priority' => $highPriority,
                'medium_priority' => $mediumPriority,
                'low_priority' => $lowPriority,
                'without_delegate' => $withoutDelegate,
                'without_center' => $withoutCenter,
                'high_priority_unknown' => $highPriorityUnknown,
                'target_unassigned' => $targetUnassigned,
            ],
            'centers' => $centers,
            'qualityAlerts' => $qualityAlerts,
        ]);
    }
}
