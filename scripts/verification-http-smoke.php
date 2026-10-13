<?php

/**
 * Local verification smoke: sqlite DB + SMS log driver only.
 * Does not call Melipayamak, Zarinpal live, or FCM.
 */

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

putenv('APP_ENV=testing');
$_ENV['APP_ENV'] = 'testing';
$_SERVER['APP_ENV'] = 'testing';
$key = 'base64:MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY=';
putenv('APP_KEY='.$key);
$_ENV['APP_KEY'] = $key;
$_SERVER['APP_KEY'] = $key;
putenv('DB_CONNECTION=sqlite');
$_ENV['DB_CONNECTION'] = 'sqlite';
$_SERVER['DB_CONNECTION'] = 'sqlite';
$db = str_replace('\\', '/', dirname(__DIR__).'/database/verification.sqlite');
putenv('DB_DATABASE='.$db);
$_ENV['DB_DATABASE'] = $db;
$_SERVER['DB_DATABASE'] = $db;
putenv('SMS_DRIVER=log');
$_ENV['SMS_DRIVER'] = 'log';
$_SERVER['SMS_DRIVER'] = 'log';
putenv('MELIPAYAMAK_USERNAME=');
putenv('MELIPAYAMAK_PASSWORD=');
putenv('PUSH_NOTIFICATIONS_ENABLED=false');

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();
config(['app.key' => $key, 'sms.driver' => 'log']);

$user = User::query()->where('role', 'super_admin')->first()
    ?? User::query()->where('phone_number', '09136708883')->first();

if (! $user) {
    fwrite(STDERR, "NO_USER\n");
    exit(1);
}

if ($user->role !== 'super_admin') {
    $user->forceFill(['role' => 'super_admin'])->save();
}

$token = $user->createToken('verify-task-13-http')->plainTextToken;

$paths = [
    '/api/admin/v1/dashboard/stats',
    '/api/admin/users',
    '/api/admin/schools',
    '/api/admin/teachers',
    '/api/admin/stories',
    '/api/admin/subscription-plans',
    '/api/admin/payments',
    '/api/admin/affiliate',
    '/api/admin/referrals',
    '/api/admin/commission-payments',
    '/api/admin/influencers',
    '/api/admin/corporate',
    '/api/admin/notifications',
    '/api/admin/sms-templates',
    '/api/admin/app-settings',
    '/api/admin/audio-management',
    '/api/admin/file-upload',
    '/api/admin/v1/school-managers',
    '/api/admin/roles',
    '/api/admin/categories',
    '/api/admin/coupons',
    '/api/admin/media',
    '/api/admin/activity-logs',
    '/api/admin/flavor-analytics',
    '/api/admin/specialized-dashboards/sales',
];

$http = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$ok = 0;
$fail = [];

foreach ($paths as $path) {
    $request = Request::create($path, 'GET', [], [], [], [
        'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $response = $http->handle($request);
    $status = $response->getStatusCode();
    $http->terminate($request, $response);
    if ($status >= 400) {
        $fail[] = "$path -> $status";
    } else {
        $ok++;
        echo "OK $status $path\n";
    }
}

echo "API_OK=$ok FAIL=".count($fail)."\n";
foreach ($fail as $line) {
    echo "FAIL $line\n";
}

exit(count($fail) === 0 ? 0 : 1);
