<?php

namespace App\Console\Commands;

use App\Services\LocalImportAccessService;
use Illuminate\Console\Command;

class CreateLocalImportTokenCommand extends Command
{
    protected $signature = 'admin:create-local-import-token
                            {--phone= : Super admin phone number}
                            {--user-id= : Super admin user id}
                            {--keep-existing : Do not revoke prior tokens with the same name}
                            {--generate-bootstrap-secret : Print a random LOCAL_IMPORT_BOOTSTRAP_SECRET value}';

    protected $description = 'Issue a Sanctum token for local → server story imports (Phase C remote pilot)';

    public function __construct(
        private readonly LocalImportAccessService $accessService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('generate-bootstrap-secret')) {
            $secret = $this->accessService->generateBootstrapSecret();
            $this->info('Add to server .env:');
            $this->line("LOCAL_IMPORT_BOOTSTRAP_SECRET={$secret}");
            $this->newLine();
            $this->line('Then bootstrap from local:');
            $this->line('POST /api/admin/local-import/bootstrap');
            $this->line('Header: X-Local-Import-Bootstrap: {secret}');

            return self::SUCCESS;
        }

        $userId = $this->option('user-id');
        $phone = $this->option('phone');

        try {
            $issued = $this->accessService->issueToken(
                userId: is_numeric($userId) ? (int) $userId : null,
                phone: is_string($phone) && $phone !== '' ? $phone : null,
                revokeExisting: ! $this->option('keep-existing'),
            );
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $baseUrl = rtrim((string) config('app.url'), '/') . '/api/admin';

        $this->info('Local import API token created.');
        $this->newLine();
        $this->line('User: #' . $issued['user']->id . ' (' . $issued['user']->phone_number . ')');
        $this->line('Token name: ' . $issued['token_name']);
        $this->newLine();
        $this->warn('Copy into your LOCAL manji-laravel/.env (shown once):');
        $this->line("LOCAL_IMPORT_API_BASE_URL={$baseUrl}");
        $this->line('LOCAL_IMPORT_API_TOKEN=' . $issued['plain_text_token']);
        $this->newLine();
        $this->line('Verify from local after deploy:');
        $this->line("curl -H \"Authorization: Bearer {token}\" {$baseUrl}/local-import/verify");

        return self::SUCCESS;
    }
}
