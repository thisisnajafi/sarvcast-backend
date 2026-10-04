<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$r = \Illuminate\Support\Facades\Http::get('https://my.manjiapp.ir/api/v1/stories', [
    'per_page' => 100,
]);
echo 'status='.$r->status()."\n";
$j = $r->json();
$data = $j['data'] ?? [];
echo 'count='.(is_array($data) ? count($data) : 0)."\n";
foreach ((array) $data as $s) {
    echo ($s['id'] ?? '?').' | '.($s['title'] ?? '').' | sub='.(($s['subtitle'] ?? '') !== '' ? 'Y' : 'N').' | desc_len='.mb_strlen((string) ($s['description'] ?? ''))."\n";
}
