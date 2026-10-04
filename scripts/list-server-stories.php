<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$base = rtrim((string) env('LOCAL_IMPORT_API_BASE_URL'), '/');
$token = (string) env('LOCAL_IMPORT_API_TOKEN');
$h = ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];
$r = \Illuminate\Support\Facades\Http::withHeaders($h)->get($base.'/story-editor/stories');
$stories = $r->json('data') ?? [];
foreach ($stories as $s) {
    echo ($s['folder_name'] ?? '').' | '.($s['id'] ?? '').' | eps='.($s['episode_count'] ?? 0)."\n";
}
