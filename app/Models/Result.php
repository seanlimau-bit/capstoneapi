<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    public function assessment(){
        return $this->morphTo();
    }

    public function test(){
        return $this->belongsTo(\App\Models\Test::class);
    }
}
