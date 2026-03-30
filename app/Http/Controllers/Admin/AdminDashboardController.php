<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\PollingCenter;
use App\Models\User;
use App\Models\Voter;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $totalVoters = Voter::count();

        $voted = Voter::where('is_voted', true)->count();

        $supporters = Voter::where('support_status','supporter')->count();

        $supportersVoted = Voter::where('support_status','supporter')
            ->where('is_voted',true)
            ->count();

        $supportersRemaining = $supporters - $supportersVoted;

        $delegates = User::role('delegate')->count();

        $centers = PollingCenter::count();

        $alerts = Alert::where('is_active',true)
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalVoters',
            'voted',
            'supporters',
            'supportersVoted',
            'supportersRemaining',
            'delegates',
            'centers',
            'alerts'
        ));
    }
}
