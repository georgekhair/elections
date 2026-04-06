<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Voter;
use App\Models\User;
use Illuminate\Http\Request;

class SupervisorDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $centerId = $user->polling_center_id;
        $centerName = $user->pollingCenter->name ?? '—';
        /*
        |--------------------------------------------------------------------------
        | Voters Statistics
        |--------------------------------------------------------------------------
        */

        $totalVoters = Voter::visibleTo($user)->count();

        $voted = Voter::visibleTo($user)
            ->where('is_voted', true)
            ->count();

        $supporters = Voter::visibleTo($user)
            ->where('support_status', 'supporter')
            ->count();

        $supportersVoted = Voter::visibleTo($user)
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

        $delegates = $user->delegates;

        /*
        |--------------------------------------------------------------------------
        | Delegate Activity (votes)
        |--------------------------------------------------------------------------
        */

        $delegateActivity = Voter::visibleTo($user)
            ->whereNotNull('assigned_delegate_id')
            ->where('is_voted', true)
            ->selectRaw('assigned_delegate_id, COUNT(*) as votes')
            ->groupBy('assigned_delegate_id')
            ->pluck('votes', 'assigned_delegate_id');

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

        $delegateLastActivity = Voter::visibleTo($user)
            ->whereNotNull('assigned_delegate_id')
            ->whereNotNull('voted_at')
            ->where('voted_by_role', 'delegate')
            ->selectRaw('assigned_delegate_id, MAX(voted_at) as last_vote_at')
            ->groupBy('assigned_delegate_id')
            ->pluck('last_vote_at', 'assigned_delegate_id');

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
            'centerName' => $centerName,

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
        if ($delegate->supervisor_id !== $supervisor->id) {
            abort(403);
        }

        $voters = Voter::visibleTo($supervisor)
            ->where('assigned_delegate_id', $delegate->id)
            ->with(['pollingCenter', 'votedByUser'])
            ->paginate(50);

        return view('supervisor.delegate-voters', [
            'delegate' => $delegate,
            'voters' => $voters
        ]);
    }

    public function markVoted($voterId)
    {
        $user = auth()->user();

        $voter = Voter::visibleTo($user)
            ->where('id', $voterId)
            ->firstOrFail();

        // 🔒 ensure supervisor owns this voter (via delegate)
        if (
            $voter->supervisor_id !== $user->id &&
            $voter->assigned_delegate_id !== null
        ) {
            abort(403);
        }

        if ($voter->is_voted) {
            return back()->with('error', 'تم تسجيل هذا الناخب مسبقاً.');
        }

        $voter->update([
            'is_voted' => true,
            'voted_at' => now(),
            'voted_by' => $user->id,
            'voted_by_role' => 'supervisor', // ⭐ important
        ]);

        return back()->with('success', 'تم تسجيل الاقتراع بواسطة المشرف');
    }

    public function voters(Request $request)
    {
        $user = auth()->user();

        $query = Voter::visibleTo($user);

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $voters = $query
            ->with(['pollingCenter', 'assignedDelegate'])
            ->orderBy('full_name')
            ->limit(20)
            ->get();

        // 🔥 IMPORTANT: return JSON for AJAX
        if ($request->ajax()) {
            return response()->json($voters);
        }

        return view('supervisor.voters', compact('voters'));
    }
}
