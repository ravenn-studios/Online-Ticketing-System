<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Tag extends Model
{
	use SoftDeletes;

	public    $table = 'tags';
    protected $dates = ['deleted_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'created_at',
        'updated_at',
    ];

    public function tickets()
    {
        return $this->belongsToMany('App\Ticket','tickets_tags','ticket_id','tag_id');
    }

    public function getRouteKeyName()
	{
	    return 'slug';
	}

}
