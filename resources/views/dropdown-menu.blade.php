<div class="dropdown-menu  dropdown-menu-right ">
    <div class="dropdown-header noti-title">
      <h6 class="text-overflow m-0">Welcome, {{ Auth::user()->name }}!</h6>
    </div>
    {{-- <a href="#!" class="dropdown-item">
      <i class="ni ni-single-02"></i>
      <span>My profile</span>
    </a>
    <a href="#!" class="dropdown-item">
      <i class="ni ni-calendar-grid-58"></i>
      <span>Activity</span>
    </a>
    <a href="#!" class="dropdown-item">
      <i class="ni ni-support-16"></i>
      <span>Support</span>
    </a>
    --}}

    <a href="#" class="dropdown-item" data-toggle="modal" data-target="#modalChangePassword">
      <i class="fas fa-key"></i>
      <span>Change Password</span>
    </a>

    @if ( Auth::user()->id !== 22 && Auth::user()->id !== 23 && Auth::user()->id !== 24 ) <!-- exclude for ram and trevor for now 22,23 -->
    <a href="{{ url('user/settings') }}" class="dropdown-item">
      <i class="ni ni-settings-gear-65"></i>
      <span>App Settings</span>
    </a>
    @endif

    @if ( Auth::user()->rolesByIdExists([\App\Role::MANAGER, \App\Role::ADMIN, \App\Role::DEVELOPER]) || Auth::user()->id == 9 ) 
      @if ( Auth::user()->id !== 22 && Auth::user()->id !== 23 && Auth::user()->id !== 24 ) <!-- exclude for ram and trevor for now 22,23 -->
        <a href="{{ url('users/schedules') }}" class="dropdown-item">
          <i class="ni ni-calendar-grid-58"></i>
          <span>Schedules</span>
        </a>
      @endif
    @endif

    <a href="{{ url('export') }}" class="dropdown-item">
      <i class="fa fa-book" aria-hidden="true"></i>
      <span>Reports</span>
    </a>

    @if ( Auth::user()->id == 1 )
    <a href="{{ url('spam/filters') }}" class="dropdown-item">
      <i class="fa fa-exclamation-circle" aria-hidden="true"></i>
      <span>Spam Filter</span>
    </a>
    @endif

    @if ( Auth::user()->rolesByIdExists([\App\Role::MANAGER, \App\Role::ADMIN, \App\Role::DEVELOPER]) )
      <a href="{{ url('activity/logs') }}" class="dropdown-item">
        <i class="far fa-file"></i>
        <span>Logs</span>
      </a>
    @endif
    
    <div class="dropdown-divider"></div>

    <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('frm-logout').submit();" class="dropdown-item">
      <i class="ni ni-user-run"></i>
      <span>User Logout</span>
    </a>
    <form id="frm-logout" action="{{ route('logout') }}" method="POST" style="display: none;">
      {{ csrf_field() }}
    </form>
</div>