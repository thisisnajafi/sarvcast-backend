<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use Carbon\Carbon;

class FixAllActiveSubscriptionsWithoutEndDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:fix-all-end-dates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix all active subscriptions that have end_date=null';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("🔍 Finding active subscriptions with null end_date...");
        $this->newLine();
        
        $subscriptions = Subscription::where('status', 'active')
            ->whereNull('end_date')
            ->get();
        
        if ($subscriptions->isEmpty()) {
            $this->info("✅ No subscriptions need fixing!");
            return 0;
        }
        
        $this->info("Found {$subscriptions->count()} subscription(s) to fix:");
        $this->newLine();
        
        foreach ($subscriptions as $subscription) {
            $this->line("  ID: {$subscription->id} | User: {$subscription->user_id} | Type: {$subscription->type} | Start: " . ($subscription->start_date ?: 'NULL'));
        }
        
        $this->newLine();
        
        if (!$this->confirm('Do you want to fix all these subscriptions?', true)) {
            $this->warn("Operation cancelled.");
            return 1;
        }
        
        $this->newLine();
        $fixed = 0;
        $failed = 0;
        
        foreach ($subscriptions as $subscription) {
            $this->info("Processing subscription ID {$subscription->id}...");
            
            try {
                // Calculate end date
                $startDate = $subscription->start_date ? Carbon::parse($subscription->start_date) : now();
                
                $durationDays = match($subscription->type) {
                    '1month' => 30,
                    '3months' => 90,
                    '6months' => 180,
                    '1year' => 365,
                    default => 30
                };
                
                $endDate = $startDate->copy()->addDays($durationDays);
                
                $subscription->end_date = $endDate;
                $subscription->save();
                
                $this->line("  ✅ Fixed! End date set to: {$endDate}");
                $fixed++;
                
            } catch (\Exception $e) {
                $this->error("  ❌ Failed: " . $e->getMessage());
                $failed++;
            }
        }
        
        $this->newLine();
        $this->info("Summary:");
        $this->line("  ✅ Fixed: {$fixed}");
        $this->line("  ❌ Failed: {$failed}");
        $this->newLine();
        
        if ($fixed > 0) {
            $this->info("🎉 Successfully fixed {$fixed} subscription(s)!");
            $this->info("   Users should now be premium.");
        }
        
        return 0;
    }
}
