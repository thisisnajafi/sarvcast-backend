<?php

/**
 * Fetch published stories (id, title, subtitle, description) from production admin API.
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$base = rtrim((string) env('LOCAL_IMPORT_API_BASE_URL'), '/');
$token = (string) env('LOCAL_IMPORT_API_TOKEN');
if ($base === '' || $token === '') {
    fwrite(STDERR, "Missing LOCAL_IMPORT_API_BASE_URL or LOCAL_IMPORT_API_TOKEN\n");
    exit(1);
}

$headers = [
    'Authorization' => 'Bearer '.$token,
    'Accept' => 'application/json',
];

$all = [];
$page = 1;
$lastPage = 1;

do {
    $resp = \Illuminate\Support\Facades\Http::withHeaders($headers)
        ->get($base.'/stories', [
            'status' => 'published',
            'per_page' => 100,
            'page' => $page,
        ]);

    if (! $resp->successful()) {
        fwrite(STDERR, "Failed page {$page}: HTTP {$resp->status()} ".$resp->body()."\n");
        exit(1);
    }

    $json = $resp->json();
    $rows = $json['data'] ?? [];
    if (! is_array($rows)) {
        $rows = [];
    }

    foreach ($rows as $row) {
        $all[] = [
            'id' => $row['id'] ?? null,
            'title' => $row['title'] ?? null,
            'subtitle' => $row['subtitle'] ?? null,
            'description' => $row['description'] ?? null,
            'status' => $row['status'] ?? null,
            'age_rating' => $row['age_rating'] ?? null,
            'category_id' => $row['category_id'] ?? null,
            'category' => $row['category']['name'] ?? ($row['category']['name_fa'] ?? null),
            'episode_count' => is_array($row['episodes'] ?? null) ? count($row['episodes']) : ($row['episodes_count'] ?? null),
            'slug' => $row['slug'] ?? null,
        ];
    }

    $meta = $json['meta'] ?? ($json['pagination'] ?? []);
    $lastPage = (int) ($meta['last_page'] ?? $json['last_page'] ?? 1);
    $page++;
} while ($page <= $lastPage);

$outPath = dirname(__DIR__).'/../manji-stories/reports/published-stories-meta.json';
@mkdir(dirname($outPath), 0777, true);
file_put_contents($outPath, json_encode([
    'fetched_at' => date('c'),
    'count' => count($all),
    'stories' => $all,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo "Fetched ".count($all)." published stories -> {$outPath}\n";
