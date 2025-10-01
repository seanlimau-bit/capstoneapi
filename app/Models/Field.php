<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Field;

class Field extends Model
{
    use HasFactory;

    protected $fillable = [
        'field',
        'description',
        'user_id',
        'image',
        'status_id',
        'icon'
    ];

    protected $casts = [
        'status_id' => 'integer',
        'user_id' => 'integer',
    ];

    /**
     * Get tracks that belong to this field (One-to-Many)
     * Field has many Tracks
     */
    public function tracks(): HasMany
    {
        return $this->hasMany(Track::class);
    }

    /**
     * Get all skills that belong to this field through tracks
     * Field -> Tracks -> Skills
     */

    public function skills()
    {
        $trackIds = $this->tracks()->pluck('id');
        return Skill::whereHas('tracks', function($query) use ($trackIds) {
            $query->whereIn('tracks.id', $trackIds);
        });
    }


    /**
     * Get the user who created this field
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the status of this field
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    /**
     * Scope to get only active fields
     */
    public function scopePublic($q) { return $q->where('status_id',3); }



    /**
     * Scope to get fields by status
     */
    public function scopeByStatus($query, $statusName)
    {
        return $query->whereHas('status', function ($q) use ($statusName) {
            $q->where('status', $statusName); // Changed from 'name' to 'status'
        });
    }

    /**
     * Scope to search fields by name or description
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('field', 'LIKE', "%{$search}%")
            ->orWhere('description', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Get fields that have tracks
     */
    public function scopeHasTracks($query)
    {
        return $query->has('tracks');
    }

    /**
     * Get fields that don't have tracks
     */
    public function scopeWithoutTracks($query)
    {
        return $query->doesntHave('tracks');
    }


    /**
     * Get the count of tracks for this field
     */
    public function getTracksCountAttribute()
    {
        return $this->tracks()->count();
    }

    /**
     * Get the count of skills for this field
     */
    public function getSkillsCountAttribute()
    {
        return $this->skills()->count();
    }

    /**
     * Get formatted field name with image
     */
    public function getDisplayNameAttribute()
    {
        return $this->image ? "<img src='{$this->image}' class='me-2' style='width: 20px; height: 20px;'> {$this->field}" : $this->field;
    }

    /**
     * Get status name
     */
    public function getStatusNameAttribute()
    {
        return $this->status ? $this->status->name : 'Unknown';
    }

    /**
     * Get status badge HTML
     */
    public function getStatusBadgeAttribute()
    {
        $statusName = $this->status_name;
        $class = $statusName === 'active' ? 'success' : 'warning';
        return "<span class='badge bg-{$class}'>" . ucfirst($statusName) . "</span>";
    }
}