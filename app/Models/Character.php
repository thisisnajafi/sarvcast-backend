<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasImageUrl;

class Character extends Model
{
    use HasImageUrl;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'story_id',
        'name',
        'image_url',
        'voice_actor_id',
        'description',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'story_id' => 'integer',
            'voice_actor_id' => 'integer',
        ];
    }

    /**
     * Get the story that owns the character.
     */
    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    /**
     * Get the voice actor (user) for this character.
     */
    public function voiceActor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voice_actor_id');
    }

    /**
     * Get validation rules for character creation/update
     */
    public static function getValidationRules($characterId = null)
    {
        return [
            'story_id' => ['required', 'integer', 'exists:stories,id'],
            'name' => ['required', 'string', 'max:200'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'voice_actor_id' => [
                'nullable',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $user = User::find($value);
                        if ($user && !in_array($user->role, [
                            User::ROLE_VOICE_ACTOR,
                            User::ROLE_ADMIN,
                            User::ROLE_SUPER_ADMIN
                        ])) {
                            $fail('کاربر انتخاب شده باید نقش صداپیشه، ادمین یا ادمین کل داشته باشد.');
                        }
                    }
                },
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get validation messages for character validation
     */
    public static function getValidationMessages()
    {
        return [
            'story_id.required' => 'شناسه داستان الزامی است',
            'story_id.exists' => 'داستان انتخاب شده معتبر نیست',
            'name.required' => 'نام شخصیت الزامی است',
            'name.max' => 'نام شخصیت نمی‌تواند بیشتر از 200 کاراکتر باشد',
            'image_url.max' => 'آدرس تصویر نمی‌تواند بیشتر از 500 کاراکتر باشد',
            'voice_actor_id.exists' => 'کاربر انتخاب شده معتبر نیست',
            'description.max' => 'توضیحات نمی‌تواند بیشتر از 1000 کاراکتر باشد',
        ];
    }

    /**
     * Get the character image URL (using HasImageUrl trait)
     */
    public function getImageUrlAttribute($value)
    {
        return $this->getImageUrlFromPath($value);
    }

    /**
     * Scope to get characters with voice actors
     */
    public function scopeWithVoiceActors($query)
    {
        return $query->with('voiceActor');
    }

    /**
     * Scope to get characters for a specific story
     */
    public function scopeForStory($query, $storyId)
    {
        return $query->where('story_id', $storyId);
    }

    /**
     * Get characters with their voice actors for a story
     */
    public static function getCharactersWithVoiceActors(int $storyId)
    {
        return self::where('story_id', $storyId)
            ->with('voiceActor')
            ->get();
    }
}
