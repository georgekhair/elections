<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\PollingCenter;
use App\Models\Voter;

class MobilizationController extends Controller
{
    public function index()
    {
        // إجماليات عامة
        $supporters = Voter::where('support_status', 'supporter')->count();

        $supportersVoted = Voter::where('support_status', 'supporter')
            ->where('is_voted', true)
            ->count();

        $supportersRemaining = $supporters - $supportersVoted;

        $supporterTurnout = $supporters > 0
            ? round(($supportersVoted / $supporters) * 100)
            : 0;

        // بيانات المراكز
        $centers = PollingCenter::withCount([
            'voters as supporters' => function ($q) {
                $q->where('support_status', 'supporter');
            },

            'voters as supporters_voted' => function ($q) {
                $q->where('support_status', 'supporter')
                  ->where('is_voted', true);
            }
        ])->get();

        foreach ($centers as $center) {
            $center->supporters_remaining = $center->supporters - $center->supporters_voted;

            $center->supporter_turnout = $center->supporters > 0
                ? round(($center->supporters_voted / $center->supporters) * 100)
                : 0;
        }

        // ترتيب المراكز حسب أولوية التعبئة
        $priorityCenters = $centers->sortByDesc('supporters_remaining')->values();

        // أهم المضمونين الذين لم يصوتوا
        $topSupporters = Voter::with('pollingCenter')
            ->where('support_status', 'supporter')
            ->where('is_voted', false)
            ->orderBy('polling_center_id')
            ->limit(50)
            ->get();

        return view('operations.mobilization.index', compact(
            'supporters',
            'supportersVoted',
            'supportersRemaining',
            'supporterTurnout',
            'centers',
            'priorityCenters',
            'topSupporters'
        ));
    }
}
