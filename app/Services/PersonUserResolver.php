<?php

namespace App\Services;

use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Resolve a Person (legacy people table) to a User account for push notifications.
 * People have no email/user_id; match by normalized display name among staff roles.
 */
class PersonUserResolver
{
    public function resolve(?Person $person): ?User
    {
        if (! $person) {
            return null;
        }

        $target = $this->normalizeName($person->name);
        if ($target === '') {
            return null;
        }

        $candidates = User::query()
            ->whereIn('role', [
                User::ROLE_VOICE_ACTOR,
                User::ROLE_ADMIN,
                User::ROLE_SUPER_ADMIN,
            ])
            ->whereIn('status', User::loginAllowedStatuses())
            ->get(['id', 'first_name', 'last_name', 'role']);

        foreach ($candidates as $user) {
            $fullName = $this->normalizeName(trim(($user->first_name ?? '').' '.($user->last_name ?? '')));
            $storedName = $this->normalizeName((string) ($user->name ?? ''));

            if ($storedName !== '' && $storedName === $target) {
                return $user;
            }

            if ($fullName !== '' && $fullName === $target) {
                return $user;
            }

            $first = $this->normalizeName((string) $user->first_name);
            $last = $this->normalizeName((string) $user->last_name);
            if ($first !== '' && $last !== '' && ($first.' '.$last) === $target) {
                return $user;
            }
        }

        return null;
    }

    public function resolveByPersonId(?int $personId): ?User
    {
        if (! $personId) {
            return null;
        }

        return $this->resolve(Person::query()->find($personId));
    }

    private function normalizeName(?string $name): string
    {
        $value = trim((string) $name);
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return Str::lower($value);
    }
}
