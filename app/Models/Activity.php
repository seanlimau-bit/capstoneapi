<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
	use RecordLog;

    protected $hidden = ['user_id', 'created_at', 'updated_at'];
    protected $fillable = ['user_id','classwork_id','classwork_type', 'user_id','house_id'];

    public function user(){
    	return $this->belongsTo(\App\Models\User::class);
    }

    public function house(){
    	return $this->belongsTo(\App\Models\House::class);
    }

    public function classwork(){
    	return $this->morphTo();
    }
}
