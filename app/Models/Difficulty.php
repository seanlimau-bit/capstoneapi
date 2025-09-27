<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Difficulty extends Model
{
    use RecordLog;
    
    protected $hidden = ['user_id', 'created_at', 'updated_at'];
    protected $fillable = ['difficulty', 'description', 'short_description',
        'image', 'status_id'];

    //relationship
    public function user() {                        //who created this difficulty level
        return $this->belongsTo(\App\Models\User::class,'user_id');
    }

    public function levels(){
        return $this->belongsToMany(\App\Models\Level);
    }
    public function status() {
        return $this->belongsTo(\App\Models\Status::class, 'status_id');
    }
}
