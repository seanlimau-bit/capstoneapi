<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkillLink extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'skilllinks';

    protected $hidden = ['user_id', 'created_at', 'updated_at', 'skill_id'];
    protected $fillable = ['user_id','skill_id','link','title','status_id'];
    //relationship
    public function user() {                        //who created this question
        return $this->belongsTo(\App\Models\User::class);
    }

    public function skill(){
    	return $this->belongsTo(\App\Models\Skill::class);
    }

    public function status(){
    	return $this->hasOne(\App\Models\Status::class);
    }
        // ✅ Accessor to return full URL for 'link'
/*    public function getLinkAttribute($value)
    {
        return url($value);  // auto-prepend your app URL
    }
*/
}
