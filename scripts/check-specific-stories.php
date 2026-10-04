<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$base = rtrim((string) env('LOCAL_IMPORT_API_BASE_URL'), '/');
$token = (string) env('LOCAL_IMPORT_API_TOKEN');
$h = ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];
foreach (['115-backyard-treasure-map', '118-walking-library', '37-nakhodoo', '10-ashpzkhanh-kochk', '11-abrhay-khndan', '21-bagh-ohsh-hoshmnd'] as $slug) {
    echo "=== $slug ===\n";
    $eps = \Illuminate\Support\Facades\Http::withHeaders($h)->get($base.'/story-editor/stories/'.rawurlencode($slug).'/episodes')->json('data') ?? [];
    echo 'episodes_with_script: '.count($eps)."\n";
    foreach ($eps as $e) {
        echo '  - '.($e['episode_number'] ?? '?').' | '.($e['title_persian'] ?? '').' | '.($e['id'] ?? '')."\n";
    }
    $pkg = \Illuminate\Support\Facades\Http::withHeaders($h)->get($base.'/story-editor/stories/'.rawurlencode($slug).'/package')->json('data');
    echo 'package_episodes: '.count($pkg['episodes'] ?? [])."\n";
    echo "\n";
}
