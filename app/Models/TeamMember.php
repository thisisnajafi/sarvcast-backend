<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMember extends Model
{
    protected $fillable = [
        'user_id',
        'display_title',
        'sort_order',
        'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_visible' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Public payload for app + landing page (role = custom display title).
     *
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        $user = $this->user;

        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone_number' => $user->phone_number,
            'profile_image_url' => $user->profile_image_url,
            'bio' => $user->bio,
            'role' => $this->display_title,
            'role_title' => $this->display_title,
        ];
    }

    /**
     * Admin dashboard payload.
     *
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $user = $this->user;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'display_title' => $this->display_title,
            'sort_order' => $this->sort_order,
            'is_visible' => $this->is_visible,
            'first_name' => $user?->first_name,
            'last_name' => $user?->last_name,
            'phone_number' => $user?->phone_number,
            'profile_image_url' => $user?->profile_image_url,
            'bio' => $user?->bio,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
