<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

// Force the verification env file (never production .env Melipayamak).
putenv('APP_ENV=testing');
$_ENV['APP_ENV'] = 'testing';
$_SERVER['APP_ENV'] = 'testing';
putenv('APP_KEY=base64:MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY=');
$_ENV['APP_KEY'] = 'base64:MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY=';
$_SERVER['APP_KEY'] = getenv('APP_KEY');
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

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$configKey = config('app.key');
if ($configKey !== 'base64:MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY=') {
    config(['app.key' => 'base64:MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY=']);
}

$user = User::query()->where('role', 'super_admin')->first()
    ?? User::query()->where('phone_number', '09136708883')->first()
    ?? User::query()->first();

if (! $user) {
    fwrite(STDERR, "NO_USER\n");
    exit(1);
}

if ($user->role !== 'super_admin') {
    $user->forceFill(['role' => 'super_admin'])->save();
}

echo $user->createToken('verify-task-13')->plainTextToken;
