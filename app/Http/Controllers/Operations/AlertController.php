<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\PollingCenter;
use App\Services\AlertEngineService;

class AlertController extends Controller
{
    public function index(AlertEngineService $engine)
    {
        $engine->run();

        $alerts = Alert::with('pollingCenter')
            ->where('is_active', true)
            ->latest('detected_at')
            ->get();

        // عدد التنبيهات النشطة لكل مركز
        $centers = PollingCenter::withCount([
            'alerts as alerts_count' => function ($q) {
                $q->where('is_active', true);
            }
        ])->get();

        return view('operations.alerts.index', compact('alerts', 'centers'));
    }
}
