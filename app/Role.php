<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    const UPDATED_AT = null;
    const DELETED_AT = null;

    const ADMIN                    = 1;
    const MANAGER                  = 2;
    const AGENT                    = 3;
    const DEVELOPER                = 4;
    const AGENT_EBAY               = 5;
    const CUSTOMER_SERVICE_SUPPORT = 6;

    // const AGENT = 'agent';

    public function users()
    {
        return $this->belongsToMany('App\User');
    }
}
