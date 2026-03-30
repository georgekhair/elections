<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Voter;
use App\Models\User;

class SupervisorDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $centerId = $user->polling_center_id;

        /*
        |--------------------------------------------------------------------------
        | Voters Statistics
        |--------------------------------------------------------------------------
        */

        $totalVoters = Voter::where('polling_center_id', $centerId)->count();

        $voted = Voter::where('polling_center_id', $centerId)
            ->where('is_voted', true)
            ->count();

        $supporters = Voter::where('polling_center_id', $centerId)
            ->where('support_status', 'supporter')
            ->count();

        $supportersVoted = Voter::where('polling_center_id', $centerId)
            ->where('support_status', 'supporter')
            ->where('is_voted', true)
            ->count();

        $supportersRemaining = $supporters - $supportersVoted;

        $turnout = $totalVoters > 0
            ? round(($voted / $totalVoters) * 100)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Delegates in this Center
        |--------------------------------------------------------------------------
        */

        $delegates = User::role('delegate')
            ->where('polling_center_id', $centerId)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Delegate Activity (votes)
        |--------------------------------------------------------------------------
        */

        $delegateActivity = Voter::selectRaw('voted_by, COUNT(*) as votes')
            ->where('polling_center_id', $centerId)
            ->whereNotNull('voted_by')
            ->groupBy('voted_by')
            ->pluck('votes', 'voted_by');

        /*
        |--------------------------------------------------------------------------
        | Delegate Assigned Voters
        |--------------------------------------------------------------------------
        */

        $delegateAssigned = Voter::selectRaw('assigned_delegate_id, COUNT(*) as total')
            ->where('polling_center_id', $centerId)
            ->whereNotNull('assigned_delegate_id')
            ->groupBy('assigned_delegate_id')
            ->pluck('total', 'assigned_delegate_id');

        /*
        |--------------------------------------------------------------------------
        | Delegate Last Activity
        |--------------------------------------------------------------------------
        */

        $delegateLastActivity = Voter::selectRaw('voted_by, MAX(voted_at) as last_vote_at')
            ->where('polling_center_id', $centerId)
            ->whereNotNull('voted_by')
            ->groupBy('voted_by')
            ->pluck('last_vote_at', 'voted_by');

        /*
        |--------------------------------------------------------------------------
        | Leaderboard (ترتيب المندوبين)
        |--------------------------------------------------------------------------
        */

        $leaderboard = $delegates->map(function ($delegate) use ($delegateAssigned, $delegateActivity, $delegateLastActivity) {

            $assigned = (int) ($delegateAssigned[$delegate->id] ?? 0);
            $votes = (int) ($delegateActivity[$delegate->id] ?? 0);

            $rate = $assigned > 0
                ? round(($votes / $assigned) * 100)
                : 0;

            return [
                'id' => $delegate->id,
                'name' => $delegate->name,
                'phone' => $delegate->phone ?? null,
                'assigned' => $assigned,
                'votes' => $votes,
                'rate' => $rate,
                'last_activity' => $delegateLastActivity[$delegate->id] ?? null,
            ];

        })
        ->sortByDesc('rate') // ترتيب حسب الأداء
        ->values();

        return view('supervisor.dashboard', [
            'totalVoters' => $totalVoters,
            'voted' => $voted,
            'supporters' => $supporters,
            'supportersVoted' => $supportersVoted,
            'supportersRemaining' => $supportersRemaining,
            'turnout' => $turnout,

            // existing (لا نكسر شيء)
            'delegates' => $delegates,
            'delegateActivity' => $delegateActivity,
            'delegateAssigned' => $delegateAssigned,

            // new
            'leaderboard' => $leaderboard,
        ]);
    }

    public function delegateVoters(User $delegate)
    {
        $supervisor = auth()->user();

        // 🔒 حماية: لازم نفس المركز
        if ($delegate->polling_center_id != $supervisor->polling_center_id) {
            abort(403);
        }

        $voters = \App\Models\Voter::where('assigned_delegate_id', $delegate->id)
            ->with('pollingCenter')
            ->paginate(50);

        return view('supervisor.delegate-voters', [
            'delegate' => $delegate,
            'voters' => $voters
        ]);
    }
}
