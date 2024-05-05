<div class="dropdown-menu dropdown-menu-xl  dropdown-menu-right  py-0 overflow-hidden {{ $showNotificationTab }}">
  <!-- Dropdown header -->
  <div class="px-3 py-3">
    <h6 class="text-sm text-muted m-0">You have <strong class="text-primary">{{ $unreadNotficiationsCount }}</strong> unread notifications.</h6>
  </div>
  <!-- List group -->
  <div class="list-group list-group-flush">
    {{-- <a href="#!" class="list-group-item list-group-item-action">
      <div class="row align-items-center">
        <div class="col-auto">
          <!-- Avatar -->
          <img alt="Image placeholder" src="{{ asset('images/theme/team-1.jpg') }}" class="avatar rounded-circle">
        </div>
        <div class="col ml--2">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h4 class="mb-0 text-sm">John Snow</h4>
            </div>
            <div class="text-right text-muted">
              <small>2 hrs ago</small>
            </div>
          </div>
          <p class="text-sm mb-0">Let's meet at Starbucks at 11:30. Wdyt?</p>
        </div>
      </div>
    </a>
    <a href="#!" class="list-group-item list-group-item-action">
      <div class="row align-items-center">
        <div class="col-auto">
          <!-- Avatar -->
          <img alt="Image placeholder" src="{{ asset('images/theme/team-2.jpg') }}" class="avatar rounded-circle">
        </div>
        <div class="col ml--2">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h4 class="mb-0 text-sm">John Snow</h4>
            </div>
            <div class="text-right text-muted">
              <small>3 hrs ago</small>
            </div>
          </div>
          <p class="text-sm mb-0">A new issue has been reported for Argon.</p>
        </div>
      </div>
    </a>
    <a href="#!" class="list-group-item list-group-item-action">
      <div class="row align-items-center">
        <div class="col-auto">
          <!-- Avatar -->
          <img alt="Image placeholder" src="{{ asset('images/theme/team-3.jpg') }}" class="avatar rounded-circle">
        </div>
        <div class="col ml--2">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h4 class="mb-0 text-sm">John Snow</h4>
            </div>
            <div class="text-right text-muted">
              <small>5 hrs ago</small>
            </div>
          </div>
          <p class="text-sm mb-0">Your posts have been liked a lot.</p>
        </div>
      </div>
    </a>
    <a href="#!" class="list-group-item list-group-item-action">
      <div class="row align-items-center">
        <div class="col-auto">
          <!-- Avatar -->
          {!! Auth::user()->roundedAvatar() !!}
        </div>
        <div class="col ml--2">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h4 class="mb-0 text-sm">John Snow</h4>
            </div>
            <div class="text-right text-muted">
              <small>2 hrs ago</small>
            </div>
          </div>
          <p class="text-sm mb-0">Let's meet at Starbucks at 11:30. Wdyt?</p>
        </div>
      </div>
    </a>
    <a href="#!" class="list-group-item list-group-item-action">
      <div class="row align-items-center">
        <div class="col-auto">
          <!-- Avatar -->
          <img alt="Image placeholder" src="{{ asset('images/theme/team-5.jpg') }}" class="avatar rounded-circle">
        </div>
        <div class="col ml--2">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h4 class="mb-0 text-sm">John Snow</h4>
            </div>
            <div class="text-right text-muted">
              <small>3 hrs ago</small>
            </div>
          </div>
          <p class="text-sm mb-0">A new issue has been reported for Argon.</p>
        </div>
      </div>
    </a> --}}

    @forelse( $notifications as $notification )

      <a href="#!" class="list-group-item list-group-item-action view-notification-details @if( !$notification->read ) bg-unread @endif" data-toggle="modal" data-target="#modalNotificationDetailsPopup" data-notification-id="{{ $notification->id }}" data-ticket-request-id="{{ $notification->ticket_request->id }}" data-ticket-id="{{ $notification->ticket_request->ticket_id }}">
        <div class="row align-items-center">
          <div class="col-auto">
            {!! $notification->user->roundedAvatar() !!}
          </div>
          <div class="col ml--2">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h4 class="mb-0 text-sm">{{ $notification->user->name }}</h4>
              </div>
              <div class="text-right text-muted">
                <small>{{ $notification->created_at->diffForHumans() }}</small>
              </div>
            </div>
            <p class="text-sm mb-0">{{ $notification->description }}</p>
          </div>
        </div>
      </a>

    @empty

      <p class="text-sm mb-0 view-all-notifications">Notifications not found.</p>

    @endforelse

  </div>
  <!-- View all -->
  <a href="#" class="dropdown-item text-center text-primary font-weight-bold py-3 view-more-notifications">View More</a>
</div>