<?php

namespace App\Services;

use App\DataTransferObjects\ActivityLogEntry;
use App\Jobs\LogActivityJob;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ActivityLogService
{
    public function record(ActivityLogEntry $entry): void
    {
        $payload = $this->sanitizeEntry($entry);

        if (config('activity_log.dispatch_sync', true)) {
            LogActivityJob::dispatchSync($payload);

            return;
        }

        LogActivityJob::dispatch($payload);
    }

    public function recordSecurityEvent(
        string $action,
        Request $request,
        string $status = ActivityLog::STATUS_FAILED,
        ?string $description = null,
        array $properties = [],
        ?int $actorUserId = null,
    ): void {
        $this->record(new ActivityLogEntry(
            channel: ActivityLog::CHANNEL_SECURITY,
            actorUserId: $actorUserId,
            actorType: $actorUserId ? 'admin' : 'guest',
            action: $action,
            subjectType: 'auth',
            description: $description ?? $action,
            properties: $properties,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            requestId: $this->resolveRequestId($request),
            status: $status,
        ));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function evaluateSecurityAlerts(): array
    {
        $alerts = [];
        $spikeConfig = config('activity_log.security_alerts.failed_login_spike', []);

        if ($spikeConfig !== []) {
            $windowMinutes = (int) ($spikeConfig['window_minutes'] ?? 60);
            $threshold = (int) ($spikeConfig['threshold'] ?? 10);
            $actions = $spikeConfig['actions'] ?? ['login_failed'];
            $since = now()->subMinutes($windowMinutes);

            $count = ActivityLog::query()
                ->where('channel', ActivityLog::CHANNEL_SECURITY)
                ->where('occurred_at', '>=', $since)
                ->whereIn('action', $actions)
                ->where('status', ActivityLog::STATUS_FAILED)
                ->count();

            if ($count >= $threshold) {
                $alerts[] = [
                    'type' => 'failed_login_spike',
                    'severity' => 'warning',
                    'message' => "تعداد {$count} تلاش ورود ناموفق در {$windowMinutes} دقیقه اخیر",
                    'count' => $count,
                    'threshold' => $threshold,
                    'window_minutes' => $windowMinutes,
                ];
            }
        }

        return $alerts;
    }

    /**
     * @return array<string, int> channel => deleted count
     */
    public function pruneExpiredLogs(): array
    {
        $retention = config('activity_log.retention_days', []);
        $deleted = [];

        foreach ($retention as $channel => $days) {
            $cutoff = now()->subDays((int) $days);
            $deleted[$channel] = ActivityLog::query()
                ->where('channel', $channel)
                ->where('occurred_at', '<', $cutoff)
                ->delete();
        }

        return $deleted;
    }

    public function recordAdminHttpAction(Request $request, Response $response): void
    {
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        if ($this->shouldSkipPath($request->path())) {
            return;
        }

        $user = auth('sanctum')->user();
        [$subjectType, $subjectId, $subjectLabel] = $this->resolveSubject($request);
        $action = $this->resolveHttpAction($request->method(), $request->path());
        $status = $response->getStatusCode() >= 400
            ? ActivityLog::STATUS_FAILED
            : ActivityLog::STATUS_SUCCESS;

        $resource = (string) $request->segment(3);
        $description = $this->buildAdminDescription($action, $resource, $subjectId, $status);

        $this->record(new ActivityLogEntry(
            channel: ActivityLog::CHANNEL_ADMIN,
            actorUserId: $user?->id,
            actorType: $user ? 'admin' : 'guest',
            action: $action,
            subjectType: $subjectType,
            subjectId: $subjectId,
            subjectLabel: $subjectLabel,
            description: $description,
            properties: array_filter([
                'method' => $request->method(),
                'path' => '/' . $request->path(),
                'route_name' => $request->route()?->getName(),
                'resource' => $resource !== '' ? $resource : null,
                'status_code' => $response->getStatusCode(),
            ]),
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            requestId: $this->resolveRequestId($request),
            status: $status,
        ));
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: ?string}
     */
    public function resolveSubject(Request $request): array
    {
        $route = $request->route();
        if (! $route) {
            return [null, null, null];
        }

        $paramMap = [
            'mediaAsset' => 'media_asset',
            'story' => 'story',
            'episode' => 'episode',
            'category' => 'category',
            'user' => 'user',
            'role' => 'role',
            'coin' => 'coin',
            'coupon' => 'coupon',
            'notification' => 'notification',
            'person' => 'person',
        ];

        foreach ($paramMap as $param => $subjectType) {
            if (! $route->hasParameter($param)) {
                continue;
            }

            $value = $route->parameter($param);
            if (is_object($value) && method_exists($value, 'getKey')) {
                $id = (string) $value->getKey();
                $label = $this->guessModelLabel($value);

                return [$subjectType, $id, $label];
            }

            return [$subjectType, (string) $value, null];
        }

        if ($route->hasParameter('storyId')) {
            return ['story_editor_story', (string) $route->parameter('storyId'), null];
        }

        if ($route->hasParameter('episodeId')) {
            return ['story_editor_episode', (string) $route->parameter('episodeId'), null];
        }

        if ($route->hasParameter('id')) {
            $segment = (string) $request->segment(3);

            return [$this->segmentToSubjectType($segment), (string) $route->parameter('id'), null];
        }

        return [null, null, null];
    }

    public function resolveHttpAction(string $method, string $path): string
    {
        if (str_contains($path, '/bulk-action')) {
            return 'bulk_action';
        }

        if (str_contains($path, '/import-legacy') || str_contains($path, '/import')) {
            return 'imported';
        }

        return match ($method) {
            'POST' => 'created',
            'PUT', 'PATCH' => 'updated',
            'DELETE' => 'deleted',
            default => 'updated',
        };
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    public function diff(array $before, array $after, array $only = []): array
    {
        $keys = $only !== [] ? $only : array_unique(array_merge(array_keys($before), array_keys($after)));
        $old = [];
        $new = [];

        foreach ($keys as $key) {
            $prev = $before[$key] ?? null;
            $next = $after[$key] ?? null;
            if ($prev === $next) {
                continue;
            }
            $old[$key] = $prev;
            $new[$key] = $next;
        }

        return [
            $this->redactArray($old),
            $this->redactArray($new),
        ];
    }

    public function recordModelChange(Model $model, string $action): void
    {
        if (! $this->shouldRecordModelAudit()) {
            return;
        }

        $config = $this->modelAuditConfig($model);
        if ($config === null) {
            return;
        }

        $subjectType = (string) $config['subject_type'];
        $ignore = array_merge(
            ['updated_at', 'created_at'],
            $config['ignore'] ?? [],
        );

        [$oldValues, $newValues] = match ($action) {
            'created', 'uploaded' => $this->snapshotNewModel($model, $ignore),
            'deleted' => $this->snapshotDeletedModel($model, $ignore),
            default => $this->snapshotChangedModel($model, $ignore),
        };

        if ($action !== 'deleted' && $oldValues === [] && $newValues === []) {
            return;
        }

        $request = request();
        $user = auth('sanctum')->user();

        $this->record(new ActivityLogEntry(
            channel: ActivityLog::CHANNEL_ADMIN,
            actorUserId: $user?->id,
            actorType: $user ? 'admin' : 'system',
            action: "{$subjectType}.{$action}",
            subjectType: $subjectType,
            subjectId: (string) $model->getKey(),
            subjectLabel: $this->guessModelLabel($model),
            description: $this->buildModelChangeDescription($subjectType, $action, $model),
            properties: array_filter([
                'changed_fields' => array_keys($newValues),
                'source' => 'eloquent_observer',
            ]),
            oldValues: $oldValues !== [] ? $oldValues : null,
            newValues: $newValues !== [] ? $newValues : null,
            ipAddress: $request?->ip(),
            userAgent: $request?->userAgent(),
            requestId: $request ? $this->resolveRequestId($request) : null,
            status: ActivityLog::STATUS_SUCCESS,
        ));
    }

    /**
     * @param  array<string, mixed>  $beforeEpisode
     * @param  array<string, mixed>  $afterEpisode
     */
    public function recordStoryEditorEpisodeSaved(
        Request $request,
        string $storySlug,
        string $episodeSlug,
        array $beforeEpisode,
        array $afterEpisode,
        ?string $backupPath = null,
    ): void {
        if (! $this->shouldRecordModelAudit()) {
            return;
        }

        $before = $this->storyEditorEpisodeSummary($beforeEpisode);
        $after = $this->storyEditorEpisodeSummary($afterEpisode);
        [$oldValues, $newValues] = $this->diff($before, $after);

        $user = auth('sanctum')->user();

        $this->record(new ActivityLogEntry(
            channel: ActivityLog::CHANNEL_ADMIN,
            actorUserId: $user?->id,
            actorType: $user ? 'admin' : 'system',
            action: 'story_editor.episode_saved',
            subjectType: 'story_editor_episode',
            subjectId: $episodeSlug,
            subjectLabel: (string) ($after['title'] ?? $episodeSlug),
            description: "ذخیره قسمت «{$after['title']}» در ویرایشگر داستان",
            properties: array_filter([
                'story_slug' => $storySlug,
                'backup_path' => $backupPath,
                'changed_fields' => array_keys($newValues),
                'source' => 'story_editor',
            ]),
            oldValues: $oldValues !== [] ? $oldValues : null,
            newValues: $newValues !== [] ? $newValues : null,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            requestId: $this->resolveRequestId($request),
            status: ActivityLog::STATUS_SUCCESS,
        ));
    }

    public function recordStoryEditorImport(
        Request $request,
        string $storySlug,
        ?string $episodeSlug,
        string $filename,
    ): void {
        if (! $this->shouldRecordModelAudit()) {
            return;
        }

        $user = auth('sanctum')->user();

        $this->record(new ActivityLogEntry(
            channel: ActivityLog::CHANNEL_ADMIN,
            actorUserId: $user?->id,
            actorType: $user ? 'admin' : 'system',
            action: 'story_editor.import',
            subjectType: $episodeSlug ? 'story_editor_episode' : 'story_editor_story',
            subjectId: $episodeSlug ?? $storySlug,
            subjectLabel: $filename,
            description: 'واردسازی فایل در ویرایشگر داستان',
            properties: array_filter([
                'story_slug' => $storySlug,
                'episode_slug' => $episodeSlug,
                'filename' => $filename,
                'source' => 'story_editor',
            ]),
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            requestId: $this->resolveRequestId($request),
            status: ActivityLog::STATUS_SUCCESS,
        ));
    }

    private function shouldRecordModelAudit(): bool
    {
        if (! config('activity_log.model_audit.enabled', true)) {
            return false;
        }

        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return false;
        }

        $user = auth('sanctum')->user();
        if (! $user instanceof User) {
            return false;
        }

        return $user->isAdmin()
            || $user->isSuperAdmin()
            || $user->hasRole('voice_actor');
    }

    /**
     * @return array{subject_type: string, ignore?: array<int, string>}|null
     */
    private function modelAuditConfig(Model $model): ?array
    {
        $config = config('activity_log.model_audit', []);
        $class = $model::class;

        if (! isset($config[$class]) || ! is_array($config[$class])) {
            return null;
        }

        return $config[$class];
    }

    /**
     * @param  array<int, string>  $ignore
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function snapshotNewModel(Model $model, array $ignore): array
    {
        $attributes = $this->auditableAttributes($model, $ignore);

        return [[], $this->redactArray($attributes) ?? []];
    }

    /**
     * @param  array<int, string>  $ignore
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function snapshotDeletedModel(Model $model, array $ignore): array
    {
        $attributes = $this->auditableAttributes($model, $ignore);

        return [$this->redactArray($attributes) ?? [], []];
    }

    /**
     * @param  array<int, string>  $ignore
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function snapshotChangedModel(Model $model, array $ignore): array
    {
        $before = [];
        $after = [];

        foreach (array_keys($model->getChanges()) as $key) {
            if (in_array($key, $ignore, true)) {
                continue;
            }

            if (in_array($key, $model->getHidden(), true)) {
                continue;
            }

            $before[$key] = $model->getOriginal($key);
            $after[$key] = $model->getAttribute($key);
        }

        return $this->diff($before, $after);
    }

    /**
     * @param  array<int, string>  $ignore
     * @return array<string, mixed>
     */
    private function auditableAttributes(Model $model, array $ignore): array
    {
        $attributes = [];

        foreach ($model->getAttributes() as $key => $value) {
            if (in_array($key, $ignore, true)) {
                continue;
            }

            if (in_array($key, $model->getHidden(), true)) {
                continue;
            }

            $attributes[$key] = $value;
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $episode
     * @return array<string, mixed>
     */
    private function storyEditorEpisodeSummary(array $episode): array
    {
        return [
            'title' => $episode['title'] ?? null,
            'summary' => $episode['summary'] ?? null,
            'scene_count' => count($episode['scenes'] ?? []),
            'character_count' => count($episode['characters'] ?? []),
            'dialogue_count' => count($episode['dialogues'] ?? []),
        ];
    }

    private function buildModelChangeDescription(string $subjectType, string $action, Model $model): string
    {
        $label = $this->guessModelLabel($model);
        $name = $label ? "«{$label}»" : ('#' . $model->getKey());
        $typeLabel = str_replace('_', ' ', $subjectType);

        return match ($action) {
            'created' => "ایجاد {$typeLabel} {$name}",
            'uploaded' => "آپلود {$typeLabel} {$name}",
            'updated' => "ویرایش {$typeLabel} {$name}",
            'deleted' => "حذف {$typeLabel} {$name}",
            'published' => "انتشار {$typeLabel} {$name}",
            'banned' => "مسدودسازی کاربر {$name}",
            'role_assigned' => "تغییر نقش کاربر {$name}",
            'renamed' => "تغییر نام {$typeLabel} {$name}",
            default => "تغییر {$typeLabel} {$name}",
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function sanitizeEntry(ActivityLogEntry $entry): array
    {
        $array = $entry->toArray();
        $array['properties'] = $this->capJsonSize($this->redactArray($array['properties'] ?? []));
        $array['old_values'] = $array['old_values'] !== null
            ? $this->capJsonSize($this->redactArray($array['old_values']))
            : null;
        $array['new_values'] = $array['new_values'] !== null
            ? $this->capJsonSize($this->redactArray($array['new_values']))
            : null;

        $maxUa = (int) config('activity_log.max_user_agent_length', 512);
        if (! empty($array['user_agent']) && strlen((string) $array['user_agent']) > $maxUa) {
            $array['user_agent'] = Str::limit((string) $array['user_agent'], $maxUa, '…');
        }

        return $array;
    }

    /**
     * @param  array<string, mixed>|null  $data
     * @return array<string, mixed>|null
     */
    private function redactArray(?array $data): ?array
    {
        if ($data === null || $data === []) {
            return $data;
        }

        $keys = config('activity_log.redact_keys', []);
        $out = [];

        foreach ($data as $key => $value) {
            if ($this->shouldRedactKey((string) $key, $keys)) {
                $out[$key] = '[REDACTED]';
                continue;
            }

            if (is_array($value)) {
                $out[$key] = $this->redactArray($value);
                continue;
            }

            $out[$key] = $value;
        }

        return $out;
    }

  /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function capJsonSize(array $data): array
    {
        $max = (int) config('activity_log.max_properties_bytes', 32768);
        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($encoded !== false && strlen($encoded) <= $max) {
            return $data;
        }

        return [
            'truncated' => true,
            'summary' => Str::limit($encoded ?: '', 2000, '…'),
        ];
    }

    private function shouldRedactKey(string $key, array $needles): bool
    {
        $lower = strtolower($key);
        foreach ($needles as $needle) {
            if (str_contains($lower, strtolower((string) $needle))) {
                return true;
            }
        }

        return false;
    }

    private function shouldSkipPath(string $path): bool
    {
        $normalized = trim($path, '/');
        foreach (config('activity_log.skip_path_patterns', []) as $pattern) {
            if (str_starts_with($normalized, trim($pattern, '/'))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array<string, mixed>>  $events
     * @return array{accepted: int, skipped: int}
     */
    public function recordAppBatch(User $user, array $events, Request $request, string $deviceId, ?string $appVersion, ?string $platform): array
    {
        $accepted = 0;
        $skipped = 0;

        foreach ($events as $event) {
            $eventUuid = (string) ($event['event_uuid'] ?? '');
            if ($eventUuid === '') {
                continue;
            }

            if (ActivityLog::query()
                ->where('device_id', $deviceId)
                ->where('event_uuid', $eventUuid)
                ->exists()) {
                $skipped++;
                continue;
            }

            $occurredAt = ! empty($event['occurred_at'])
                ? \Illuminate\Support\Carbon::parse($event['occurred_at'])
                : now();

            $this->record(new ActivityLogEntry(
                channel: ActivityLog::CHANNEL_APP,
                actorUserId: $user->id,
                actorType: 'user',
                action: (string) $event['action'],
                subjectType: isset($event['subject_type']) ? (string) $event['subject_type'] : null,
                subjectId: isset($event['subject_id']) ? (string) $event['subject_id'] : null,
                subjectLabel: isset($event['subject_label']) ? (string) $event['subject_label'] : null,
                description: $this->buildAppDescription((string) $event['action'], $event),
                properties: is_array($event['properties'] ?? null) ? $event['properties'] : [],
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                requestId: $this->resolveRequestId($request),
                eventUuid: $eventUuid,
                deviceId: $deviceId,
                appVersion: $appVersion,
                platform: $platform,
                status: ActivityLog::STATUS_SUCCESS,
                occurredAt: $occurredAt,
            ));

            $accepted++;
        }

        return ['accepted' => $accepted, 'skipped' => $skipped];
    }

    private function buildAppDescription(string $action, array $event): string
    {
        $label = $event['subject_label'] ?? $event['subject_id'] ?? null;
        $suffix = $label ? " — {$label}" : '';

        return match (true) {
            str_starts_with($action, 'episode.play') => "فعالیت پخش{$suffix}",
            str_starts_with($action, 'auth.') => 'فعالیت ورود/خروج',
            str_starts_with($action, 'favorite.') => 'علاقه‌مندی',
            str_starts_with($action, 'subscription.') => 'اشتراک',
            str_starts_with($action, 'story.') => "داستان{$suffix}",
            default => $action,
        };
    }

    private function resolveRequestId(Request $request): ?string
    {
        $header = $request->header('X-Request-Id');

        return is_string($header) && $header !== '' ? $header : null;
    }

    private function segmentToSubjectType(string $segment): string
    {
        return match ($segment) {
            'story-editor' => 'story_editor',
            'media' => 'media_asset',
            'commission-payments' => 'commission_payment',
            'subscription-plans' => 'subscription_plan',
            'app-versions' => 'app_version',
            'voice-actors' => 'voice_actor',
            'timeline-management' => 'timeline',
            'file-upload' => 'file_upload',
            default => str_replace('-', '_', $segment),
        };
    }

    private function guessModelLabel(object $model): ?string
    {
        if ($model instanceof Model) {
            foreach (['title', 'name', 'name_persian', 'display_name', 'original_name'] as $attr) {
                if (! empty($model->{$attr})) {
                    return (string) $model->{$attr};
                }
            }
        }

        return null;
    }

    private function buildAdminDescription(string $action, string $resource, ?string $subjectId, string $status): string
    {
        $resourceLabel = str_replace(['-', '_'], ' ', $resource);
        $target = $subjectId ? " #{$subjectId}" : '';
        $failed = $status === ActivityLog::STATUS_FAILED ? ' (ناموفق)' : '';

        return match ($action) {
            'created' => "ایجاد در {$resourceLabel}{$target}{$failed}",
            'updated' => "ویرایش در {$resourceLabel}{$target}{$failed}",
            'deleted' => "حذف در {$resourceLabel}{$target}{$failed}",
            'imported' => "واردسازی در {$resourceLabel}{$target}{$failed}",
            'bulk_action' => "عملیات گروهی در {$resourceLabel}{$failed}",
            default => "عملیات {$action} در {$resourceLabel}{$target}{$failed}",
        };
    }
}
