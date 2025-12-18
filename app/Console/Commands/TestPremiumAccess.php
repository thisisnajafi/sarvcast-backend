<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\AccessControlService;

class TestPremiumAccess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:premium-access {user_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test premium access for a specific user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        
        $this->info("🧪 Testing premium access for User ID: {$userId}");
        $this->newLine();
        
        try {
            $user = User::find($userId);
            if (!$user) {
                $this->error("❌ User not found!");
                return 1;
            }
            
            $this->info("Found user: {$user->first_name} {$user->last_name}");
            $this->newLine();
            
            // Test User model methods
            $this->info("Testing User model methods:");
            $activeSubscription = $user->activeSubscription;
            $this->line("  activeSubscription: " . ($activeSubscription ? "Yes (ID: {$activeSubscription->id})" : 'No'));
            
            $hasActiveSubscription = $user->hasActiveSubscription();
            $this->line("  hasActiveSubscription: " . ($hasActiveSubscription ? 'Yes' : 'No'));
            $this->newLine();
            
            // Test AccessControlService
            $this->info("Testing AccessControlService:");
            $accessControlService = app(AccessControlService::class);
            $hasPremium = $accessControlService->hasPremiumAccess($userId);
            $this->line("  hasPremiumAccess: " . ($hasPremium ? 'Yes' : 'No'));
            $this->newLine();
            
            // Show subscription details if found
            if ($activeSubscription) {
                $this->info("Active subscription details:");
                $this->line("  ID: {$activeSubscription->id}");
                $this->line("  Status: {$activeSubscription->status}");
                $this->line("  Type: {$activeSubscription->type}");
                $this->line("  Start Date: {$activeSubscription->start_date}");
                $this->line("  End Date: {$activeSubscription->end_date}");
                $this->line("  Current Time: " . now());
                $this->line("  Days Remaining: " . max(0, now()->diffInDays($activeSubscription->end_date, false)));
                $this->newLine();
            }
            
            // Final result
            if ($hasPremium) {
                $this->info("✅ User has premium access!");
            } else {
                $this->error("❌ User does NOT have premium access!");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
