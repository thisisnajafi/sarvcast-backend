<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $query = Role::with(['permissions']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('display_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Date range filter
        if ($request->filled('date_range')) {
            $dateRange = $request->date_range;
            switch ($dateRange) {
                case 'today':
                    $query->whereDate('created_at', Carbon::today());
                    break;
                case 'week':
                    $query->where('created_at', '>=', Carbon::now()->subWeek());
                    break;
                case 'month':
                    $query->where('created_at', '>=', Carbon::now()->subMonth());
                    break;
                case 'year':
                    $query->where('created_at', '>=', Carbon::now()->subYear());
                    break;
            }
        }

        $roles = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::all()->groupBy('group');
        return view('admin.roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
            'status' => 'required|in:active,inactive',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'description' => $request->description,
            'status' => $request->status,
        ]);

        if ($request->permissions) {
            $role->permissions()->attach($request->permissions);
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'نقش با موفقیت ایجاد شد.');
    }

    public function show(Role $role)
    {
        $role->load(['permissions']);
        return view('admin.roles.show', compact('role'));
    }

    public function edit(Role $role)
    {
        $permissions = Permission::all()->groupBy('group');
        $role->load(['permissions']);
        return view('admin.roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
            'status' => 'required|in:active,inactive',
        ]);

        $role->update([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'description' => $request->description,
            'status' => $request->status,
        ]);

        if ($request->permissions) {
            $role->permissions()->sync($request->permissions);
        } else {
            $role->permissions()->detach();
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'نقش با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(Role $role)
    {
        // Check if role is being used by users
        if ($role->users()->count() > 0) {
            return redirect()->back()
                ->with('error', 'این نقش در حال استفاده است و نمی‌توان آن را حذف کرد.');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', 'نقش با موفقیت حذف شد.');
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,activate,deactivate',
            'selected_items' => 'required|array',
            'selected_items.*' => 'exists:roles,id',
        ]);

        $roles = Role::whereIn('id', $request->selected_items);

        switch ($request->action) {
            case 'delete':
                // Check if any role is being used
                $usedRoles = Role::whereIn('id', $request->selected_items)
                    ->whereHas('users')
                    ->pluck('name')
                    ->toArray();
                
                if (!empty($usedRoles)) {
                    return redirect()->back()
                        ->with('error', 'برخی از نقش‌های انتخاب شده در حال استفاده هستند: ' . implode(', ', $usedRoles));
                }
                
                $roles->delete();
                $message = 'نقش‌های انتخاب شده با موفقیت حذف شدند.';
                break;

            case 'activate':
                $roles->update(['status' => 'active']);
                $message = 'نقش‌های انتخاب شده با موفقیت فعال شدند.';
                break;

            case 'deactivate':
                $roles->update(['status' => 'inactive']);
                $message = 'نقش‌های انتخاب شده با موفقیت غیرفعال شدند.';
                break;
        }

        return redirect()->back()->with('success', $message);
    }

    public function export(Request $request)
    {
        $query = Role::with(['permissions']);

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('display_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $roles = $query->orderBy('created_at', 'desc')->get();

        return redirect()->back()
            ->with('success', 'گزارش نقش‌ها آماده دانلود است.');
    }

    public function statistics()
    {
        $stats = [
            'total_roles' => Role::count(),
            'active_roles' => Role::where('status', 'active')->count(),
            'inactive_roles' => Role::where('status', 'inactive')->count(),
            'roles_with_permissions' => Role::whereHas('permissions')->count(),
            'roles_without_permissions' => Role::whereDoesntHave('permissions')->count(),
            'most_used_role' => Role::withCount('users')->orderBy('users_count', 'desc')->first(),
            'total_permissions' => Permission::count(),
            'average_permissions_per_role' => Role::withCount('permissions')->avg('permissions_count'),
        ];

        $dailyStats = [];
        for ($i = 30; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dailyStats[] = [
                'date' => $date->format('Y-m-d'),
                'created' => Role::whereDate('created_at', $date)->count(),
                'assigned' => rand(0, 10), // This would come from role assignments
            ];
        }

        return view('admin.roles.statistics', compact('stats', 'dailyStats'));
    }
}