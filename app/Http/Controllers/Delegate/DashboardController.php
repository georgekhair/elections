<?php

namespace App\Http\Controllers\Delegate;

use App\Http\Controllers\Controller;
use App\Models\Voter;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $centerId = $user->polling_center_id;

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

        $priorityVoters = Voter::visibleTo($user)
            ->where('is_voted', false)
            ->orderByRaw("
                CASE
                    WHEN EXISTS (
                        SELECT 1 FROM voter_notes
                        WHERE voter_notes.voter_id = voters.id
                        AND requires_action = 1
                    ) THEN 1
                    WHEN support_status IN ('undecided','leaning') THEN 2
                    WHEN support_status = 'supporter' AND priority_level = 'high' THEN 3
                    ELSE 4
                END
            ")
            ->limit(10)
            ->get();

        return view('delegate.dashboard', compact(
            'totalVoters',
            'voted',
            'supporters',
            'supportersVoted',
            'priorityVoters'
        ));
    }
}
