<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'user_id',
        'report_type',
        'comment',
        'status',
        'reviewed_by',
        'reviewed_at',
        'admin_notes',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    // Relationships
    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    // Helper methods
    public function markAsUnderReview($reviewerId)
    {
        $this->update([
            'status' => 'under_review',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
        ]);
    }

    public function resolve($reviewerId, $notes = null)
    {
        $this->update([
            'status' => 'resolved',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'admin_notes' => $notes,
        ]);
    }

    public function dismiss($reviewerId, $notes = null)
    {
        $this->update([
            'status' => 'dismissed',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'admin_notes' => $notes,
        ]);
    }
}