<?php

namespace App\Services;

use App\Models\FieldTask;

class TaskEngineService
{
    public function __construct(
        protected SmartAssignmentService $assignmentService
    ) {}

    public function run(array $centers): void
    {
        foreach ($centers as $center) {

            // 1) critical center => mobilization
            if (($center['priority_level'] ?? null) === 'critical') {
                $assignee = $this->assignmentService->assignForCenterTask($center, 'mobilization');

                $this->createTaskIfNotExists([
                    'type' => 'mobilization',
                    'priority' => 'critical',
                    'polling_center_id' => $center['id'],
                    'user_id' => $assignee?->id,
                    'description' => "تعبئة فورية للمركز: {$center['name']}",
                    'source' => 'system',
                ]);
            }

            // 2) weak turnout center
            if (($center['supporter_turnout'] ?? 0) < 40 && ($center['supporters_remaining'] ?? 0) > 50) {
                $assignee = $this->assignmentService->assignForCenterTask($center, 'center_followup');

                $this->createTaskIfNotExists([
                    'type' => 'center_followup',
                    'priority' => 'high',
                    'polling_center_id' => $center['id'],
                    'user_id' => $assignee?->id,
                    'description' => "متابعة ضعف الإقبال في المركز: {$center['name']}",
                    'source' => 'system',
                ]);
            }

            // 3) stale center activity
            if (($center['last_vote_minutes_ago'] ?? 0) > 20) {
                $assignee = $this->assignmentService->assignForCenterTask($center, 'supervisor_call');

                $this->createTaskIfNotExists([
                    'type' => 'supervisor_call',
                    'priority' => 'high',
                    'polling_center_id' => $center['id'],
                    'user_id' => $assignee?->id,
                    'description' => "الاتصال بالمشرف بسبب توقف النشاط في {$center['name']}",
                    'source' => 'system',
                ]);
            }
        }
    }

    private function createTaskIfNotExists(array $data): void
    {
        $exists = FieldTask::where('type', $data['type'])
            ->where('polling_center_id', $data['polling_center_id'] ?? null)
            ->where('status', '!=', 'done')
            ->where('created_at', '>=', now()->subMinutes(30))
            ->exists();

        if ($exists) {
            return;
        }

        FieldTask::create([
            'user_id' => $data['user_id'] ?? null,
            'polling_center_id' => $data['polling_center_id'] ?? null,
            'created_by' => null,
            'type' => $data['type'],
            'priority' => $data['priority'],
            'description' => $data['description'],
            'source' => $data['source'] ?? 'manual',
            'status' => 'pending',
            'assigned_at' => now(),
        ]);
    }
}
