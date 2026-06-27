<?php

namespace App\Services;

use App\Models\Character;
use App\Models\ContentModeration;
use App\Models\Story;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserDeletionService
{
    /**
     * Detach a user from creative/content references before the account is removed.
     */
    public function detachUserReferences(User $user): void
    {
        Story::query()
            ->where('author_id', $user->id)
            ->update(['author_id' => null]);

        Story::query()
            ->where('narrator_id', $user->id)
            ->update(['narrator_id' => null]);

        Character::query()
            ->where('voice_actor_id', $user->id)
            ->update(['voice_actor_id' => null]);

        User::query()
            ->where('parent_id', $user->id)
            ->update(['parent_id' => null]);

        ContentModeration::query()
            ->where('moderator_id', $user->id)
            ->update(['moderator_id' => null]);
    }

    public function deleteUser(User $user): void
    {
        if ($user->isAdmin()) {
            throw new \RuntimeException('نمی‌توان کاربران مدیر را حذف کرد.');
        }

        DB::transaction(function () use ($user) {
            $this->detachUserReferences($user);
            $user->delete();
        });
    }
}
