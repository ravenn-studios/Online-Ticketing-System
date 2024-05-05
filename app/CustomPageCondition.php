<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomPageCondition extends Model
{
    
    use SoftDeletes;

    public    $table = 'custom_pages_conditions';
    protected $dates = ['deleted_at'];

    CONST OPERATOR_ALL = 'AND';
    CONST OPERATOR_ANY = 'OR';

    protected $fillable = [
        'user_id',
        'custom_page_id',
        'filter',
        'filter_id',
        'operator',
    ];

    public function customPage()
    {
        return $this->belongsTo('App\UserCustomPage', 'custom_page_id');
    }

    public function scopeAll($query)
    {
        return $query->where('operator', self::OPERATOR_ALL);
    }

    public function scopeAny($query)
    {
        return $query->where('operator', self::OPERATOR_ANY);
    }

}
