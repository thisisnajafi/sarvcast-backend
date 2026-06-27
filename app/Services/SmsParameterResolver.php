<?php

namespace App\Services;

use App\Helpers\JalaliHelper;
use App\Models\User;

class SmsParameterResolver
{
    /**
     * Resolve template parameters for a user into an ordered array for Melipayamak.
     *
     * @param  array<int, array<string, mixed>>  $parameterDefinitions
     * @param  array<int|string, string>  $overrides  Index-keyed override values
     * @return array<int, string>
     */
    public function resolve(?User $user, array $parameterDefinitions, array $overrides = []): array
    {
        $sorted = collect($parameterDefinitions)
            ->sortBy(fn (array $param) => (int) ($param['index'] ?? 0))
            ->values()
            ->all();

        $values = [];

        foreach ($sorted as $param) {
            $index = (int) ($param['index'] ?? count($values));
            $overrideKey = (string) $index;

            if (array_key_exists($overrideKey, $overrides)) {
                $values[] = (string) $overrides[$overrideKey];
                continue;
            }

            if (array_key_exists($index, $overrides)) {
                $values[] = (string) $overrides[$index];
                continue;
            }

            $values[] = $this->resolveSingle($user, $param);
        }

        return $values;
    }

    /**
     * Build a preview message by substituting {N} placeholders in preview text.
     *
     * @param  array<int, string>  $values
     */
    public function renderPreview(string $previewText, array $values): string
    {
        $message = $previewText;

        foreach ($values as $index => $value) {
            $message = str_replace('{'.$index.'}', $value, $message);
        }

        return $message;
    }

    /**
     * @param  array<string, mixed>  $param
     */
    private function resolveSingle(?User $user, array $param): string
    {
        $source = (string) ($param['source'] ?? '');
        $fallback = (string) ($param['fallback'] ?? '');

        if ($source === 'static') {
            return (string) ($param['static_value'] ?? $fallback);
        }

        if ($source === 'custom') {
            return $fallback;
        }

        if ($user === null) {
            return $fallback;
        }

        return match ($source) {
            'user.first_name' => trim((string) $user->first_name) ?: $fallback,
            'user.last_name' => trim((string) $user->last_name) ?: $fallback,
            'user.full_name' => trim($user->full_name) ?: $fallback,
            'user.phone_number' => (string) $user->phone_number ?: $fallback,
            'subscription.end_date_jalali' => $this->resolveSubscriptionEndDateJalali($user, $fallback),
            'subscription.type_label' => $this->resolveSubscriptionTypeLabel($user, $fallback),
            default => $fallback,
        };
    }

    private function resolveSubscriptionEndDateJalali(User $user, string $fallback): string
    {
        $subscription = $user->activeSubscription;

        if (! $subscription?->end_date) {
            return $fallback;
        }

        return JalaliHelper::toJalali($subscription->end_date, 'Y/m/d') ?? $fallback;
    }

    private function resolveSubscriptionTypeLabel(User $user, string $fallback): string
    {
        $subscription = $user->activeSubscription;

        if (! $subscription) {
            return $fallback;
        }

        return $subscription->type_text ?: $fallback;
    }
}
