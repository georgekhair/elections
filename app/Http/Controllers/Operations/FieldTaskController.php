<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\FieldTask;
use App\Models\PollingCenter;
use App\Models\User;
use App\Services\FieldTaskService;
use Illuminate\Http\Request;

class FieldTaskController extends Controller
{
    public function index()
    {
        $tasks = FieldTask::with(['user', 'pollingCenter', 'creator'])
            ->latest()
            ->paginate(20);

        return view('operations.tasks.index', compact('tasks'));
    }

    public function create()
    {
        $centers = PollingCenter::orderBy('name')->get();

        $users = User::role(['supervisor', 'delegate', 'operations'])
            ->orderBy('name')
            ->get();

        return view('operations.tasks.create', compact('centers', 'users'));
    }

    public function store(Request $request, FieldTaskService $taskService)
    {
        $request->validate([
            'type' => 'required|string|max:255',
            'priority' => 'required|in:low,medium,high,critical',
            'description' => 'required|string',
            'polling_center_id' => 'nullable|exists:polling_centers,id',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $taskService->createTask([
            'user_id' => $request->user_id,
            'polling_center_id' => $request->polling_center_id,
            'created_by' => auth()->id(),
            'type' => $request->type,
            'priority' => $request->priority,
            'description' => $request->description,
        ]);

        return redirect()
            ->route('operations.tasks.index')
            ->with('success', 'تم إنشاء المهمة بنجاح');
    }

    public function markDone(FieldTask $task, FieldTaskService $taskService)
    {
        $taskService->markDone($task);

        return redirect()
            ->route('operations.tasks.index')
            ->with('success', 'تم إغلاق المهمة بنجاح');
    }

    public function markInProgress(FieldTask $task, FieldTaskService $taskService)
    {
        $taskService->markInProgress($task);

        return redirect()
            ->route('operations.tasks.index')
            ->with('success', 'تم تحديث المهمة إلى قيد التنفيذ');
    }
}
