<?php

namespace App\Console\Commands;

use App\Services\ReferralService;
use Illuminate\Console\Command;

class ProcessReferralCompletions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'referrals:process-completions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process automatic referral completion checks';

    /**
     * Execute the console command.
     */
    public function handle(ReferralService $referralService)
    {
        $this->info('Processing referral completions...');

        $result = $referralService->processAutomaticReferralChecks();

        if ($result['success']) {
            $this->info('Referral processing completed successfully!');
            $this->info("Processed: {$result['data']['processed_referrals']} referrals");
            $this->info("Completed: {$result['data']['completed_referrals']} referrals");
            $this->info("Expired: {$result['data']['expired_referrals']} referrals");
        } else {
            $this->error('Referral processing failed: ' . $result['message']);
            return 1;
        }

        return 0;
    }
}
