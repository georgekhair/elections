<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\PollingCenter;
use App\Models\Voter;
use App\Models\ElectionList;
use App\Services\SainteLagueService;
use App\Models\Alert;
use App\Services\AlertEngineService;

class CommandCenterController extends Controller
{
    public function index(SainteLagueService $service, AlertEngineService $alertEngine)
    {
        $alertEngine->run();

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

        foreach ($centers as $center) {

            $center->supporters_remaining =
                $center->supporters - $center->supporters_voted;

            $center->supporter_turnout =
                $center->supporters > 0
                ? round(($center->supporters_voted / $center->supporters) * 100)
                : 0;

        }

        $lists = ElectionList::all();

        $votes = $lists->pluck('estimated_votes','name')->toArray();

        $projection = $service->allocate($votes,13,5);

        $alerts = Alert::with('pollingCenter')
            ->where('is_active', true)
            ->latest('detected_at')
            ->take(10)
            ->get();

        return view('operations.command-center', compact(
            'totalVoters',
            'voted',
            'supporters',
            'supportersVoted',
            'supportersRemaining',
            'centers',
            'lists',
            'projection',
            'alerts'
        ));

    }
}
