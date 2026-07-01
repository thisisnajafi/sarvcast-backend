<?php

namespace App\Console\Commands;

use App\Services\FirebaseAuthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class VerifyFirebaseConfig extends Command
{
    protected $signature = 'firebase:verify';

    protected $description = 'Verify Firebase/FCM alignment between Flutter google-services.json and Laravel backend';

    public function handle(FirebaseAuthService $firebaseAuthService): int
    {
        $this->info('Firebase / FCM configuration check');
        $this->line(str_repeat('=', 50));

        $ok = true;
        $backendProjectId = (string) config('notification.firebase.project_id');
        $serviceAccountPath = (string) config('notification.firebase.service_account_path');
        $flutterJsonPath = base_path('../manji-flutter/android/app/google-services.json');

        $this->newLine();
        $this->info('1. Flutter google-services.json');
        if (! File::exists($flutterJsonPath)) {
            $this->error("   Missing: {$flutterJsonPath}");
            $ok = false;
        } else {
            $flutterConfig = json_decode(File::get($flutterJsonPath), true);
            $flutterProjectId = $flutterConfig['project_info']['project_id'] ?? null;
            $clients = $flutterConfig['client'] ?? [];
            $packages = collect($clients)
                ->map(fn ($c) => $c['client_info']['android_client_info']['package_name'] ?? null)
                ->filter()
                ->values()
                ->all();

            $this->line("   Path: {$flutterJsonPath}");
            $this->line("   Project ID: {$flutterProjectId}");
            $this->line('   Package names: ' . implode(', ', $packages));

            $expectedPackages = [
                'com.avinpishtazan.manji.cafebazaar',
                'com.avinpishtazan.manji.myket',
                'com.avinpishtazan.manji.website',
            ];
            foreach ($expectedPackages as $pkg) {
                if (! in_array($pkg, $packages, true)) {
                    $this->warn("   Missing client for package: {$pkg}");
                    $ok = false;
                }
            }

            if ($flutterProjectId !== $backendProjectId) {
                $this->error("   Project mismatch: Flutter={$flutterProjectId}, Laravel={$backendProjectId}");
                $ok = false;
            } else {
                $this->line('   ✅ Project ID matches Laravel .env');
            }
        }

        $this->newLine();
        $this->info('2. Laravel service account (server push sender)');
        $this->line("   Path: {$serviceAccountPath}");

        if (! File::exists($serviceAccountPath)) {
            $this->error('   ❌ Service account JSON not found.');
            $this->line('   Download from Firebase Console → Project settings → Service accounts');
            $this->line("   → Generate new private key → save as: {$serviceAccountPath}");
            $ok = false;
        } else {
            $serviceAccount = json_decode(File::get($serviceAccountPath), true);
            $saProjectId = $serviceAccount['project_id'] ?? null;
            $this->line("   Project ID in file: {$saProjectId}");

            if ($saProjectId !== $backendProjectId) {
                $this->error("   ❌ Service account project ({$saProjectId}) != FIREBASE_PROJECT_ID ({$backendProjectId})");
                $ok = false;
            } else {
                $this->line('   ✅ Service account project matches .env');
            }

            try {
                $token = $firebaseAuthService->getAccessToken();
                if ($token) {
                    $this->line('   ✅ OAuth2 access token generated');
                } else {
                    $this->error('   ❌ Could not generate OAuth2 access token');
                    $ok = false;
                }
            } catch (\Throwable $e) {
                $this->error('   ❌ OAuth2 error: ' . $e->getMessage());
                $ok = false;
            }
        }

        $this->newLine();
        $this->info('3. Push settings');
        $this->line('   PUSH_NOTIFICATIONS_ENABLED: ' . (config('notification.push.enabled') ? 'true' : 'false'));
        $this->line('   FIREBASE_USE_V1_API: ' . (config('notification.firebase.use_v1_api') ? 'true' : 'false'));

        $this->newLine();
        if ($ok) {
            $this->info('✅ Firebase configuration is aligned and ready for push notifications.');
            return Command::SUCCESS;
        }

        $this->error('❌ Firebase configuration is incomplete. Fix the items above before implementing push features.');
        return Command::FAILURE;
    }
}
