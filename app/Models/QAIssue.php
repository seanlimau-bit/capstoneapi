<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QAIssue extends Model
{
    protected $table = 'qa_issues';

	protected $fillable = [
        'question_id', 'reviewer_id', 'issue_type', 
        'description', 'status'
    ];
    
    public function question()
    {
        return $this->belongsTo(\App\Models\Question::class);
    }
    
    public function reviewer()
    {
        return $this->belongsTo(\App\Models\User::class, 'reviewer_id');
    }
}