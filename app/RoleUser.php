<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RoleUser extends Model
{
    
    const CREATED_AT = null;
    const UPDATED_AT = null;
    const DELETED_AT = null;

    public $table = 'role_user';

    protected $fillable = ['role_id','user_id'];

    public function users()
    {
        return $this->belongsToMany('App\User');
    }

}
