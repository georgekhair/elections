<?php

namespace App\Http\Controllers\Field;

use App\Http\Controllers\Controller;
use App\Models\FieldTask;
use Illuminate\Http\Request;

class TaskInboxController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $tasks = FieldTask::with(['pollingCenter'])
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);

                // supervisor sees center tasks too
                if ($user->hasRole('supervisor')) {
                    $q->orWhere('polling_center_id', $user->polling_center_id);
                }
            })
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderByRaw("
                CASE
                    WHEN priority = 'critical' THEN 1
                    WHEN priority = 'high' THEN 2
                    WHEN priority = 'medium' THEN 3
                    ELSE 4
                END
            ")
            ->latest()
            ->get();

        return view('field.tasks.inbox', compact('tasks'));
    }
}
