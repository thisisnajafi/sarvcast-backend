<?php

namespace App\Services;

use App\Models\ProfileView;
use App\Models\Story;
use App\Models\User;
use App\Models\UserResume;
use Illuminate\Validation\ValidationException;

class UserResumeService
{
    public const ROLE_LABELS = [
        User::ROLE_VOICE_ACTOR => 'صداپیشه',
        User::ROLE_HEAD_WRITER => 'سرپرست نویسندگان',
        User::ROLE_SUPER_ADMIN => 'مدیر',
        User::ROLE_ADMIN => 'مدیر',
        User::ROLE_WRITER => 'نویسنده',
    ];

    /**
     * @return list<string>
     */
    public static function eligibleRoles(): array
    {
        return [
            User::ROLE_VOICE_ACTOR,
            User::ROLE_HEAD_WRITER,
            User::ROLE_WRITER,
            User::ROLE_ADMIN,
            User::ROLE_SUPER_ADMIN,
        ];
    }

    /**
     * Staff who appear in the public directory only when an admin pins them.
     *
     * @return list<string>
     */
    public static function directoryOptInRoles(): array
    {
        return [
            User::ROLE_HEAD_WRITER,
            User::ROLE_WRITER,
            User::ROLE_ADMIN,
            User::ROLE_SUPER_ADMIN,
        ];
    }

    public function canOwnResume(User $user): bool
    {
        return in_array($user->role, self::eligibleRoles(), true);
    }

    public function appearsInTalentDirectory(User $user): bool
    {
        if ($user->status !== User::STATUS_ACTIVE) {
            return false;
        }

        if ($user->role === User::ROLE_VOICE_ACTOR) {
            return true;
        }

        $resume = $user->resume;
        if (
            in_array($user->role, self::directoryOptInRoles(), true)
            && $resume
            && $resume->is_public
            && $resume->show_in_talent_directory
        ) {
            return true;
        }

        return false;
    }

    public function firstOrCreateDraft(User $user, ?int $editorId = null): UserResume
    {
        $resume = $user->resume;
        if ($resume) {
            return $resume;
        }

        return UserResume::query()->create([
            'user_id' => $user->id,
            'specialties' => [],
            'experience' => [],
            'education' => [],
            'awards' => [],
            'languages' => [],
            'social_links' => $this->emptySocialLinks(),
            'is_public' => false,
            'show_in_talent_directory' => false,
            'updated_by_user_id' => $editorId ?? $user->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function validateAndNormalize(array $input, bool $allowDirectoryFlag): array
    {
        $headline = $this->nullableString($input['headline'] ?? null, 120);
        $about = $this->nullableString($input['about'] ?? null, 4000);
        $years = $input['years_of_experience'] ?? null;
        if ($years === '' || $years === null) {
            $years = null;
        } else {
            $years = (int) $years;
            if ($years < 0 || $years > 70) {
                throw ValidationException::withMessages([
                    'years_of_experience' => 'سابقه باید بین ۰ تا ۷۰ سال باشد.',
                ]);
            }
        }

        $specialties = $this->normalizeStringList($input['specialties'] ?? [], 12, 40, 'specialties');
        $experience = $this->normalizeExperience($input['experience'] ?? []);
        $education = $this->normalizeEducation($input['education'] ?? []);
        $awards = $this->normalizeAwards($input['awards'] ?? []);
        $languages = $this->normalizeLanguages($input['languages'] ?? []);
        $demoUrl = $this->normalizeDemoUrl($input['demo_url'] ?? null);
        $social = $this->normalizeSocialLinks($input['social_links'] ?? []);

        $isPublic = array_key_exists('is_public', $input)
            ? filter_var($input['is_public'], FILTER_VALIDATE_BOOLEAN)
            : false;

        $payload = [
            'headline' => $headline,
            'years_of_experience' => $years,
            'about' => $about,
            'specialties' => $specialties,
            'experience' => $experience,
            'education' => $education,
            'awards' => $awards,
            'languages' => $languages,
            'demo_url' => $demoUrl,
            'social_links' => $social,
            'is_public' => $isPublic,
        ];

        if ($allowDirectoryFlag && array_key_exists('show_in_talent_directory', $input)) {
            $payload['show_in_talent_directory'] = filter_var(
                $input['show_in_talent_directory'],
                FILTER_VALIDATE_BOOLEAN
            );
        }

        return $payload;
    }

    public function applyUpdate(UserResume $resume, array $normalized, int $editorId): UserResume
    {
        if ($normalized['is_public'] && $resume->published_at === null) {
            $normalized['published_at'] = now();
        }

        $normalized['updated_by_user_id'] = $editorId;
        $resume->fill($normalized);
        $resume->save();

        if (array_key_exists('about', $normalized)) {
            $owner = $resume->user ?? User::query()->find($resume->user_id);
            if ($owner) {
                $about = $normalized['about'];
                $owner->bio = $about === null ? null : mb_substr($about, 0, 500);
                $owner->save();
            }
        }

        return $resume->fresh() ?? $resume;
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(UserResume $resume): array
    {
        return [
            'headline' => $resume->headline,
            'years_of_experience' => $resume->years_of_experience,
            'about' => $resume->about,
            'specialties' => $resume->specialties ?? [],
            'experience' => $resume->experience ?? [],
            'education' => $resume->education ?? [],
            'awards' => $resume->awards ?? [],
            'languages' => $resume->languages ?? [],
            'demo_url' => $resume->demo_url,
            'social_links' => array_merge($this->emptySocialLinks(), $resume->social_links ?? []),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toOwnerArray(UserResume $resume): array
    {
        return array_merge($this->toPublicArray($resume), [
            'id' => $resume->id,
            'user_id' => $resume->user_id,
            'is_public' => (bool) $resume->is_public,
            'show_in_talent_directory' => (bool) $resume->show_in_talent_directory,
            'published_at' => $resume->published_at?->toIso8601String(),
            'updated_at' => $resume->updated_at?->toIso8601String(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(UserResume $resume): array
    {
        return array_merge($this->toOwnerArray($resume), [
            'updated_by_user_id' => $resume->updated_by_user_id,
        ]);
    }

    /**
     * Safe public user fields. Never includes phone.
     *
     * @return array<string, mixed>
     */
    public function publicUserFields(User $user, bool $includeResume): array
    {
        $headline = null;
        $years = null;
        $visibleResume = $includeResume ? $user->resume : null;
        if ($visibleResume) {
            $headline = $visibleResume->headline;
            $years = $visibleResume->years_of_experience;
        }

        $payload = [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'profile_image_url' => $user->profile_image_url,
            'background_photo_url' => $user->background_photo_url,
            'bio' => $user->bio,
            'role' => $user->role,
            'role_label' => self::ROLE_LABELS[$user->role] ?? 'عضو تیم مانجی',
            'headline' => $headline,
            'years_of_experience' => $years,
        ];

        $payload['resume'] = $visibleResume
            ? $this->toPublicArray($visibleResume)
            : null;

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function listingItem(User $user): array
    {
        $resume = $user->resume && $user->resume->is_public ? $user->resume : null;
        $specialties = array_slice($resume?->specialties ?? [], 0, 3);
        $bio = $user->bio ? mb_substr(trim($user->bio), 0, 160) : null;

        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'profile_image_url' => $user->profile_image_url,
            'background_photo_url' => $user->background_photo_url,
            'headline' => $resume?->headline,
            'years_of_experience' => $resume?->years_of_experience,
            'bio' => $bio,
            'specialties' => $specialties,
            'role' => $user->role,
            'role_label' => self::ROLE_LABELS[$user->role] ?? 'عضو تیم مانجی',
            'works_count' => $this->publishedWorksCount($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function publishedWorksPayload(User $user): array
    {
        $storiesAsAuthor = Story::whereAuthor($user->id)
            ->published()
            ->with(['category', 'characters.voiceActor'])
            ->get();

        $storiesAsNarrator = Story::whereNarrator($user->id)
            ->published()
            ->with(['category', 'author', 'characters.voiceActor'])
            ->get();

        $storiesAsVoiceActor = Story::whereVoiceActor($user->id)
            ->published()
            ->with(['category', 'author', 'narrator', 'characters.voiceActor'])
            ->get();

        $viewCount = ProfileView::where('viewed_user_id', $user->id)->count();

        return [
            'statistics' => [
                'author_count' => $storiesAsAuthor->count(),
                'narrator_count' => $storiesAsNarrator->count(),
                'voice_actor_count' => $storiesAsVoiceActor->count(),
                'view_count' => $viewCount,
            ],
            'stories_as_author' => $storiesAsAuthor,
            'stories_as_narrator' => $storiesAsNarrator,
            'stories_as_voice_actor' => $storiesAsVoiceActor,
        ];
    }

    public function publishedWorksCount(User $user): int
    {
        $author = Story::whereAuthor($user->id)->published()->pluck('id');
        $narrator = Story::whereNarrator($user->id)->published()->pluck('id');
        $voice = Story::whereVoiceActor($user->id)->published()->pluck('id');

        return $author->merge($narrator)->merge($voice)->unique()->count();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<User>
     */
    public function talentDirectoryQuery()
    {
        return User::query()
            ->where('status', User::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->where('role', User::ROLE_VOICE_ACTOR)
                    ->orWhere(function ($q2) {
                        $q2->whereIn('role', self::directoryOptInRoles())
                            ->whereHas('resume', function ($r) {
                                $r->where('is_public', true)
                                    ->where('show_in_talent_directory', true);
                            });
                    });
            })
            ->with('resume');
    }

    /**
     * Public listing cards that have a stored profile photo (not a placeholder).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<User>  $query
     */
    public function constrainToProfilePhoto($query): void
    {
        $query->whereNotNull('profile_image_url')
            ->where('profile_image_url', '!=', '');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function emptySocialLinks(): array
    {
        return [
            'instagram' => null,
            'website' => null,
            'aparat' => null,
        ];
    }

    private function nullableString(mixed $value, int $max): ?string
    {
        if ($value === null) {
            return null;
        }
        $text = trim(strip_tags((string) $value));
        if ($text === '') {
            return null;
        }
        if (mb_strlen($text) > $max) {
            throw ValidationException::withMessages([
                'text' => "طول متن نمی‌تواند بیش از {$max} کاراکتر باشد.",
            ]);
        }

        return $text;
    }

    /**
     * @param  mixed  $items
     * @return list<string>
     */
    private function normalizeStringList(mixed $items, int $maxItems, int $maxLen, string $field): array
    {
        if (! is_array($items)) {
            return [];
        }
        if (count($items) > $maxItems) {
            throw ValidationException::withMessages([
                $field => "حداکثر {$maxItems} مورد مجاز است.",
            ]);
        }

        $out = [];
        foreach ($items as $item) {
            $text = trim(strip_tags((string) $item));
            if ($text === '') {
                continue;
            }
            if (mb_strlen($text) > $maxLen) {
                throw ValidationException::withMessages([
                    $field => "هر مورد حداکثر {$maxLen} کاراکتر است.",
                ]);
            }
            $out[] = $text;
        }

        return array_values(array_unique($out));
    }

    /**
     * @param  mixed  $items
     * @return list<array<string, mixed>>
     */
    private function normalizeExperience(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }
        if (count($items) > 20) {
            throw ValidationException::withMessages(['experience' => 'حداکثر ۲۰ سابقه کاری مجاز است.']);
        }

        $rows = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $title = $this->nullableString($item['title'] ?? null, 120);
            if ($title === null) {
                continue;
            }
            $rows[] = [
                'title' => $title,
                'organization' => $this->nullableString($item['organization'] ?? null, 120),
                'start_year' => $this->nullableYear($item['start_year'] ?? null),
                'end_year' => $this->nullableYear($item['end_year'] ?? null),
                'is_current' => filter_var($item['is_current'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'description' => $this->nullableString($item['description'] ?? null, 500),
            ];
        }

        return $rows;
    }

    /**
     * @param  mixed  $items
     * @return list<array<string, mixed>>
     */
    private function normalizeEducation(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }
        if (count($items) > 10) {
            throw ValidationException::withMessages(['education' => 'حداکثر ۱۰ مورد تحصیلات مجاز است.']);
        }

        $rows = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $school = $this->nullableString($item['school'] ?? null, 160);
            if ($school === null) {
                continue;
            }
            $rows[] = [
                'school' => $school,
                'degree' => $this->nullableString($item['degree'] ?? null, 120),
                'year' => $this->nullableYear($item['year'] ?? null),
                'description' => $this->nullableString($item['description'] ?? null, 500),
            ];
        }

        return $rows;
    }

    /**
     * @param  mixed  $items
     * @return list<array<string, mixed>>
     */
    private function normalizeAwards(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }
        if (count($items) > 20) {
            throw ValidationException::withMessages(['awards' => 'حداکثر ۲۰ افتخار مجاز است.']);
        }

        $rows = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $title = $this->nullableString($item['title'] ?? null, 160);
            if ($title === null) {
                continue;
            }
            $rows[] = [
                'title' => $title,
                'year' => $this->nullableYear($item['year'] ?? null),
                'description' => $this->nullableString($item['description'] ?? null, 500),
            ];
        }

        return $rows;
    }

    /**
     * @param  mixed  $items
     * @return list<array<string, mixed>>
     */
    private function normalizeLanguages(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }
        if (count($items) > 8) {
            throw ValidationException::withMessages(['languages' => 'حداکثر ۸ زبان مجاز است.']);
        }

        $rows = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $name = $this->nullableString($item['name'] ?? null, 40);
            if ($name === null) {
                continue;
            }
            $rows[] = [
                'name' => $name,
                'level' => $this->nullableString($item['level'] ?? null, 40),
            ];
        }

        return $rows;
    }

    private function nullableYear(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $year = (int) $value;
        if ($year < 1950 || $year > 2035) {
            throw ValidationException::withMessages(['year' => 'سال نامعتبر است.']);
        }

        return $year;
    }

    private function normalizeDemoUrl(mixed $value): ?string
    {
        $url = $this->nullableString($value, 500);
        if ($url === null) {
            return null;
        }
        if (! $this->isHttpsUrl($url) || ! $this->hostAllowed($url, ['aparat.com', 'youtube.com', 'youtu.be'])) {
            throw ValidationException::withMessages([
                'demo_url' => 'لینک نمونه صدا باید آپارات یا یوتیوب باشد.',
            ]);
        }

        return $url;
    }

    /**
     * @param  mixed  $links
     * @return array<string, string|null>
     */
    private function normalizeSocialLinks(mixed $links): array
    {
        $links = is_array($links) ? $links : [];
        $out = $this->emptySocialLinks();

        foreach (['instagram', 'website', 'aparat'] as $key) {
            if (! array_key_exists($key, $links)) {
                continue;
            }
            $url = $this->nullableString($links[$key], 500);
            if ($url === null) {
                $out[$key] = null;
                continue;
            }
            if (! $this->isHttpsUrl($url)) {
                throw ValidationException::withMessages([$key => 'لینک نامعتبر است.']);
            }
            $hosts = match ($key) {
                'instagram' => ['instagram.com'],
                'aparat' => ['aparat.com'],
                default => null,
            };
            if ($hosts !== null && ! $this->hostAllowed($url, $hosts)) {
                throw ValidationException::withMessages([$key => 'دامنه این لینک مجاز نیست.']);
            }
            $out[$key] = $url;
        }

        return $out;
    }

    private function isHttpsUrl(string $url): bool
    {
        if (preg_match('/^\s*(javascript|data):/i', $url)) {
            return false;
        }

        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }

        return in_array(strtolower($parts['scheme']), ['http', 'https'], true);
    }

    /**
     * @param  list<string>  $allowed
     */
    private function hostAllowed(string $url, array $allowed): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = preg_replace('/^www\./', '', $host) ?: $host;
        foreach ($allowed as $needle) {
            if ($host === $needle || str_ends_with($host, '.'.$needle)) {
                return true;
            }
        }

        return false;
    }
}
