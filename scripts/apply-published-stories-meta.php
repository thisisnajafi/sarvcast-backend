<?php

/**
 * Apply subtitle/description edits from reports/published-stories-meta-edits.json
 * via PUT /api/admin/stories/{id}
 *
 * Usage:
 *   php scripts/apply-published-stories-meta.php
 *   php scripts/apply-published-stories-meta.php --dry-run
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$dryRun = in_array('--dry-run', $argv ?? [], true);

$base = rtrim((string) env('LOCAL_IMPORT_API_BASE_URL'), '/');
$token = (string) env('LOCAL_IMPORT_API_TOKEN');
if ($base === '' || $token === '') {
    fwrite(STDERR, "Missing LOCAL_IMPORT_API_BASE_URL or LOCAL_IMPORT_API_TOKEN\n");
    exit(1);
}

$editsPath = dirname(__DIR__).'/../manji-stories/reports/published-stories-meta-edits.json';
$payload = json_decode((string) file_get_contents($editsPath), true);
$edits = $payload['edits'] ?? [];
if ($edits === []) {
    fwrite(STDERR, "No edits found in {$editsPath}\n");
    exit(1);
}

$headers = [
    'Authorization' => 'Bearer '.$token,
    'Accept' => 'application/json',
    'Content-Type' => 'application/json',
];

$results = [];
$failed = 0;

foreach ($edits as $edit) {
    $id = (int) ($edit['id'] ?? 0);
    $body = [
        'subtitle' => $edit['subtitle'] ?? null,
        'description' => $edit['description'] ?? null,
    ];

    echo ($dryRun ? '[DRY] ' : '')."Updating story {$id} ({$edit['title']})...\n";

    if ($dryRun) {
        $results[] = [
            'id' => $id,
            'title' => $edit['title'],
            'status' => 'dry_run',
            'payload' => $body,
        ];
        continue;
    }

    $resp = \Illuminate\Support\Facades\Http::withHeaders($headers)
        ->put($base.'/stories/'.$id, $body);

    $json = $resp->json();
    $ok = $resp->successful() && (($json['success'] ?? true) !== false);
    $data = $json['data'] ?? $json;

    $savedSubtitle = is_array($data) ? ($data['subtitle'] ?? null) : null;
    $savedDescription = is_array($data) ? ($data['description'] ?? null) : null;

    $subtitleMatched = ($savedSubtitle === ($body['subtitle'] ?? null));
    $descriptionMatched = ($savedDescription === ($body['description'] ?? null));

    if (! $ok || ! $descriptionMatched) {
        $failed++;
        $status = 'failed';
        echo "  FAILED HTTP {$resp->status()}\n";
    } elseif (! $subtitleMatched) {
        $failed++;
        $status = 'partial_description_only';
        echo "  PARTIAL: description saved, subtitle NOT accepted by API yet\n";
    } else {
        $status = 'ok';
        echo "  OK\n";
    }

    $results[] = [
        'id' => $id,
        'title' => $edit['title'],
        'status' => $status,
        'http' => $resp->status(),
        'subtitle_matched' => $subtitleMatched,
        'description_matched' => $descriptionMatched,
        'saved_subtitle' => $savedSubtitle,
        'error' => $ok ? null : ($json['message'] ?? $resp->body()),
    ];
}

$out = [
    'applied_at' => date('c'),
    'dry_run' => $dryRun,
    'failed_count' => $failed,
    'results' => $results,
];

$outPath = dirname(__DIR__).'/../manji-stories/reports/published-stories-meta-apply.json';
file_put_contents($outPath, json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "Summary written to {$outPath}\n";
exit($failed > 0 ? 2 : 0);
