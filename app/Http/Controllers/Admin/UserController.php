<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PollingCenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Models\Voter;
use App\Services\VoterAssignmentService;
use App\Models\UserFamilyAssignment;



class UserController extends Controller
{
    public function index()
    {
        $users = User::with('pollingCenter')->latest()->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $centers = PollingCenter::all();
        $roles = Role::pluck('name');

        return view('admin.users.create', compact('centers', 'roles'));
    }

    public function store(Request $request, VoterAssignmentService $assignmentService)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required',
            'phone' => 'nullable|string|max:30',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'polling_center_id' => $request->polling_center_id,
            'is_active' => true,
        ]);

        $user->syncRoles([$request->role]);

        // 🔥 future-proof (safe even if no voters yet)
        $assignmentService->syncUserRoleAssignments($user);

        return redirect()->route('admin.users.index')
            ->with('success', 'تم إنشاء المستخدم بنجاح');
    }

    public function edit(User $user)
    {
        $centers = PollingCenter::all();
        $roles = Role::pluck('name');

        return view('admin.users.edit', compact('user', 'centers', 'roles'));
    }

    public function update(Request $request, User $user, VoterAssignmentService $assignmentService)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required',
            'phone' => 'nullable|string|max:30',
        ]);

        // 🟢 Step 1: store old role
        $oldRole = $user->getRoleNames()->first();

        // 🟢 Step 2: update user data
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'polling_center_id' => $request->polling_center_id,
            'is_active' => $request->is_active ?? false,
        ]);

        // 🟢 Step 3: password update
        if ($request->filled('password')) {
            $user->update([
                'password' => Hash::make($request->password)
            ]);
        }

        // 🟢 Step 4: update role
        $user->syncRoles([$request->role]);

        // 🔥 Step 5: sync voters ONLY if role changed
        if ($oldRole !== $request->role) {
            $assignmentService->syncUserRoleAssignments($user);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'تم تحديث المستخدم');
    }

    public function updateRole(Request $request, User $user, VoterAssignmentService $assignmentService)
    {
        $oldRole = $user->getRoleNames()->first();
        $newRole = $request->role;

        $user->syncRoles([$newRole]);

        if ($oldRole !== $newRole) {
            $assignmentService->syncUserRoleAssignments($user);
        }

        return back()->with('success', 'تم تحديث الدور');
    }

    public function destroy(User $user)
    {
        // ❌ لا تحذف نفسك
        if ($user->id === auth()->id()) {
            return back()->with('error', 'لا يمكنك حذف نفسك');
        }
        // ❌ لا تحذف آخر admin
        if ($user->hasRole('admin') && Role::where('name', 'admin')->count() <= 1) {
            return back()->with('error', 'لا يمكن حذف آخر admin في النظام');
        }

        // 1. فك ارتباط الناخبين
        Voter::where('assigned_user_id', $user->id)
            ->update([
                'assigned_user_id' => null,
                'assigned_delegate_id' => null,
                'supervisor_id' => null,
            ]);

        Voter::where('voted_by', $user->id)
            ->update(['voted_by' => null]);

        // 2. فك ارتباط المهام (إذا موجودة)
        if (class_exists(FieldTask::class)) {
            \App\Models\FieldTask::where('user_id', $user->id)
                ->update(['user_id' => null]);

            FieldTask::where('created_by', $user->id)
                ->update(['created_by' => null]);
        }

        // 3. حذف المستخدم
        $user->delete();

        return back()->with('success', 'تم حذف المستخدم بنجاح');
    }

    public function families()
    {
        $users = User::all();

        $families = Voter::select('family_name')
            ->distinct()
            ->pluck('family_name');

        return view('admin.users.families', compact('users', 'families'));
    }

    public function assignFamilies(Request $request)
    {
        $user = User::findOrFail($request->user_id);

        // حذف القديم
        $user->familyAssignments()->delete();

        foreach ($request->families as $family) {
            $user->familyAssignments()->create([
                'family_name' => $family,
                'priority' => 1
            ]);
        }

        return back()->with('success', 'تم تعيين العائلات بنجاح');
    }
}
