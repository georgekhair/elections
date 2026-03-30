<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\CenterMetricSnapshot;
use App\Models\ElectionList;
use App\Models\PollingCenter;
use App\Models\Voter;
use App\Services\AlertEngineService;
use App\Services\DecisionEngineService;
use App\Services\MetricSnapshotService;
use App\Services\SainteLagueService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use App\Services\DelegateIntelligenceService;
use App\Services\TaskEngineService;
use App\Models\FieldTask;
use Illuminate\Support\Facades\Auth;

class LiveDataController extends Controller
{
    public function commandCenter(
        AlertEngineService $alertEngine,
        SainteLagueService $service,
        MetricSnapshotService $snapshotService,
        DecisionEngineService $decisionEngine,
        TaskEngineService $taskEngine
    ): JsonResponse {
        // Recalculate alerts
        $alertEngine->run();

        // Capture current center metrics snapshot
        $snapshotService->capture();

        /*
        |--------------------------------------------------------------------------
        | Global Metrics
        |--------------------------------------------------------------------------
        */

        $totalVoters = Voter::count();

        $voted = Voter::where('is_voted', true)->count();

        $supporters = Voter::where('support_status', 'supporter')->count();

        $supportersVoted = Voter::where('support_status', 'supporter')
            ->where('is_voted', true)
            ->count();

        $supportersRemaining = $supporters - $supportersVoted;

        /*
        |--------------------------------------------------------------------------
        | Centers
        |--------------------------------------------------------------------------
        */

        $centers = PollingCenter::withCount([
            'voters',
            'voters as voted_count' => fn($q) => $q->where('is_voted', true),
            'voters as supporters' => fn($q) => $q->where('support_status', 'supporter'),
            'voters as supporters_voted' => fn($q) => $q->where('support_status', 'supporter')->where('is_voted', true),
        ])->get();

        $centers = $centers->map(function ($center) use ($decisionEngine) {

            $supportersRemaining = $center->supporters - $center->supporters_voted;

            $supporterTurnout = $center->supporters > 0
                ? round(($center->supporters_voted / $center->supporters) * 100)
                : 0;

            /*
            |--------------------------------------------------------------------------
            | Trend Calculation
            |--------------------------------------------------------------------------
            */

            $lastTwo = CenterMetricSnapshot::where('polling_center_id', $center->id)
                ->latest('captured_at')
                ->take(2)
                ->get()
                ->values();

            $trend = 'stable';
            $delta = 0;

            if ($lastTwo->count() >= 2) {
                $current = (int) $lastTwo[0]->supporter_turnout;
                $previous = (int) $lastTwo[1]->supporter_turnout;

                $delta = $current - $previous;

                if ($delta > 0) {
                    $trend = 'up';
                } elseif ($delta < 0) {
                    $trend = 'down';
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Last vote age
            |--------------------------------------------------------------------------
            */

            $lastVoteAt = Voter::where('polling_center_id', $center->id)
                ->whereNotNull('voted_at')
                ->latest('voted_at')
                ->value('voted_at');

            $lastVoteMinutesAgo = $lastVoteAt
                ? now()->diffInMinutes(Carbon::parse($lastVoteAt))
                : 999;

            /*
            |--------------------------------------------------------------------------
            | Decision Engine
            |--------------------------------------------------------------------------
            */

            $centerData = [
                'id' => $center->id,
                'name' => $center->name,
                'latitude' => $center->latitude,
                'longitude' => $center->longitude,
                'voters_count' => $center->voters_count,
                'voted_count' => $center->voted_count,
                'supporters' => $center->supporters,
                'supporters_voted' => $center->supporters_voted,
                'supporters_remaining' => $supportersRemaining,
                'supporter_turnout' => $supporterTurnout,
                'trend' => $trend,
                'trend_delta' => $delta,
                'last_vote_minutes_ago' => $lastVoteMinutesAgo,
            ];

            $decision = $decisionEngine->evaluateCenter($centerData);

            return array_merge($centerData, $decision);
        })->sortByDesc('priority_score')->values();

        $centersArray = $centers->toArray();
        $taskEngine->run($centersArray);

        /*
        |--------------------------------------------------------------------------
        | Alerts
        |--------------------------------------------------------------------------
        */

        $alerts = Alert::with('pollingCenter')
            ->where('is_active', true)
            ->latest('detected_at')
            ->take(10)
            ->get()
            ->map(function ($alert) {
                return [
                    'id' => $alert->id,
                    'title' => $alert->title,
                    'message' => $alert->message,
                    'severity' => $alert->severity,
                    'polling_center' => $alert->pollingCenter?->name,
                    'detected_at' => optional($alert->detected_at)->format('Y-m-d H:i:s'),
                ];
            })->values();


        /*
        |--------------------------------------------------------------------------
        | Delegate Intelligence
        |--------------------------------------------------------------------------
        */

        $delegates = app(DelegateIntelligenceService::class)->analyze();
        /*
        |--------------------------------------------------------------------------
        | Seat Projection
        |--------------------------------------------------------------------------
        */

        $lists = ElectionList::orderBy('id')->get();

        $votes = $lists->pluck('estimated_votes', 'name')->toArray();

        $projection = $service->allocate($votes, 13, 5);

        $seatProjection = $lists->map(function ($list) use ($projection) {
            return [
                'name' => $list->name,
                'estimated_votes' => $list->estimated_votes,
                'seats' => $projection['seats'][$list->name] ?? 0,
                'is_our_list' => (bool) $list->is_our_list,
            ];
        })->values();

        $tasks = FieldTask::with(['user', 'pollingCenter'])
            ->whereIn('status', ['pending', 'in_progress'])
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'type' => $task->type,
                    'priority' => $task->priority,
                    'status' => $task->status,
                    'source' => $task->source,
                    'user' => $task->user?->name,
                    'polling_center' => $task->pollingCenter?->name,
                    'description' => $task->description,
                ];
            })->values();


        $user = Auth::user();

        $userTasksCount = 0;

        if ($user) {
            $userTasksCount = FieldTask::where(function ($q) use ($user) {
                $q->where('user_id', $user->id);

                if ($user->hasRole('supervisor')) {
                    $q->orWhere('polling_center_id', $user->polling_center_id);
                }
            })
            ->whereIn('status', ['pending', 'in_progress'])
            ->count();
        }
        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'metrics' => [
                'total_voters' => $totalVoters,
                'voted' => $voted,
                'supporters' => $supporters,
                'supporters_voted' => $supportersVoted,
                'supporters_remaining' => $supportersRemaining,
            ],
            'centers' => $centers,
            'alerts' => $alerts,
            'tasks' => $tasks,
            'user_tasks_count' => $userTasksCount,
            'delegates' => $delegates,
            'seat_projection' => $seatProjection,
            'updated_at' => now()->format('Y-m-d H:i:s'),
        ]);
    }
}
