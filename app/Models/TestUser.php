<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestUser extends Model
{
    use RecordLog;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'test_user';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['test_id','user_id', 'test_completed', 'completed_date','result','attempts','kudos'];

    public function user() {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function test() {
        return $this->belongsTo(\App\Models\SkillTest::class, 'test_id');
    }

}
