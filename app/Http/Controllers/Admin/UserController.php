<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PollingCenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

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

        return view('admin.users.create', compact('centers','roles'));
    }

    public function store(Request $request)
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

        return redirect()->route('admin.users.index')
            ->with('success','تم إنشاء المستخدم بنجاح');
    }

    public function edit(User $user)
    {
        $centers = PollingCenter::all();
        $roles = Role::pluck('name');

        return view('admin.users.edit', compact('user','centers','roles'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'role' => 'required',
            'phone' => 'nullable|string|max:30',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'polling_center_id' => $request->polling_center_id,
            'is_active' => $request->is_active ?? false,
        ]);

        if ($request->filled('password')) {
            $user->update([
                'password' => Hash::make($request->password)
            ]);
        }

        $user->syncRoles([$request->role]);

        return redirect()->route('admin.users.index')
            ->with('success','تم تحديث المستخدم');
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
        \App\Models\Voter::where('assigned_delegate_id', $user->id)
            ->update(['assigned_delegate_id' => null]);

        \App\Models\Voter::where('voted_by', $user->id)
            ->update(['voted_by' => null]);

        // 2. فك ارتباط المهام (إذا موجودة)
        if (class_exists(\App\Models\FieldTask::class)) {
            \App\Models\FieldTask::where('user_id', $user->id)
                ->update(['user_id' => null]);

            \App\Models\FieldTask::where('created_by', $user->id)
                ->update(['created_by' => null]);
        }

        // 3. حذف المستخدم
        $user->delete();

        return back()->with('success', 'تم حذف المستخدم بنجاح');
    }
}
