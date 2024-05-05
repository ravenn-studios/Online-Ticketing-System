<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Role;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Contracts\Activity;

class User extends Authenticatable
{
    use Notifiable, SoftDeletes;
    use LogsActivity;

    protected $dates = ['deleted_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'is_online', 'email_verified_at', 'last_seen'
    ];

    protected static $ignoreChangedAttributes = ['updated_at'];

    protected static $logAttributes = [
        'name', 'email', 'is_online', 'last_seen'
    ];
    
    protected static $recordEvents = ['created', 'updated', 'deleted', 'restored'];
    
    protected static $logOnlyDirty = true;

    protected static $logName = 'User';


    public static function getDescriptionForEvent(string $eventName): string
    {
        $eventName = ucfirst($eventName);
        
        return "{$eventName} a User";
    }

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function ticketLimit()
    {
        return $this->hasMany('App\TicketLimit', 'user_id');
    }
  
    public function online()
    {
        return $roles = $this->where('is_online', true);
    }

    public function chatLogs()
    {
        return $this->hasMany('App\AgentChatLog', 'user_id');
    }

    public function tickets()
    {
        return $this->belongsToMany('App\Ticket','assigned_tickets','user_id','ticket_id');
    }

    public function scopeEbayAgents($query)
    {
        // return $query->where('origin_id', '!=', TicketOrigin::ORIGIN_FACEBOOK);
        return $query->whereHas('roles', function($a){
                        $a->whereIn('id', [Role::AGENT_EBAY]);
                    });
    }

    public function scopeAllAgents($query)
    {
        // return $query->where('origin_id', '!=', TicketOrigin::ORIGIN_FACEBOOK);
        return $query->whereHas('roles', function($a){
                        $a->whereIn('id', [Role::AGENT, Role::AGENT_EBAY, Role::CUSTOMER_SERVICE_SUPPORT]);
                    });
    }

    public function scopeTeamAnne($query)
    {
        return $query->whereIn('id', [28]);
    }

    public function scopeTeamThea($query)
    {
        return $query->whereIn('id', [25,29,26]);
    }

    public function scopeAllAgentsAndManagers($query)
    {
        // return $query->where('origin_id', '!=', TicketOrigin::ORIGIN_FACEBOOK);
        return $query->whereHas('roles', function($a){
                        $a->whereIn('id', [Role::AGENT, Role::AGENT_EBAY, Role::CUSTOMER_SERVICE_SUPPORT, Role::MANAGER]);
                    });
    }

    public function scopeManagers($query)
    {
        // return $query->where('origin_id', '!=', TicketOrigin::ORIGIN_FACEBOOK);
        return $query->whereHas('roles', function($a){
                        $a->whereIn('id', [Role::MANAGER]);
                    });
    }

    public function scopeFaeAgents($query)
    {
        // return $query->where('origin_id', '!=', TicketOrigin::ORIGIN_FACEBOOK);
        return $query->where('id','<>', 18)->whereHas('roles', function($a){
                        // $a->whereIn('id', [Role::AGENT, Role::CUSTOMER_SERVICE_SUPPORT]);
                        $a->whereIn('id', [Role::AGENT, Role::CUSTOMER_SERVICE_SUPPORT]);
                    });
    }

    public function scopeFaeAgentsExcludeAnne($query)
    {
        // return $query->where('origin_id', '!=', TicketOrigin::ORIGIN_FACEBOOK);
        return $query->where('id', '!=', 9)->whereHas('roles', function($a){
                        // $a->whereIn('id', [Role::AGENT, Role::CUSTOMER_SERVICE_SUPPORT]);
                        $a->whereIn('id', [Role::AGENT, Role::CUSTOMER_SERVICE_SUPPORT]);
                    });
    }

    /**
     * The roles that belong to the user.
     */
    public function roles()
    {
        return $this->belongsToMany('App\Role');
    }

    public function hasRole($role)
    {
        $roles = $this->roles()->where('name', $role)->count();

        if( $roles == 1 )
        {
            return true;
        }

        return false;
    }

    public function getRoles() {
        $roles = [];
        if ($this->roles()) {
            $roles = $this->roles()->get();
        }
        return $roles;
    }

    //check if user has both
    public function rolesByIdExists(Array $roleIds) {
        $roles = [];

        $_roles = $this->roles()->whereIn('id', $roleIds);
        if ( $_roles->count() ) {
            return true;
        }
        else
        {
            return false;
        }
        // return $this->roles()->get();
    }

    public function getUsersOnlineCount()
    {
        return $this->where('is_online', true)->count();
    }

    public function performanceLogs()
    {
        return $this->hasMany('App\UserPerformanceLog', 'user_id');
    }

    /**
     * The custom pages that belong to the user.
     */
    public function customPages()
    {
        return $this->hasMany('App\UserCustomPage', 'user_id');
    }

    public function hasCustomPages()
    {
        $customPages = $this->customPages()->count();

        if( $customPages )
        {
            return true;
        }

        return false;
    }

    public function roundedAvatar2()
    {
        
        $path = 'images/'.$this->avatar;
        $full_path = \Storage::path($path);
        $base64 = base64_encode(\Storage::get($path));
        $image_data = 'data:'.mime_content_type($full_path).';base64,' .$base64;
        // $_image = base64_encode($_image);

        // return '<img src="'.$image_data.'" width="64" class="avatar rounded-circle">';
        return env('APP_URL', false).\Storage::url($path);

    }
    
    public function roundedAvatar($size = 64)
    {
        
        $_image = \Storage::get('images/'.$this->avatar);
        $_image = base64_encode($_image);

        if ( $size == 'sm' )
        {
            return '<img width="32" style="width:32px;height:32px;" src="data:image/jpeg;charset=utf-8;base64,'.$_image.'" class="avatar rounded-circle">';
        }
        return '<img width="64" src="data:image/jpeg;charset=utf-8;base64,'.$_image.'" class="avatar rounded-circle">';

    }

    public function avatar()
    {

        $_image = \Storage::get('images/'.$this->avatar);
        $_image = base64_encode($_image);

        return '<img src="data:image/jpeg;charset=utf-8;base64,'.$_image.'" width="64">';

    }

    public function avatarNav()
    {

        $_image = \Storage::get('images/'.$this->avatar);
        $_image = base64_encode($_image);

        return '<img src="data:image/jpeg;charset=utf-8;base64,'.$_image.'" width="64" style="width: 40px; height: 40px;" class="avatar rounded-circle">';

    }

    public function chatAvatar()
    {

        $_image = \Storage::get('images/'.$this->avatar);
        $_image = base64_encode($_image);

        return '<img src="data:image/jpeg;charset=utf-8;base64,'.$_image.'" width="64" style="margin: 0!important; width: 40px; height: 40px;" class="avatar rounded-circle">';

    }

    public function userDetails()
    {
        return [
            'id'     => $this->id,
            'name'   => ucwords($this->name),
            'email'  => $this->email,
            'avatar' => $this->roundedAvatar2(),
            'role'   => ucwords($this->roles->first()->name),
        ];
    }

    public function next() {
        
        return $this->where('id', '>', $this->id)->first();
    
    }

}
