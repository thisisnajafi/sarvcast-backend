<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class TestUserSearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:user-search {query?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test user search functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $query = $this->argument('query') ?? '0913';
        
        $this->info("Testing user search with query: {$query}");
        
        // Test the same logic as the UserController search method
        $users = User::where(function($q) use ($query) {
                $q->where('phone_number', 'LIKE', "%{$query}%")
                  ->orWhere('first_name', 'LIKE', "%{$query}%")
                  ->orWhere('last_name', 'LIKE', "%{$query}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$query}%"]);
            })
            ->select('id', 'first_name', 'last_name', 'email', 'phone_number', 'status')
            ->where('status', 'active')
            ->orderByRaw("CASE WHEN phone_number LIKE ? THEN 1 ELSE 2 END", ["%{$query}%"])
            ->orderBy('first_name', 'asc')
            ->limit(20)
            ->get();
        
        if ($users->count() > 0) {
            $this->info("Found {$users->count()} users:");
            foreach ($users as $user) {
                $phoneMatch = strpos($user->phone_number, $query) !== false ? '✓' : '✗';
                $this->line("ID: {$user->id} - {$user->first_name} {$user->last_name} - {$user->phone_number} {$phoneMatch}");
            }
        } else {
            $this->warn('No users found');
        }
        
        $this->info('User search test completed!');
    }
}