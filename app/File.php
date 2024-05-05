<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    public $table = 'files';

    protected $fillable = [
        'name',
        'extension',
        'path'
    ];
}
