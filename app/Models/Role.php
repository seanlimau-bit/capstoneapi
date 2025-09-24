<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use RecordLog;

    protected $fillable = ['role', 'description'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['pivot','created_at','updated_at'];

    public function users() {
        return $this->hasMany(\App\Models\User::class);       
    }
    public function permissions(){
    	return $this->belongsToMany(\App\Models\Permission::class);
    }

    public function givePermissionTo(\App\Models\Permission $permission) {
    	return $this->permissions()->save($permission);
    }

    public function houseusers() {
        return $this->belongsToMany(\App\Models\User::class, 'house_role_user')->withPivot('house_id')->withTimestamps();    	
    }

    public function enrolment(){
        return $this->belongsToMany(\App\Models\User::class, 'house_role_user')->withPivot('house_id', 'mastercode', 'profile', 'payment_email','purchaser_id', 'transaction_id')->withTimestamps();
    }
}
