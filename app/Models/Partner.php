<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}