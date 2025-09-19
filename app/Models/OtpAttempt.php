<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OtpAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone_number',
        'code',
        'purpose',
        'verified',
        'expires_at'
    ];

    protected $casts = [
        'verified' => 'boolean',
        'expires_at' => 'datetime'
    ];

    /**
     * Scope for expired OTPs
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope for valid OTPs
     */
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope for unverified OTPs
     */
    public function scopeUnverified($query)
    {
        return $query->where('verified', false);
    }

    /**
     * Check if OTP is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at < now();
    }

    /**
     * Check if OTP is valid
     */
    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->verified;
    }
}
