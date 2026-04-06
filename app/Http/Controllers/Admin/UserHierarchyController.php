<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserHierarchyService;
use Illuminate\Http\Request;

class UserHierarchyController extends Controller
{
    public function index(UserHierarchyService $hierarchyService)
    {
        $data = $hierarchyService->getHierarchyData();

        $groupedSupervisors = collect($data['supervisors'])
            ->groupBy(function ($s) {
                return $s->pollingCenter->name ?? 'بدون مركز';
            });

        return view('admin.user-hierarchy.index', [
            'supervisors' => $data['supervisors'], // keep original (used elsewhere)
            'groupedSupervisors' => $groupedSupervisors, // 🔥 NEW
            'unassignedDelegates' => $data['unassignedDelegates'],
        ]);
    }

    public function assign(Request $request, UserHierarchyService $hierarchyService)
    {
        $request->validate([
            'delegate_ids' => ['required', 'array', 'min:1'],
            'delegate_ids.*' => ['integer', 'exists:users,id'],
            'supervisor_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $hierarchyService->bulkAssignDelegates(
            $request->delegate_ids,
            $request->supervisor_id
        );

        return back()->with('success', 'تم تحديث الهيكل التنظيمي بنجاح');
    }

    public function move(Request $request, UserHierarchyService $hierarchyService)
    {
        $request->validate([
            'delegate_id' => ['required', 'integer', 'exists:users,id'],
            'supervisor_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $hierarchyService->moveDelegate(
            $request->delegate_id,
            $request->supervisor_id
        );

        return response()->json([
            'success' => true,
            'message' => 'تم نقل المندوب بنجاح',
        ]);
    }
}
