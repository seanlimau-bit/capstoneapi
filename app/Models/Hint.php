<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Question;
use App\Models\User;

class Hint extends Model {
    protected $fillable = ['question_id','hint_level','hint_text','user_id'];

    public function question(){ 
    	return $this->belongsTo(Question::class); 
    }

    public function author(){ 
    	return $this->belongsTo(User::class); 
    }

}
