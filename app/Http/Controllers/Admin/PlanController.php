<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlanController extends Controller
{
    /**
     * Display a listing of subscription plans
     */
    public function index(): View
    {
        $plans = SubscriptionPlan::ordered()->get();
        
        return view('admin.plans.index', compact('plans'));
    }

    /**
     * Show the form for creating a new plan
     */
    public function create(): View
    {
        return view('admin.plans.create');
    }

    /**
     * Store a newly created plan
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:subscription_plans,slug',
            'description' => 'nullable|string',
            'duration_days' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'discount_percentage' => 'nullable|integer|min:0|max:100',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'features' => 'nullable|array',
            'features.*' => 'string|max:255'
        ]);

        try {
            DB::beginTransaction();

            // Generate slug if not provided
            if (empty($validated['slug'])) {
                $validated['slug'] = Str::slug($validated['name']);
            }

            // Set default sort order if not provided
            if (!isset($validated['sort_order'])) {
                $validated['sort_order'] = SubscriptionPlan::max('sort_order') + 1;
            }

            $plan = SubscriptionPlan::create($validated);

            DB::commit();

            Log::info('Subscription plan created', [
                'plan_id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug
            ]);

            return redirect()->route('admin.plans.index')
                ->with('success', 'پلن اشتراک با موفقیت ایجاد شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create subscription plan', [
                'error' => $e->getMessage(),
                'data' => $validated
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'خطا در ایجاد پلن اشتراک: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified plan
     */
    public function show(SubscriptionPlan $plan): View
    {
        $plan->load('subscriptions.user');
        
        return view('admin.plans.show', compact('plan'));
    }

    /**
     * Show the form for editing the specified plan
     */
    public function edit(SubscriptionPlan $plan): View
    {
        return view('admin.plans.edit', compact('plan'));
    }

    /**
     * Update the specified plan
     */
    public function update(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:subscription_plans,slug,' . $plan->id,
            'description' => 'nullable|string',
            'duration_days' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'discount_percentage' => 'nullable|integer|min:0|max:100',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'features' => 'nullable|array',
            'features.*' => 'string|max:255'
        ]);

        try {
            DB::beginTransaction();

            $plan->update($validated);

            DB::commit();

            Log::info('Subscription plan updated', [
                'plan_id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug
            ]);

            return redirect()->route('admin.plans.index')
                ->with('success', 'پلن اشتراک با موفقیت به‌روزرسانی شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update subscription plan', [
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
                'data' => $validated
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'خطا در به‌روزرسانی پلن اشتراک: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified plan
     */
    public function destroy(SubscriptionPlan $plan): RedirectResponse
    {
        try {
            // Check if plan has active subscriptions
            $activeSubscriptions = $plan->subscriptions()->where('status', 'active')->count();
            
            if ($activeSubscriptions > 0) {
                return redirect()->route('admin.plans.index')
                    ->with('error', 'نمی‌توان پلنی که اشتراک فعال دارد را حذف کرد.');
            }

            DB::beginTransaction();

            $planName = $plan->name;
            $plan->delete();

            DB::commit();

            Log::info('Subscription plan deleted', [
                'plan_name' => $planName
            ]);

            return redirect()->route('admin.plans.index')
                ->with('success', 'پلن اشتراک با موفقیت حذف شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete subscription plan', [
                'plan_id' => $plan->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.plans.index')
                ->with('error', 'خطا در حذف پلن اشتراک: ' . $e->getMessage());
        }
    }

    /**
     * Toggle plan active status
     */
    public function toggleStatus(SubscriptionPlan $plan): RedirectResponse
    {
        try {
            $plan->update(['is_active' => !$plan->is_active]);
            
            $status = $plan->is_active ? 'فعال' : 'غیرفعال';
            
            return redirect()->route('admin.plans.index')
                ->with('success', "پلن {$plan->name} {$status} شد.");

        } catch (\Exception $e) {
            Log::error('Failed to toggle plan status', [
                'plan_id' => $plan->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.plans.index')
                ->with('error', 'خطا در تغییر وضعیت پلن: ' . $e->getMessage());
        }
    }
}
