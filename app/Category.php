<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Category extends Model
{
	use SoftDeletes;

	public    $table = 'categories';
    protected $dates = ['deleted_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'slug',
        'parent_category_id',
        'created_at',
        'updated_at',
    ];

    public function tickets()
    {
        return $this->belongsToMany('App\Ticket','ticket_categories','ticket_id','category_id');
    }

    public function getRouteKeyName()
	{
	    return 'slug';
	}

    //each category might have one parent
      public function parent() {
        return $this->belongsToOne(static::class, 'parent_category_id');
      }

      //each category might have multiple children
      public function children() {
        return $this->hasMany(static::class, 'parent_category_id')->orderBy('name', 'asc');
      }

}
