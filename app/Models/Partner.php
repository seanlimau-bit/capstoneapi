<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Partner extends Model
{
    protected $fillable = [
        'code', 'name', 'phone_prefixes', 'access_type', 'billing_method',
        'default_lives', 'trial_duration_days', 'features', 'verification_required'
    ];

    protected $casts = [
        'phone_prefixes' => 'array',
        'features' => 'array',
        'verification_required' => 'boolean'
    ];
    /**
     * Get the status of this partner
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function scopePublic($q) { return $q->where('status', 'Public'); }

}