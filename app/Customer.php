<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    public $table = 'customer';

    protected $fillable = [
        'name',
        'email',
        'ip_address',
    ];
}
