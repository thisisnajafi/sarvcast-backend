<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::with(['profiles', 'activeSubscription']);

        // Apply filters
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->search . '%')
                  ->orWhere('last_name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone_number', 'like', '%' . $request->search . '%');
            });
        }

        $users = $query->latest()->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $parents = User::where('role', 'parent')->get();
        return view('admin.users.create', compact('parents'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users',
            'phone_number' => 'nullable|string|unique:users',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:parent,child,admin',
            'parent_id' => 'nullable|exists:users,id',
            'status' => 'required|in:active,inactive,suspended,pending',
            'language' => 'nullable|string|max:10',
            'timezone' => 'nullable|string|max:50',
        ]);

        $data = $request->except(['password_confirmation']);
        $data['password'] = Hash::make($request->password);

        User::create($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'کاربر با موفقیت ایجاد شد.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $user->load(['profiles', 'activeSubscription', 'subscriptions', 'payments', 'playHistories', 'favorites', 'ratings']);
        
        // Get recent activity
        $recentActivity = $user->playHistories()
            ->with('episode.story')
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.users.show', compact('user', 'recentActivity'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $parents = User::where('role', 'parent')->where('id', '!=', $user->id)->get();
        return view('admin.users.edit', compact('user', 'parents'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone_number' => 'nullable|string|unique:users,phone_number,' . $user->id,
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|in:parent,child,admin',
            'parent_id' => 'nullable|exists:users,id',
            'status' => 'required|in:active,inactive,suspended,pending',
            'language' => 'nullable|string|max:10',
            'timezone' => 'nullable|string|max:50',
        ]);

        $data = $request->except(['password_confirmation']);
        
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'کاربر با موفقیت به‌روزرسانی شد.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        // Prevent deletion of admin users
        if ($user->isAdmin()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'نمی‌توان کاربران مدیر را حذف کرد.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'کاربر با موفقیت حذف شد.');
    }

    /**
     * Toggle user status
     */
    public function toggleStatus(User $user)
    {
        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        $user->update(['status' => $newStatus]);

        $message = $newStatus === 'active' ? 'کاربر فعال شد.' : 'کاربر غیرفعال شد.';
        
        return redirect()->route('admin.users.index')
            ->with('success', $message);
    }

    /**
     * Suspend user
     */
    public function suspend(User $user)
    {
        $user->update(['status' => 'suspended']);
        
        return redirect()->route('admin.users.index')
            ->with('success', 'کاربر معلق شد.');
    }

    /**
     * Activate user
     */
    public function activate(User $user)
    {
        $user->update(['status' => 'active']);
        
        return redirect()->route('admin.users.index')
            ->with('success', 'کاربر فعال شد.');
    }
}
