<?php

namespace App\Console\Commands;

use App\Services\FirebaseAuthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class VerifyFirebaseConfig extends Command
{
    protected $signature = 'firebase:verify {--clear-cache : Clear cached Firebase OAuth token before testing}';

    protected $description = 'Verify Firebase/FCM alignment between Flutter google-services.json and Laravel backend';

    public function handle(FirebaseAuthService $firebaseAuthService): int
    {
        if ($this->option('clear-cache')) {
            \Illuminate\Support\Facades\Cache::store('file')->forget('firebase_access_token');
            $this->line('Cleared cached Firebase OAuth token.');
            $this->newLine();
        }
        $this->info('Firebase / FCM configuration check');
        $this->line(str_repeat('=', 50));

        $ok = true;
        $backendProjectId = (string) config('notification.firebase.project_id');
        $serviceAccountPath = (string) config('notification.firebase.service_account_path');
        $flutterJsonCandidates = array_values(array_filter([
            base_path('../manji-flutter/android/app/google-services.json'),
            storage_path('app/google-services.json'),
        ]));
        $flutterJsonPath = null;
        foreach ($flutterJsonCandidates as $candidate) {
            if (File::exists($candidate)) {
                $flutterJsonPath = $candidate;
                break;
            }
        }

        $this->newLine();
        $this->info('1. Flutter google-services.json (optional on server)');
        if ($flutterJsonPath === null) {
            $this->warn('   Skipped: google-services.json not found (not required for server push).');
            $this->line('   Checked: ' . implode(', ', $flutterJsonCandidates));
            $this->line('   Server push only needs the Firebase service account JSON + FIREBASE_PROJECT_ID.');
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
            $this->line('   This file is gitignored and must be uploaded manually on each server.');
            $this->line('   Download from Firebase Console → Project settings → Service accounts');
            $this->line("   → Generate new private key → save as: {$serviceAccountPath}");
            $ok = false;
        } else {
            $serviceAccount = json_decode(File::get($serviceAccountPath), true);
            $saProjectId = $serviceAccount['project_id'] ?? null;
            $saEmail = $serviceAccount['client_email'] ?? null;
            $this->line("   Project ID in file: {$saProjectId}");
            $this->line("   Service account: {$saEmail}");

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
                    $this->verifyFcmSendPermission($token, $backendProjectId, $ok);
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

    /**
     * Probe FCM v1 with an invalid token: 403 = IAM issue, 400/404 = send permission OK.
     */
    protected function verifyFcmSendPermission(string $accessToken, string $projectId, bool &$ok): void
    {
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        $response = Http::withToken($accessToken)->post($url, [
            'message' => [
                'token' => 'permission-probe-invalid-token',
                'notification' => [
                    'title' => 'probe',
                    'body' => 'probe',
                ],
            ],
        ]);

        if ($response->status() === 403 && str_contains($response->body(), 'cloudmessaging.messages.create')) {
            $this->error('   ❌ FCM send permission denied (cloudmessaging.messages.create)');
            $this->line('   Grant "Firebase Cloud Messaging API Admin" to your service account in IAM:');
            $this->line("   https://console.cloud.google.com/iam-admin/iam?project={$projectId}");
            $ok = false;
            return;
        }

        if (in_array($response->status(), [400, 404], true)) {
            $this->line('   ✅ FCM v1 send permission verified');
            return;
        }

        $this->warn("   ⚠️ FCM probe returned HTTP {$response->status()} (check API is enabled)");
    }
}
