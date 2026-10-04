<?php

/**
 * One-off audit: check production story-editor for scripts + image_prompts JSON.
 * Usage: php scripts/audit-story-scripts-server.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$targets = [
    ['label' => 'پازل جادویی', 'match' => ['magical puzzle', 'magical_puzzle', 'پازل']],
    ['label' => 'خانواده پرنده', 'match' => ['bird family', 'bird_family', 'خانواده پرنده']],
    ['label' => 'نگهبانان سبز', 'match' => ['green guardians', 'green_guardians', 'نگهبانان سبز']],
    ['label' => 'سرزمین رویاها', 'match' => ['land of dreams', 'land_of_dreams', 'سرزمین رویا']],
    ['label' => 'باد و بادبادک', 'match' => ['wind and kite', 'wind_and_kite', 'باد و بادبادک']],
    ['label' => 'قله رویاها', 'match' => ['summit of dreams', 'summit_of_dreams', 'قله رویا']],
    ['label' => 'آشپزخانه کوچک', 'match' => ['little kitchen', 'little_kitchen', 'آشپزخانه کوچک']],
    ['label' => 'صابون قهرمان', 'match' => ['hero soap', 'hero_soap', 'صابون قهرمان']],
    ['label' => 'قلم موهای زنده', 'match' => ['living brushes', 'living_brushes', 'قلم مو']],
    ['label' => 'اسباب بازی های حسود', 'match' => ['jealous toys', 'jealous_toys', 'اسباب بازی']],
    ['label' => 'برداشت محصول مزرعه', 'parent' => 'gavi', 'match' => ['برداشت محصول', 'farm harvest', 'farm_harvest']],
    ['label' => 'شستشوی روزانه', 'parent' => 'gavi', 'match' => ['شستشوی روزانه', 'daily hygiene', 'daily_hygiene']],
    ['label' => 'ساخت خانه جدید', 'parent' => 'gavi', 'match' => ['ساخت خانه', 'building new house', 'building_new_house']],
    ['label' => 'روز بارانی غمگین', 'parent' => 'gavi', 'match' => ['روز بارانی', 'sad rainy', 'sad_rainy']],
    ['label' => 'روباه مزاحم مزرعه', 'parent' => 'gavi', 'match' => ['روباه مزاحم', 'troublesome fox', 'troublesome_fox']],
    ['label' => 'دوستان جدید در مزرعه', 'parent' => 'gavi', 'match' => ['دوستان جدید', 'new friends farm', 'new_friends_farm']],
    ['label' => 'چرخه آب در مزرعه', 'parent' => 'gavi', 'match' => ['چرخه آب', 'water cycle', 'water_cycle_farm']],
    ['label' => 'گنج پنهان مزرعه', 'parent' => 'gavi', 'match' => ['گنج پنهان', 'hidden farm treasure', 'hidden_farm_treasure']],
    ['label' => 'آزمایش با آب', 'parent' => 'gavi', 'match' => ['آزمایش با آب', 'water experiment', 'water_experiment']],
    ['label' => 'جادوی مزرعه', 'parent' => 'gavi', 'match' => ['جادوی مزرعه', 'farm magic', 'farm_magic']],
    ['label' => 'خرد ساکت', 'match' => ['quiet wisdom', 'quiet_wisdom', 'خرد ساکت']],
    ['label' => 'دل دلخور', 'match' => ['resentful heart', 'resentful_heart', 'دل دلخور']],
    ['label' => 'احساسات آزرده', 'match' => ['hurt feelings', 'hurt_feelings', 'احساسات آزرده']],
    ['label' => 'ماجراجویی کسل', 'match' => ['bored adventure', 'bored_adventure', 'ماجراجویی کسل']],
    ['label' => 'سوگند و قلب های کمک کننده', 'match' => ['sogand', 'helping hearts', 'helping_hearts', 'سوگند']],
    ['label' => 'جادوی صدا محمود', 'match' => ['mahmoud voice', 'mahmoud_voice', 'محمود']],
    ['label' => 'ماجرای برنامه نویسی ابوالفضل', 'match' => ['abolfazl', 'programming adventure', 'ابوالفضل']],
    ['label' => 'رویای ستاره شدن نجمه', 'match' => ['najmeh', 'celebrity dream', 'نجمه']],
    ['label' => 'سفر سلامتی امیر مسعود', 'match' => ['amir masoud', 'amir_masoud', 'امیر مسعود']],
    ['label' => 'جعبه اسرار آمیز', 'match' => ['mysterious box', 'mysterious_box', 'جعبه اسرار']],
    ['label' => 'دوستان آنلاین', 'match' => ['online friends', 'online_friends', 'دوستان آنلاین']],
    ['label' => 'شهر سبز', 'match' => ['green city', 'green_city', 'شهر سبز']],
    ['label' => 'جنگل سخن گو', 'match' => ['talking forest', 'talking_forest', 'جنگل سخن']],
    ['label' => 'نقشه گنج نهان', 'match' => ['world treasure map', 'world_treasure_map', 'treasure map', 'نقشه گنج نهان']],
    ['label' => 'مزرعه شاد', 'match' => ['happy farm', 'happy_farm', 'مزرعه شاد']],
    ['label' => 'ماه و خواب', 'match' => ['moon and sleep', 'moon_and_sleep', 'ماه و خواب']],
    ['label' => 'شب و ستاره ها', 'match' => ['night and stars', 'night_and_stars', 'شب و ستاره']],
    ['label' => 'کتابخانه زنده', 'match' => ['living library', 'living_library', 'کتابخانه زنده']],
    ['label' => 'روبات گمشده', 'match' => ['lost robot', 'lost_robot', 'روبات گمشده']],
    ['label' => 'باغ وحش هوشمند', 'match' => ['smart zoo', 'smart_zoo', 'باغ وحش']],
    ['label' => 'ابرهای خندان', 'match' => ['laughing clouds', 'laughing_clouds', 'ابرهای خندان']],
    ['label' => 'زیر آب ماجراجویانه', 'match' => ['underwater adventure', 'underwater_adventure', 'زیر آب']],
    ['label' => 'صحنه بزرگ', 'match' => ['big stage', 'big_stage', 'صحنه بزرگ']],
    ['label' => 'دوست تنها', 'match' => ['lonely friend', 'lonely_friend', 'دوست تنها']],
    ['label' => 'متاسف و بخشش', 'match' => ['sorry and forgiveness', 'sorry_and_forgiveness', 'متاسف']],
    ['label' => 'تیدالیک قورباغه تشنه', 'match' => ['tiddalik', 'تیدالیک']],
    ['label' => 'نخودو', 'match' => ['nakhodoo', 'نخودو']],
];

$baseUrl = rtrim((string) env('LOCAL_IMPORT_API_BASE_URL'), '/');
$token = (string) env('LOCAL_IMPORT_API_TOKEN');
if ($baseUrl === '' || $token === '') {
    fwrite(STDERR, "Missing LOCAL_IMPORT_API_BASE_URL or LOCAL_IMPORT_API_TOKEN\n");
    exit(1);
}

$headers = [
    'Authorization' => 'Bearer ' . $token,
    'Accept' => 'application/json',
];

$listResp = \Illuminate\Support\Facades\Http::withHeaders($headers)
    ->get($baseUrl . '/story-editor/stories');
if (!$listResp->successful()) {
    fwrite(STDERR, "Failed to list stories: HTTP {$listResp->status()}\n");
    exit(1);
}
$stories = $listResp->json('data') ?? [];

function norm(string $s): string
{
    $s = mb_strtolower($s, 'UTF-8');
    $s = str_replace(['‌', 'ي', 'ك'], [' ', 'ی', 'ک'], $s);
    return preg_replace('/\s+/u', ' ', trim($s)) ?? trim($s);
}

function matchesStory(array $story, array $needles): bool
{
    $hay = norm(implode(' ', array_filter([
        $story['id'] ?? '',
        $story['folder_name'] ?? '',
        $story['name_persian'] ?? '',
        $story['name_english'] ?? '',
    ])));
    foreach ($needles as $n) {
        $n = norm($n);
        if ($n !== '' && str_contains($hay, $n)) {
            return true;
        }
    }
    return false;
}

function matchesEpisode(array $episode, array $needles): bool
{
    $hay = norm(implode(' ', array_filter([
        $episode['id'] ?? '',
        $episode['title_persian'] ?? '',
        $episode['file_path'] ?? '',
    ])));
    foreach ($needles as $n) {
        $n = norm($n);
        if ($n !== '' && str_contains($hay, $n)) {
            return true;
        }
    }
    return false;
}

function findStory(array $stories, array $needles, ?string $parent = null): ?array
{
    if ($parent !== null) {
        foreach ($stories as $story) {
            if (matchesStory($story, [$parent, 'gavi', 'گاوی'])) {
                return $story;
            }
        }
        return null;
    }
    foreach ($stories as $story) {
        if (matchesStory($story, $needles)) {
            return $story;
        }
    }
    return null;
}

function fetchEpisodes(string $baseUrl, array $headers, string $storyId): array
{
    $resp = \Illuminate\Support\Facades\Http::withHeaders($headers)
        ->get($baseUrl . '/story-editor/stories/' . rawurlencode($storyId) . '/episodes');
    if (!$resp->successful()) {
        return [];
    }
    return $resp->json('data') ?? [];
}

function fetchPackage(string $baseUrl, array $headers, string $storyId): ?array
{
    $resp = \Illuminate\Support\Facades\Http::withHeaders($headers)
        ->get($baseUrl . '/story-editor/stories/' . rawurlencode($storyId) . '/package');
    if (!$resp->successful()) {
        return null;
    }
    return $resp->json('data');
}

function episodeHasPrompts(?array $package, string $episodeId): ?bool
{
    if ($package === null) {
        return null;
    }
    foreach ($package['episodes'] ?? [] as $ep) {
        if (($ep['id'] ?? '') === $episodeId) {
            $prompts = $ep['files']['image_prompts'] ?? null;
            return $prompts !== null;
        }
    }
    return false;
}

$results = [];
foreach ($targets as $target) {
    $parent = $target['parent'] ?? null;
    $story = findStory($stories, $target['match'], $parent);
    $row = [
        'label' => $target['label'],
        'story_found' => false,
        'story_slug' => null,
        'story_folder' => null,
        'episode_found' => false,
        'episode_slug' => null,
        'episode_title' => null,
        'script' => false,
        'prompts_json' => null,
        'scene_count' => null,
        'status' => 'NOT_ON_SERVER',
    ];

    if ($story === null) {
        $results[] = $row;
        continue;
    }

    $row['story_found'] = true;
    $row['story_slug'] = $story['id'] ?? null;
    $row['story_folder'] = $story['folder_name'] ?? null;

    $episodes = fetchEpisodes($baseUrl, $headers, (string) $row['story_slug']);
    $package = fetchPackage($baseUrl, $headers, (string) $row['story_slug']);

    $matchedEpisode = null;
    if ($parent !== null) {
        foreach ($episodes as $ep) {
            if (matchesEpisode($ep, $target['match'])) {
                $matchedEpisode = $ep;
                break;
            }
        }
    } elseif (count($episodes) === 1) {
        $matchedEpisode = $episodes[0];
    } else {
        foreach ($episodes as $ep) {
            if (matchesEpisode($ep, $target['match'])) {
                $matchedEpisode = $ep;
                break;
            }
        }
        if ($matchedEpisode === null && count($episodes) > 0) {
            $matchedEpisode = $episodes[0];
        }
    }

    if ($matchedEpisode === null) {
        $row['status'] = 'STORY_NO_SCRIPT_EPISODES';
        $row['episode_count_on_server'] = count($episodes);
        $results[] = $row;
        continue;
    }

    $row['episode_found'] = true;
    $row['episode_slug'] = $matchedEpisode['id'] ?? null;
    $row['episode_title'] = $matchedEpisode['title_persian'] ?? null;
    $row['script'] = true;
    $row['prompts_json'] = episodeHasPrompts($package, (string) $row['episode_slug']);

    if ($package !== null) {
        foreach ($package['episodes'] ?? [] as $epPkg) {
            if (($epPkg['id'] ?? '') === $row['episode_slug']) {
                $row['scene_count'] = $epPkg['scene_count'] ?? 0;
                break;
            }
        }
    }

    if ($row['prompts_json'] === true && ($row['scene_count'] ?? 0) > 0) {
        $row['status'] = 'OK';
    } elseif ($row['prompts_json'] === true) {
        $row['status'] = 'SCRIPT_OK_PROMPTS_EMPTY_SCENES';
    } elseif ($row['prompts_json'] === false) {
        $row['status'] = 'MISSING_PROMPTS_JSON';
    } else {
        $row['status'] = 'SCRIPT_OK_PROMPTS_UNKNOWN';
    }

    $results[] = $row;
}

echo json_encode(['audited_at' => date('c'), 'api' => $baseUrl, 'results' => $results], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
