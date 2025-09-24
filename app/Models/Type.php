<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Type extends Model
{
    use RecordLog;

    protected $hidden = ['created_at', 'updated_at'];
    protected $fillable = ['type', 'description'];

    //relationship
    public function questions(){
        return $this->hasMany(\App\Models\Question::class);
    }

    public function status() {
        return $this->belongsTo(\App\Models\Status::class);
    }
}
