<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Level extends Model
{
    use RecordLog;
    
    protected $hidden = ['user_id', 'created_at', 'updated_at'];
    protected $fillable = ['level', 'description', 'age', 'start_maxile_level','end_maxile_level',
        'image', 'status_id'];

    //relationship
    public function creator() {                        //who created this level
        return $this->belongsTo(\App\Models\User::class);
    }

    public function tracks(){
        return $this->hasMany(\App\Models\Track::class);
    }

    public function questions(){
        return $this->hasManyThrough(\App\Models\Question::class,\App\Models\Skill::class);
    }

    public function difficulties(){
        return $this->hasMany(\App\Models\Difficulty::class);
    }

    public function status() {
        return $this->belongsTo(\App\Models\Status::class);
    }
    public function scopePublic($q) { return $q->where('status', 'Public'); }

    public function scopeNext() {
        return $this->belongsTo(\App\Models\Level::class)->where('start_maxile_level','>', $this->start_maxile_level)->orderBy('start_maxile_level','asc')->first();
    }

    public function scopeMaxilePerTrack(){
        return 100/$this->tracks()->count();
    }

    public function scopeMyLevel(){
        return $this->whereAge(min(\App\Models\User::age(),18));
    }

    public function scopeUntestedSkills($user){
        $skills = [];
        $tracks = $this->tracks()->with('skills')->get();
        foreach ($tracks as $track) {
            $anySkill = $track->skillsdesc->diff(Auth::user()->skill_user);
            count($anySkill) > 0 ? array_push($skills, $anySkill->first()->id) : null;
        }
        return \App\Models\Question::whereIn('skill_id',$skills)->groupBy('skill_id')->orderBy('difficulty_id','desc')->orderBy('skill_id','desc')->get();
    }

    /**
     * Given a user level, find all the done tracks first, then the lowest undone skill for each 
     * attempted track, and then returns a list of questions with the lowest difficulty of these 
     * skills
     *
     */
    public function scopeNextQuestions(){
        $tracks = $this->tracks()->with('skills')->get(); //returns all the tracks for user's level
        $skills = [];
        foreach ($tracks as $track) {
            count($track->skills) > 0 ? array_push($skills, $track->skills[0]->id): null; // $skills as an array of the easiest skill for each track
        }

        return \App\Models\Question::whereIn('skill_id',$skills)->groupBy('skill_id')->whereDifficultyId(1)->inRandomOrder()->get();
    }
}
