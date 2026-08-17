<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserResume extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'headline',
        'years_of_experience',
        'about',
        'specialties',
        'experience',
        'education',
        'awards',
        'languages',
        'demo_url',
        'social_links',
        'is_public',
        'show_in_talent_directory',
        'published_at',
        'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'years_of_experience' => 'integer',
            'specialties' => 'array',
            'experience' => 'array',
            'education' => 'array',
            'awards' => 'array',
            'languages' => 'array',
            'social_links' => 'array',
            'is_public' => 'boolean',
            'show_in_talent_directory' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
