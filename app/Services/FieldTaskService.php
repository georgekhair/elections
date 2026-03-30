<?php

namespace App\Services;

use App\Models\FieldTask;

class FieldTaskService
{
    public function createTask(array $data): FieldTask
    {
        return FieldTask::create([
            'user_id' => $data['user_id'] ?? null,
            'polling_center_id' => $data['polling_center_id'] ?? null,
            'created_by' => $data['created_by'] ?? null,
            'type' => $data['type'],
            'priority' => $data['priority'] ?? 'medium',
            'description' => $data['description'],
            'status' => $data['status'] ?? 'pending',
            'assigned_at' => now(),
        ]);
    }

    public function markDone(FieldTask $task): FieldTask
    {
        $task->update([
            'status' => 'done',
            'completed_at' => now(),
        ]);

        return $task;
    }

    public function markInProgress(FieldTask $task): FieldTask
    {
        $task->update([
            'status' => 'in_progress',
        ]);

        return $task;
    }
}
