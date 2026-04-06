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

        return view('delegate.dashboard', compact(
            'totalVoters',
            'voted',
            'supporters',
            'supportersVoted'
        ));
    }
}
