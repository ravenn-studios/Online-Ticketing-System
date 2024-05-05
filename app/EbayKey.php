<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EbayKey extends Model
{
    public $table = 'ebay_keys';

    use SoftDeletes;

    const ACTIVE = 1;

    protected $fillable = [
		'id',
		'status',
		'access_token',
		'refresh_token',
		'appId',
		'certId',
		'devId',
		'redirect_url',
		'created_at',
		'updated_at',
		'deleted_at',
    ];

    public function scopeActive($query)
    {
    	$query->where('status', self::ACTIVE);
    }

}
