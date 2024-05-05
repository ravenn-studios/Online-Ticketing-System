<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FacebookPage extends Model
{
    
    use SoftDeletes;
    public    $table = 'facebook_pages';
    protected $dates = ['deleted_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'page_id',
        'name',
        'access_token',
        'image',
        'created_at',
        'updated_at',
    ];

    public function displayPhoto()
    {
        
        $_image = \Storage::get('images/'.$this->image);
        $_image = base64_encode($_image);

        return '<img class="facebookPageDisplayPhoto avatar rounded-circle" src="data:image/jpeg;charset=utf-8;base64,'.$_image.'">';

    }

    public function displayPhotoSm($name = '')
    {
        
        $_image = \Storage::get('images/'.$this->image);
        $_image = base64_encode($_image);

        return '<img class="facebookPageDisplayPhoto avatar avatar-sm float-right mt--10 rounded-circle" src="data:image/jpeg;charset=utf-8;base64,'.$_image.'" title="'.$name.'" data-toggle="tooltip" data-placement="top" title="'.$name.'">';

    }

    public function getFacebookPagesNames()
    {
        $facebookPageNames   = array_column( $this->get('name')->toArray(), 'name' );
        $facebookPageNames[] = 'DevHelpdesk';
        $facebookPageNames[] = 'DevTicketing';
        
        return $facebookPageNames;
    }

}
