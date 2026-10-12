<?php

namespace App\Console\Commands;

use App\Services\SchoolPremiumGrantService;
use Illuminate\Console\Command;

class CloseSchoolPremiumWindows extends Command
{
    protected $signature = 'school:close-premium-windows';

    protected $description = 'Expire school premium windows past their grant deadline, and mark grants whose premium end date has passed';

    public function handle(SchoolPremiumGrantService $grants): int
    {
        $result = $grants->closeExpired();
        $this->info('Windows closed: '.$result['windows'].'. Grants expired: '.$result['grants'].'.');

        return self::SUCCESS;
    }
}
