<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    public function index()
    {
        // Check if user is super admin or has roles.view permission
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->hasPermission('roles.view')) {
            abort(403, 'شما دسترسی لازم برای این بخش را ندارید.');
        }
        
        $roles = Role::with('permissions')->get();
        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        // Check if user is super admin or has roles.create permission
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->hasPermission('roles.create')) {
            abort(403, 'شما دسترسی لازم برای این بخش را ندارید.');
        }
        
        $permissions = Permission::all()->groupBy('group');
        return view('admin.roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        // Check if user is super admin or has roles.create permission
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->hasPermission('roles.create')) {
            abort(403, 'شما دسترسی لازم برای این بخش را ندارید.');
        }
        
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'display_name' => 'required|string',
            'description' => 'nullable|string',
            'permissions' => 'array',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'description' => $request->description,
        ]);

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'نقش با موفقیت ایجاد شد.');
    }

    public function edit(Role $role)
    {
        // Check if user is super admin or has roles.edit permission
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->hasPermission('roles.edit')) {
            abort(403, 'شما دسترسی لازم برای این بخش را ندارید.');
        }
        
        $permissions = Permission::all()->groupBy('group');
        $role->load('permissions');
        
        return view('admin.roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, Role $role)
    {
        // Check if user is super admin or has roles.edit permission
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->hasPermission('roles.edit')) {
            abort(403, 'شما دسترسی لازم برای این بخش را ندارید.');
        }
        
        $request->validate([
            'name' => 'required|string|unique:roles,name,' . $role->id,
            'display_name' => 'required|string',
            'description' => 'nullable|string',
            'permissions' => 'array',
        ]);

        $role->update([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'description' => $request->description,
        ]);

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'نقش با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(Role $role)
    {
        // Check if user is super admin or has roles.delete permission
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->hasPermission('roles.delete')) {
            abort(403, 'شما دسترسی لازم برای این بخش را ندارید.');
        }
        
        // Prevent deletion of super_admin role
        if ($role->name === 'super_admin') {
            return redirect()->route('admin.roles.index')
                ->with('error', 'نمی‌توانید نقش مدیر کل را حذف کنید.');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', 'نقش با موفقیت حذف شد.');
    }

    public function assignRole(Request $request, User $user)
    {
        // Check if user is super admin or has roles.assign permission
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->hasPermission('roles.assign')) {
            abort(403, 'شما دسترسی لازم برای این بخش را ندارید.');
        }
        
        $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $role = Role::findOrFail($request->role_id);
        $user->assignRole($role);

        return redirect()->back()
            ->with('success', 'نقش با موفقیت به کاربر اختصاص داده شد.');
    }

    public function removeRole(User $user, Role $role)
    {
        // Check if user is super admin or has roles.assign permission
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->hasPermission('roles.assign')) {
            abort(403, 'شما دسترسی لازم برای این بخش را ندارید.');
        }
        
        // Prevent removing super_admin role from super admin users
        if ($role->name === 'super_admin' && $user->isSuperAdmin()) {
            return redirect()->back()
                ->with('error', 'نمی‌توانید نقش مدیر کل را از کاربر حذف کنید.');
        }

        $user->removeRole($role);

        return redirect()->back()
            ->with('success', 'نقش با موفقیت از کاربر حذف شد.');
    }
}