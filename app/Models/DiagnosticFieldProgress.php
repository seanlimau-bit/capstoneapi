<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiagnosticFieldProgress extends Model
{
    protected $table = 'diagnostic_field_progress';
    
    protected $fillable = [
        'session_id',
        'field_id',
        'current_level',
        'wrong_count_at_level',
        'final_level',
        'completed',
    ];
    
    protected $casts = [
        'completed' => 'boolean',
    ];
    
    /**
     * Get the diagnostic session
     */
    public function session()
    {
        return $this->belongsTo(DiagnosticSession::class, 'session_id');
    }
    
    /**
     * Get the field
     */
    public function field()
    {
        return $this->belongsTo(Field::class);
    }
    
    /**
     * Mark this field as completed with final level
     */
    public function complete($finalLevel)
    {
        $this->update([
            'final_level' => $finalLevel,
            'completed' => true,
        ]);
    }
    
    /**
     * Record a correct answer - move up a level
     */
    public function moveUp($increment = 100)
    {
        $this->update([
            'current_level' => $this->current_level + $increment,
            'wrong_count_at_level' => 0, // Reset wrong count when moving to new level
        ]);
    }
    
    /**
     * Record a wrong answer - increment wrong count
     * Returns true if hit ceiling (2 wrongs at this level)
     */
    public function recordWrong()
    {
        $newWrongCount = $this->wrong_count_at_level + 1;
        
        $this->update([
            'wrong_count_at_level' => $newWrongCount,
        ]);
        
        // Hit ceiling if wrong twice at same level
        return $newWrongCount >= 2;
    }
    
    /**
     * Move down a level after wrong answer
     */
    public function moveDown($decrement = 100)
    {
        $this->update([
            'current_level' => max(0, $this->current_level - $decrement),
            'wrong_count_at_level' => 0, // Reset wrong count when moving to new level
        ]);
    }
}