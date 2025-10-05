<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Episode;
use App\Services\AccessControlService;

class TestEpisodeAccess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:episode-access {user_id} {episode_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test episode access for a specific user and episode';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        $episodeId = $this->argument('episode_id');
        
        $this->info("🧪 Testing episode access for User ID: {$userId}, Episode ID: {$episodeId}");
        $this->newLine();
        
        $user = User::find($userId);
        if (!$user) {
            $this->error("❌ User not found!");
            return 1;
        }
        
        $episode = Episode::with('story')->find($episodeId);
        if (!$episode) {
            $this->error("❌ Episode not found!");
            return 1;
        }
        
        $this->info("Found user and episode:");
        $this->line("  User: {$user->first_name} {$user->last_name} (ID: {$user->id})");
        $this->line("  Episode: {$episode->title} (ID: {$episode->id})");
        $this->line("  Episode Premium: " . ($episode->is_premium ? 'Yes' : 'No'));
        $this->line("  Story: {$episode->story->title}");
        $this->line("  Free Episodes: " . ($episode->story->free_episodes ?? 0));
        $this->newLine();
        
        // Check user's subscriptions
        $subscriptions = \App\Models\Subscription::where('user_id', $userId)->get();
        $this->info("User's subscriptions:");
        foreach ($subscriptions as $sub) {
            $isActive = $sub->status === 'active' && $sub->end_date && $sub->end_date > now();
            $this->line("  ID: {$sub->id} | Status: {$sub->status} | End: {$sub->end_date} | Active: " . ($isActive ? 'Yes' : 'No'));
        }
        $this->newLine();
        
        // Test AccessControlService
        $accessControlService = app(AccessControlService::class);
        
        $this->info("Testing AccessControlService methods:");
        
        // Test hasPremiumAccess
        $hasPremium = $accessControlService->hasPremiumAccess($userId);
        $this->line("  hasPremiumAccess: " . ($hasPremium ? 'Yes' : 'No'));
        
        // Test canAccessEpisode
        $accessInfo = $accessControlService->canAccessEpisode($userId, $episodeId);
        $this->line("  canAccessEpisode:");
        $this->line("    Has Access: " . ($accessInfo['has_access'] ? 'Yes' : 'No'));
        $this->line("    Reason: {$accessInfo['reason']}");
        $this->line("    Message: {$accessInfo['message']}");
        $this->newLine();
        
        // Test User model methods
        $this->info("Testing User model methods:");
        $activeSubscription = $user->activeSubscription;
        $this->line("  activeSubscription: " . ($activeSubscription ? "Yes (ID: {$activeSubscription->id})" : 'No'));
        
        $hasActiveSubscription = $user->hasActiveSubscription();
        $this->line("  hasActiveSubscription: " . ($hasActiveSubscription ? 'Yes' : 'No'));
        $this->newLine();
        
        // Manual query test
        $this->info("Manual query test:");
        $manualActiveSub = \App\Models\Subscription::where('user_id', $userId)
            ->where('status', 'active')
            ->where('end_date', '>', now())
            ->first();
        $this->line("  Manual active subscription: " . ($manualActiveSub ? "Yes (ID: {$manualActiveSub->id})" : 'No'));
        
        if ($manualActiveSub) {
            $this->line("    Status: {$manualActiveSub->status}");
            $this->line("    End Date: {$manualActiveSub->end_date}");
            $this->line("    Current Time: " . now());
            $this->line("    Is End Date Future: " . ($manualActiveSub->end_date > now() ? 'Yes' : 'No'));
        }
        
        $this->newLine();
        
        if ($accessInfo['has_access']) {
            $this->info("✅ User has access to the episode!");
        } else {
            $this->error("❌ User does NOT have access to the episode!");
            $this->error("   Reason: {$accessInfo['reason']}");
            $this->error("   Message: {$accessInfo['message']}");
        }
        
        return 0;
    }
}
