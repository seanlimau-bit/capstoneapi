<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiagnosticSession extends Model
{
    protected $fillable = [
        'user_id',
        'subject',
        'mode',
        'seed_hint_type',
        'seed_hint_value',
        'start_maxile',
        'end_maxile',
        'item_count',
        'current_item_id',
        'status',
        'started_at',
        'completed_at',
        'result',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'result' => 'array',
        'start_maxile' => 'float',
        'end_maxile' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}