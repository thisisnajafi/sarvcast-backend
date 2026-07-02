<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $members = \App\Models\TeamMember::query()
        ->visible()
        ->ordered()
        ->whereHas('user')
        ->with('user:id,first_name,last_name,phone_number,profile_image_url,bio')
        ->get()
        ->map(fn (\App\Models\TeamMember $member) => $member->toPublicArray())
        ->filter(fn (array $row) => ! empty($row))
        ->values();

    echo json_encode(['success' => true, 'count' => $members->count(), 'data' => $members], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ], JSON_PRETTY_PRINT);
}
