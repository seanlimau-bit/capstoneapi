<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    protected $fillable = [
        'code', 'name', 'phone_prefixes', 'access_type', 'billing_method',
        'default_lives', 'trial_duration_days', 'features', 'verification_required',
        'auto_activate', 'api_key', 'webhook_secret', 'status_sync_url',
        'is_active', 'config'
    ];

    protected $casts = [
        'phone_prefixes' => 'array',
        'features' => 'array',
        'config' => 'array',
        'verification_required' => 'boolean',
        'auto_activate' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}