<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\PollingCenter;
use App\Models\User;
use App\Models\Voter;
use App\Services\CommunicationService;

class AdminDashboardController extends Controller
{
    public function index()
    {
        /*
        |--------------------------------------------------------------------------
        | GLOBAL STATS
        |--------------------------------------------------------------------------
        */

        $totalVoters = Voter::count();

        $voted = Voter::where('is_voted', true)->count();

        $supporters = Voter::where('support_status', 'supporter')->count();

        $supportersVoted = Voter::where('support_status', 'supporter')
            ->where('is_voted', true)
            ->count();

        $supportersRemaining = $supporters - $supportersVoted;

        $delegatesCount = User::role('delegate')->count();

        $centers = PollingCenter::count();

        $alerts = Alert::where('is_active', true)
            ->latest()
            ->take(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | LOAD DELEGATES
        |--------------------------------------------------------------------------
        */

        $delegatesList = User::role('delegate')
            ->with('pollingCenter')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | PERFORMANCE DATA
        |--------------------------------------------------------------------------
        */

        // Total assigned voters per delegate
        $delegateAssigned = Voter::selectRaw('assigned_delegate_id, COUNT(*) as total')
            ->whereNotNull('assigned_delegate_id')
            ->groupBy('assigned_delegate_id')
            ->pluck('total', 'assigned_delegate_id');

        // Total voted voters per delegate
        $delegateVotes = Voter::selectRaw('assigned_delegate_id, COUNT(*) as votes')
            ->whereNotNull('assigned_delegate_id')
            ->where('is_voted', true)
            ->groupBy('assigned_delegate_id')
            ->pluck('votes', 'assigned_delegate_id');

        /*
        |--------------------------------------------------------------------------
        | COMMUNICATION SERVICE
        |--------------------------------------------------------------------------
        */

        $communication = app(CommunicationService::class);

        /*
        |--------------------------------------------------------------------------
        | BUILD FINAL DATA STRUCTURE
        |--------------------------------------------------------------------------
        */

        $delegates = $delegatesList->map(function ($delegate) use (
            $delegateAssigned,
            $delegateVotes,
            $communication
        ) {

            $assigned = (int) ($delegateAssigned[$delegate->id] ?? 0);
            $votes = (int) ($delegateVotes[$delegate->id] ?? 0);

            $rate = $assigned > 0
                ? round(($votes / $assigned) * 100)
                : 0;

            return [
                'id' => $delegate->id,
                'name' => $delegate->name,
                'phone' => $delegate->phone,
                'center' => $delegate->pollingCenter->name ?? '-',

                'assigned' => $assigned,
                'votes' => $votes,
                'rate' => $rate,

                /*
                |--------------------------------------------------------------------------
                | COMMUNICATION LINKS
                |--------------------------------------------------------------------------
                */

                'whatsapp_summary' => $delegate->phone
                    ? $communication->whatsappLink(
                        $delegate->phone,
                        $communication->delegateSummaryMessage($delegate, $assigned, $votes)
                    )
                    : null,

                'whatsapp_alert' => $delegate->phone
                    ? $communication->whatsappLink(
                        $delegate->phone,
                        $communication->lowTurnoutAlert($delegate)
                    )
                    : null,
            ];
        })
        ->sortByDesc('rate') // 🔥 sort by performance
        ->values();

        /*
        |--------------------------------------------------------------------------
        | RETURN VIEW
        |--------------------------------------------------------------------------
        */

        return view('admin.dashboard', [
            'totalVoters' => $totalVoters,
            'voted' => $voted,
            'supporters' => $supporters,
            'supportersVoted' => $supportersVoted,
            'supportersRemaining' => $supportersRemaining,
            'delegatesCount' => $delegatesCount,
            'centers' => $centers,
            'alerts' => $alerts,
            'delegates' => $delegates,
        ]);
    }
}
