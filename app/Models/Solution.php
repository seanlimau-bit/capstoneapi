<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Solution extends Model
{
    use RecordLog;
	
    protected $hidden = ['user_id', 'created_at', 'updated_at','question_id'];
    protected $fillable = ['user_id','question_id','user_id','solution'];

    //relationship
    public function author() {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function question(){
    	return $this->belongsTo(\App\Models\Question::class);
    }

    public function status() {
        return $this->belongsTo(\App\Models\Status::class);
    }

    public function scopePublic($q) { return $q->where('status', 'Public'); }
}
