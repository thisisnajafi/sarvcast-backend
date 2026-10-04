<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$labels = [
    'پازل جادویی', 'خانواده پرنده', 'نگهبانان سبز', 'سرزمین رویاها', 'باد و بادبادک', 'قله رویاها',
    'آشپزخانه کوچک', 'صابون قهرمان', 'قلم موهای زنده', 'اسباب بازی های حسود',
    'برداشت محصول مزرعه', 'شستشوی روزانه', 'ساخت خانه جدید', 'روز بارانی غمگین', 'روباه مزاحم مزرعه',
    'دوستان جدید در مزرعه', 'چرخه آب در مزرعه', 'گنج پنهان مزرعه', 'آزمایش با آب', 'جادوی مزرعه',
    'خرد ساکت', 'دل دلخور', 'احساسات آزرده', 'ماجراجویی کسل', 'سوگند و قلب های کمک کننده',
    'جادوی صدا محمود', 'ماجرای برنامه نویسی ابوالفضل', 'رویای ستاره شدن نجمه', 'سفر سلامتی امیر مسعود',
    'جعبه اسرار آمیز', 'دوستان آنلاین', 'شهر سبز', 'جنگل سخن گو', 'نقشه گنج نهان', 'مزرعه شاد',
    'ماه و خواب', 'شب و ستاره ها', 'کتابخانه زنده', 'روبات گمشده', 'باغ وحش هوشمند', 'ابرهای خندان',
    'زیر آب ماجراجویانه', 'صحنه بزرگ', 'دوست تنها', 'متاسف و بخشش', 'تیدالیک قورباغه تشنه', 'نخودو',
];

$gaviEps = [
    'برداشت محصول مزرعه', 'شستشوی روزانه', 'ساخت خانه جدید', 'روز بارانی غمگین', 'روباه مزاحم مزرعه',
    'دوستان جدید در مزرعه', 'چرخه آب در مزرعه', 'گنج پنهان مزرعه', 'آزمایش با آب', 'جادوی مزرعه',
];

$base = rtrim((string) env('LOCAL_IMPORT_API_BASE_URL'), '/');
$token = (string) env('LOCAL_IMPORT_API_TOKEN');
$h = ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];

$stories = \Illuminate\Support\Facades\Http::withHeaders($h)->get($base.'/story-editor/stories')->json('data') ?? [];

function norm(string $s): string {
    $s = mb_strtolower($s, 'UTF-8');
    $s = str_replace(['‌', 'ي', 'ك', '‌'], [' ', 'ی', 'ک', ' '], $s);
    return preg_replace('/\s+/u', ' ', trim($s)) ?? trim($s);
}

function contains(string $hay, string $needle): bool {
    return str_contains(norm($hay), norm($needle));
}

$gavi = null;
foreach ($stories as $s) {
    if (contains($s['folder_name'] ?? '', 'گاو و گنجشک') || contains($s['id'] ?? '', 'gao-o-gngshk')) {
        $gavi = $s;
        break;
    }
}

$gaviEpisodes = [];
$gaviPackage = null;
if ($gavi) {
    $slug = $gavi['id'];
    $gaviEpisodes = \Illuminate\Support\Facades\Http::withHeaders($h)
        ->get($base.'/story-editor/stories/'.rawurlencode($slug).'/episodes')->json('data') ?? [];
    $gaviPackage = \Illuminate\Support\Facades\Http::withHeaders($h)
        ->get($base.'/story-editor/stories/'.rawurlencode($slug).'/package')->json('data');
}

$out = [];
foreach ($labels as $label) {
    $row = ['label' => $label, 'status' => 'NOT_ON_SERVER', 'story_folder' => null, 'story_slug' => null, 'episode' => null, 'script' => false, 'prompts' => false, 'scenes' => 0];

    if (in_array($label, $gaviEps, true) && $gavi) {
        $row['story_folder'] = $gavi['folder_name'];
        $row['story_slug'] = $gavi['id'];
        $ep = null;
        foreach ($gaviEpisodes as $e) {
            if (contains($e['title_persian'] ?? '', $label) || contains($e['id'] ?? '', str_replace(' ', '_', norm($label)))) {
                $ep = $e;
                break;
            }
        }
        if ($ep) {
            $row['episode'] = $ep['title_persian'] ?: $ep['id'];
            $row['script'] = true;
            foreach ($gaviPackage['episodes'] ?? [] as $p) {
                if (($p['id'] ?? '') === ($ep['id'] ?? '')) {
                    $row['prompts'] = !empty($p['files']['image_prompts']);
                    $row['scenes'] = (int) ($p['scene_count'] ?? 0);
                    break;
                }
            }
            $row['status'] = ($row['script'] && $row['prompts'] && $row['scenes'] > 0) ? 'OK' : 'PARTIAL';
        } else {
            $row['status'] = 'EPISODE_MISSING';
        }
        $out[] = $row;
        continue;
    }

    $story = null;
    foreach ($stories as $s) {
        $fn = $s['folder_name'] ?? '';
        $np = $s['name_persian'] ?? '';
        $ne = $s['name_english'] ?? '';
        if (contains($fn, $label) || contains($np, $label) || contains($ne, $label)) {
            $story = $s;
            break;
        }
        // partial: first 8 chars of label
        $short = mb_substr(norm($label), 0, 8, 'UTF-8');
        if (mb_strlen(norm($label), 'UTF-8') >= 4 && (contains($fn, $short) || contains($np, $short))) {
            $story = $s;
            break;
        }
    }

    if (!$story) {
        $out[] = $row;
        continue;
    }

    $row['story_folder'] = $story['folder_name'];
    $row['story_slug'] = $story['id'];
    $slug = $story['id'];
    $episodes = \Illuminate\Support\Facades\Http::withHeaders($h)
        ->get($base.'/story-editor/stories/'.rawurlencode($slug).'/episodes')->json('data') ?? [];
    $package = \Illuminate\Support\Facades\Http::withHeaders($h)
        ->get($base.'/story-editor/stories/'.rawurlencode($slug).'/package')->json('data');

    if (count($episodes) === 0) {
        $row['status'] = 'NO_SCRIPT_ON_SERVER';
        $out[] = $row;
        continue;
    }

    $ep = $episodes[0];
    if (count($episodes) > 1) {
        foreach ($episodes as $e) {
            if (contains($e['title_persian'] ?? '', $label)) {
                $ep = $e;
                break;
            }
        }
    }
    $row['episode'] = $ep['title_persian'] ?: $ep['id'];
    $row['script'] = true;
    foreach ($package['episodes'] ?? [] as $p) {
        if (($p['id'] ?? '') === ($ep['id'] ?? '')) {
            $row['prompts'] = !empty($p['files']['image_prompts']);
            $row['scenes'] = (int) ($p['scene_count'] ?? 0);
            break;
        }
    }
    $row['status'] = ($row['script'] && $row['prompts'] && $row['scenes'] > 0) ? 'OK' : 'PARTIAL';
    $out[] = $row;
}

file_put_contents(dirname(__DIR__).'/../manji-stories/reports/audit-no-scripts-server-v2.json', json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
foreach ($out as $r) {
    echo sprintf("%-28s | %-22s | %s\n", $r['label'], $r['status'], $r['story_folder'] ?? '-');
}
