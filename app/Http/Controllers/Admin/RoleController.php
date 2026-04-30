<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
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

    /**
     * Assign role to user
     */
    public function assign(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id'
        ]);

        try {
            $user = \App\Models\User::findOrFail($request->user_id);
            $role = Role::findOrFail($request->role_id);

            // Assign role to user
            $user->role_id = $role->id;
            $user->save();

            return redirect()->back()
                ->with('success', "نقش '{$role->display_name}' با موفقیت به کاربر '{$user->name}' اختصاص داده شد.");

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'خطا در اختصاص نقش: ' . $e->getMessage());
        }
    }

    // API Methods for Next dashboard
    public function apiIndex(Request $request)
    {
        $query = $this->buildRoleApiQuery($request);
        $this->applyRoleListSort($query, $request);

        $perPage = $this->resolveRolePerPage($request);
        $page = max(1, (int) $request->input('page', 1));
        $paginator = $query->with(['permissions'])->paginate($perPage, ['*'], 'page', $page);

        return AdminApiResponse::paginated($paginator);
    }

    public function apiExport(Request $request)
    {
        $query = $this->buildRoleApiQuery($request);
        $this->applyRoleListSort($query, $request);

        $filename = 'roles-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['id', 'name', 'display_name', 'description', 'is_active', 'permission_ids', 'created_at']);

            $query->clone()->with('permissions')->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->id,
                        $row->name,
                        $row->display_name,
                        $row->description,
                        $row->is_active ? '1' : '0',
                        $row->permissions->pluck('id')->implode('|'),
                        $row->created_at?->toIso8601String(),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function apiStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'status' => 'nullable|in:active,inactive',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $isActive = array_key_exists('is_active', $validated)
            ? (bool) $validated['is_active']
            : (($validated['status'] ?? 'active') === 'active');

        $role = Role::create([
            'name' => $validated['name'],
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $isActive,
        ]);

        if (! empty($validated['permissions'])) {
            $role->permissions()->attach($validated['permissions']);
        }

        return AdminApiResponse::success($role->load('permissions'), 'Role created successfully', 201);
    }

    public function apiShow(Role $role)
    {
        return AdminApiResponse::success($role->load('permissions'));
    }

    public function apiUpdate(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:roles,name,'.$role->id,
            'display_name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'status' => 'nullable|in:active,inactive',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $payload = collect($validated)->only(['name', 'display_name', 'description'])->all();

        if (array_key_exists('is_active', $validated)) {
            $payload['is_active'] = (bool) $validated['is_active'];
        } elseif (array_key_exists('status', $validated)) {
            $payload['is_active'] = $validated['status'] === 'active';
        }

        if ($payload !== []) {
            $role->update($payload);
        }

        if (array_key_exists('permissions', $validated)) {
            $perms = $validated['permissions'];
            if ($perms === null || $perms === []) {
                $role->permissions()->detach();
            } else {
                $role->permissions()->sync($perms);
            }
        }

        return AdminApiResponse::success($role->fresh()->load('permissions'), 'Role updated successfully');
    }

    public function apiDestroy(Role $role)
    {
        if ($role->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'این نقش در حال استفاده است و نمی‌توان آن را حذف کرد.',
                'error' => 'VALIDATION_ERROR',
            ], 422);
        }

        $role->delete();

        return AdminApiResponse::okMessage('نقش با موفقیت حذف شد.');
    }

    public function apiBulkAction(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:delete,activate,deactivate',
            'selected_items' => 'nullable|array',
            'selected_items.*' => 'integer|exists:roles,id',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'integer|exists:roles,id',
        ]);

        $ids = $validated['role_ids'] ?? $validated['selected_items'] ?? [];
        if (count($ids) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'هیچ موردی انتخاب نشده است.',
                'error' => 'VALIDATION_ERROR',
            ], 422);
        }

        if ($validated['action'] === 'delete') {
            $used = Role::whereIn('id', $ids)->whereHas('users')->pluck('name')->all();
            if ($used !== []) {
                return response()->json([
                    'success' => false,
                    'message' => 'برخی نقش‌های انتخاب‌شده در حال استفاده هستند: '.implode(', ', $used),
                    'error' => 'VALIDATION_ERROR',
                ], 422);
            }
            Role::whereIn('id', $ids)->delete();

            return AdminApiResponse::okMessage('نقش‌های انتخاب شده با موفقیت حذف شدند.');
        }

        $query = Role::whereIn('id', $ids);
        if ($validated['action'] === 'activate') {
            $query->update(['is_active' => true]);
            $message = 'نقش‌های انتخاب شده با موفقیت فعال شدند.';
        } else {
            $query->update(['is_active' => false]);
            $message = 'نقش‌های انتخاب شده با موفقیت غیرفعال شدند.';
        }

        return AdminApiResponse::okMessage($message);
    }

    public function apiStatistics()
    {
        return AdminApiResponse::success([
            'total_roles' => Role::count(),
            'active_roles' => Role::where('is_active', true)->count(),
            'inactive_roles' => Role::where('is_active', false)->count(),
            'roles_with_permissions' => Role::whereHas('permissions')->count(),
            'roles_without_permissions' => Role::whereDoesntHave('permissions')->count(),
            'total_permissions' => Permission::count(),
        ]);
    }

    private function buildRoleApiQuery(Request $request)
    {
        $query = Role::query();

        $search = trim((string) $request->input('q', $request->input('search', '')));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('display_name', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('is_active')) {
            $val = $request->input('is_active');
            $query->where('is_active', filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $val);
        }

        if ($request->filled('dateFrom')) {
            $query->whereDate('created_at', '>=', $request->dateFrom);
        }

        if ($request->filled('dateTo')) {
            $query->whereDate('created_at', '<=', $request->dateTo);
        }

        if ($request->filled('date_range')) {
            switch ($request->date_range) {
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

        return $query;
    }

    private function applyRoleListSort($query, Request $request): void
    {
        $sortBy = $request->input('sortBy', 'created_at');
        $sortDir = strtolower((string) $request->input('sortDir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = ['created_at', 'id', 'name', 'display_name', 'is_active'];
        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'created_at';
        }
        $query->orderBy($sortBy, $sortDir);
    }

    private function resolveRolePerPage(Request $request): int
    {
        $raw = (int) $request->input('perPage', $request->input('per_page', 20));

        return min(100, max(1, $raw ?: 20));
    }
}