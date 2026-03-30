<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\PollingCenter;
use App\Models\Voter;

class WarRoomController extends Controller
{

    public function dashboard()
    {

        $totalVoters = Voter::count();

        $voted = Voter::where('is_voted', true)->count();

        $supporters = Voter::where('support_status','supporter')->count();

        $supportersVoted = Voter::where('support_status','supporter')
            ->where('is_voted',true)
            ->count();

        $supportersRemaining = $supporters - $supportersVoted;

        $centers = PollingCenter::withCount([
            'voters',

            'voters as voted_count' => function ($q) {
                $q->where('is_voted',true);
            },

            'voters as supporters' => function ($q) {
                $q->where('support_status','supporter');
            },

            'voters as supporters_voted' => function ($q) {
                $q->where('support_status','supporter')
                  ->where('is_voted',true);
            }

        ])->get();

        return view('operations.dashboard', compact(
            'totalVoters',
            'voted',
            'supporters',
            'supportersVoted',
            'supportersRemaining',
            'centers'
        ));
    }

    /**
     * LIST SUPPORTERS WHO DID NOT VOTE
     */
    public function supportersMissing()
    {

        $voters = Voter::where('support_status','supporter')
            ->where('is_voted',false)
            ->with('pollingCenter')
            ->orderBy('polling_center_id')
            ->get();

        return view('operations.supporters-missing', compact('voters'));

    }

    public function mobilization()
    {

        $centers = \App\Models\PollingCenter::withCount([

            'voters as supporters_total' => function ($q) {
                $q->where('support_status','supporter');
            },

            'voters as supporters_voted' => function ($q) {
                $q->where('support_status','supporter')
                ->where('is_voted',true);
            }

        ])->get();

        foreach ($centers as $center) {

            $total = $center->supporters_total;
            $voted = $center->supporters_voted;

            $remaining = $total - $voted;

            $center->supporters_remaining = $remaining;

            $percentage = $total > 0
                ? round(($voted / $total) * 100)
                : 0;

            $center->supporter_turnout = $percentage;

            if ($percentage < 40) {
                $center->priority = 'CRITICAL';
            } elseif ($percentage < 60) {
                $center->priority = 'HIGH';
            } elseif ($percentage < 75) {
                $center->priority = 'MEDIUM';
            } else {
                $center->priority = 'GOOD';
            }
        }

        return view('operations.mobilization', compact('centers'));

    }

}
