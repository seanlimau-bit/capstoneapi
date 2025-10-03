<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    use HasFactory;

    protected $table = 'videos';

    protected $fillable = [
        'video_link',
        'video_title',
        'description',
        'status_id',
        'user_id',
        'field_id',
    ];

    protected $casts = [
        'id'        => 'integer',
        'status_id' => 'integer',
        'user_id'   => 'integer',
        'field_id'  => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Many-to-many: videos ↔ skills through skill_video
    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'skill_video', 'video_id', 'skill_id')
        ->withPivot(['status_id', 'sort_order'])
        ->withTimestamps();
    }

    // Belongs-to: videos.status_id → statuses.id
    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function scopePublic($q) 
    { 
    return $q->where('videos.status_id', 3); // Specify table name
    }

    // Belongs-to: videos.user_id → users.id
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
