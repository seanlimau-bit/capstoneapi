<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\RecordLog;

class Status extends Model
{
    use RecordLog;
    
    public function tracks(){
        return $this->hasMany(\App\Models\Track::class);
    }
    public function houses(){
        return $this->hasMany(\App\Models\House::class);
    }
}