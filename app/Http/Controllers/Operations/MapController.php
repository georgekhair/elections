<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\PollingCenter;

class MapController extends Controller
{
    public function index()
    {
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

            if ($center->supporter_turnout < 40) {
                $center->priority = 'critical';
            } elseif ($center->supporter_turnout < 60) {
                $center->priority = 'high';
            } elseif ($center->supporter_turnout < 75) {
                $center->priority = 'medium';
            } else {
                $center->priority = 'good';
            }

        }

        return view('operations.map', compact('centers'));
    }
}
