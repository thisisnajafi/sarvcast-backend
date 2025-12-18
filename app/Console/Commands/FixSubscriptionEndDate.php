<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use Carbon\Carbon;

class FixSubscriptionEndDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:fix-end-date {subscription_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix subscription end_date for subscriptions that have status=active but end_date=null';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $subscriptionId = $this->argument('subscription_id');
        
        $this->info("🔧 Fixing subscription ID: {$subscriptionId}");
        $this->newLine();
        
        $subscription = Subscription::find($subscriptionId);
        
        if (!$subscription) {
            $this->error("❌ Subscription not found!");
            return 1;
        }
        
        $this->info("Found subscription:");
        $this->line("  ID: {$subscription->id}");
        $this->line("  User ID: {$subscription->user_id}");
        $this->line("  Type: {$subscription->type}");
        $this->line("  Status: {$subscription->status}");
        $this->line("  Start Date: " . ($subscription->start_date ?: 'NULL'));
        $this->line("  End Date: " . ($subscription->end_date ?: 'NULL'));
        $this->newLine();
        
        if ($subscription->end_date) {
            $this->warn("⚠️  Subscription already has an end_date: {$subscription->end_date}");
            
            $isActive = $subscription->status === 'active' && $subscription->end_date > now();
            if ($isActive) {
                $this->info("✅ Subscription is already properly active!");
                return 0;
            } else {
                $this->warn("⚠️  Subscription has end_date but is not active");
            }
        }
        
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
        
        $this->info("Calculated dates:");
        $this->line("  Start Date: {$startDate}");
        $this->line("  Duration: {$durationDays} days");
        $this->line("  End Date: {$endDate}");
        $this->newLine();
        
        if ($this->confirm('Do you want to update this subscription?', true)) {
            $subscription->end_date = $endDate;
            $subscription->save();
            
            $this->info("✅ Subscription updated successfully!");
            $this->newLine();
            
            // Verify
            $subscription = $subscription->fresh();
            $this->info("Verification:");
            $this->line("  Status: {$subscription->status}");
            $this->line("  Start Date: {$subscription->start_date}");
            $this->line("  End Date: {$subscription->end_date}");
            $this->line("  Updated At: {$subscription->updated_at}");
            $this->newLine();
            
            $isActive = $subscription->status === 'active' && $subscription->end_date > now();
            if ($isActive) {
                $this->info("🎉 SUCCESS! Subscription is now properly active!");
                $this->info("   User should now be premium.");
            } else {
                $this->error("❌ Subscription still not active. Check the dates.");
            }
            
            return 0;
        } else {
            $this->warn("Operation cancelled.");
            return 1;
        }
    }
}
