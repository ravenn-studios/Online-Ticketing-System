<!--
=========================================================
* Argon Dashboard - v1.2.0
=========================================================
* Product Page: https://www.creative-tim.com/product/argon-dashboard


* Copyright  Creative Tim (http://www.creative-tim.com)
* Coded by www.creative-tim.com



=========================================================
* The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
-->

<!DOCTYPE html>
<html>

<head>
  
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="Start your development with a Dashboard for Bootstrap 4.">
  <meta name="author" content="Creative Tim">

  {{-- Recent Addition --}}
  {{-- Google Platform Library --}}
  <meta name="google-signin-client_id" content="183254322995-npft2i8o8scv9uq3r60h6o25i66s13lj.apps.googleusercontent.com">

  <title>Black Edge - Help Desk</title>
  <!-- Favicon -->
  <link rel="icon" href="{{ asset('images/favicon.png') }}" type="image/png">

  <link href="{{ asset('css/@fortawesome/fontawesome-free/css/all.min.css') }}" rel="stylesheet">

  <!-- Fonts -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700">
  <!-- Icons -->
  <link rel="stylesheet" href="{{ asset('css/nucleo/css/nucleo.css') }}" type="text/css">
  <link rel="stylesheet" href="{{ asset('css/fontawesome-free/css/all.min.css') }}" type="text/css">
  <!-- Argon CSS -->
  <link rel="stylesheet" href="{{ asset('css/argon.css?v=1.2.0') }}" type="text/css">

  <link href="{{ asset('css/custom.css').'?v='.time() }}" rel="stylesheet">

  <link rel="stylesheet" href="{{ asset('css/dropzone-3.min.css') }}" type="text/css">

  <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />

  <!-- Latest compiled and minified CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">

  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href="{{ asset('css/select2totree.css') }}" rel="stylesheet" />

  <style>
    canvas{
      -moz-user-select: none;
      -webkit-user-select: none;
      -ms-user-select: none;
    }
    #chartjs-tooltip {
      opacity: 1;
      position: absolute;
      background: rgba(0, 0, 0, .7);
      color: white;
      border-radius: 3px;
      -webkit-transition: all .1s ease;
      transition: all .1s ease;
      pointer-events: none;
      -webkit-transform: translate(-50%, 0);
      transform: translate(-50%, 0);
    }

    .chartjs-tooltip-key {
      display: inline-block;
      width: 10px;
      height: 10px;
      margin-right: 10px;
    }
  </style>

  <meta name="csrf-token" content="{{ csrf_token() }}" />

</head>

<body>
  <div class="floating-search-results"></div>
  <div class="spinner-border text-primary search-order-spinner" role="status">
    <span class="sr-only">Loading...</span>
  </div>
  <input class="floating-search-input form-control" placeholder="Search Order #">
  <a href="javascript:" id="floating-search-btn"><i class="fas fa-search"></i></a>
  <a href="javascript:" id="return-to-top"><i class="fas fa-angle-up"></i></a>
    <!-- Sidenav -->
    @include('layouts.sidenav')

  <!-- Main content -->
  <div class="main-content" id="panel">
    <!-- Topnav -->
    <nav class="navbar navbar-top navbar-expand navbar-dark bg-primary border-bottom">
      <div class="container-fluid">
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
          <!-- Search form -->
          {{-- <form class="navbar-search navbar-search-light form-inline mr-sm-3" id="navbar-search-main">
            <div class="form-group mb-0">
              <div class="input-group input-group-alternative input-group-merge">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-search"></i></span>
                </div>
                <input class="form-control" placeholder="Search" type="text">
              </div>
            </div>
            <button type="button" class="close" data-action="search-close" data-target="#navbar-search-main" aria-label="Close">
              <span aria-hidden="true">×</span>
            </button>
          </form> --}}
          <!-- Navbar links -->
          <ul class="navbar-nav align-items-center  ml-md-auto ">
            <li class="nav-item d-xl-none">
              <!-- Sidenav toggler -->
              <div class="pr-3 sidenav-toggler sidenav-toggler-dark" data-action="sidenav-pin" data-target="#sidenav-main">
                <div class="sidenav-toggler-inner">
                  <i class="sidenav-toggler-line"></i>
                  <i class="sidenav-toggler-line"></i>
                  <i class="sidenav-toggler-line"></i>
                </div>
              </div>
            </li>
            <li class="nav-item d-sm-none">
              <a class="nav-link" href="#" data-action="search-show" data-target="#navbar-search-main">
                <i class="ni ni-zoom-split-in"></i>
              </a>
            </li>

            @if ( Auth::user()->id == 1 )
            
            <li class="nav-item dropdown notification-bell hidden">
              {{-- <a class="nav-link" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> --}}
              <a class="nav-link link-notifications" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                <i class="ni ni-bell-55"></i>
              </a>

              <div class="notifications-tab-wrapper">

                {{-- @include('notifications.notification-tab') --}}

                {{-- <div class="dropdown-menu dropdown-menu-xl  dropdown-menu-right  py-0 overflow-hidden">
                  <!-- Dropdown header -->
                  <div class="px-3 py-3">
                    <h6 class="text-sm text-muted m-0">You have <strong class="text-primary">13</strong> notifications.</h6>
                  </div>
                  <!-- List group -->
                  <div class="list-group list-group-flush">
                    <a href="#!" class="list-group-item list-group-item-action">
                      <div class="row align-items-center">
                        <div class="col-auto">
                          <!-- Avatar -->
                          <img alt="Image placeholder" src="{{ asset('images/theme/team-1.jpg') }}" class="avatar rounded-circle">
                        </div>
                        <div class="col ml--2">
                          <div class="d-flex justify-content-between align-items-center">
                            <div>
                              <h4 class="mb-0 text-sm"></h4>
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
                    </a>
                  </div>
                  <!-- View all -->
                  <a href="#!" class="dropdown-item text-center text-primary font-weight-bold py-3">View all</a>
                </div> --}}
                
              </div>

            </li>

            @endif

            {{-- <li class="nav-item dropdown">
              <a class="nav-link" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="ni ni-bell-55"></i>
              </a>
              <div class="dropdown-menu dropdown-menu-xl  dropdown-menu-right  py-0 overflow-hidden">
                <!-- Dropdown header -->
                <div class="px-3 py-3">
                  <h6 class="text-sm text-muted m-0">You have <strong class="text-primary">13</strong> notifications.</h6>
                </div>
                <!-- List group -->
                <div class="list-group list-group-flush">
                  <a href="#!" class="list-group-item list-group-item-action">
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
                  </a>
                </div>
                <!-- View all -->
                <a href="#!" class="dropdown-item text-center text-primary font-weight-bold py-3">View all</a>
              </div>
            </li>
            <li class="nav-item dropdown">
              <a class="nav-link" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="ni ni-ungroup"></i>
              </a>
              <div class="dropdown-menu dropdown-menu-lg dropdown-menu-dark bg-default  dropdown-menu-right ">
                <div class="row shortcuts px-4">
                  <a href="#!" class="col-4 shortcut-item">
                    <span class="shortcut-media avatar rounded-circle bg-gradient-red">
                      <i class="ni ni-calendar-grid-58"></i>
                    </span>
                    <small>Calendar</small>
                  </a>
                  <a href="#!" class="col-4 shortcut-item">
                    <span class="shortcut-media avatar rounded-circle bg-gradient-orange">
                      <i class="ni ni-email-83"></i>
                    </span>
                    <small>Email</small>
                  </a>
                  <a href="#!" class="col-4 shortcut-item">
                    <span class="shortcut-media avatar rounded-circle bg-gradient-info">
                      <i class="ni ni-credit-card"></i>
                    </span>
                    <small>Payments</small>
                  </a>
                  <a href="#!" class="col-4 shortcut-item">
                    <span class="shortcut-media avatar rounded-circle bg-gradient-green">
                      <i class="ni ni-books"></i>
                    </span>
                    <small>Reports</small>
                  </a>
                  <a href="#!" class="col-4 shortcut-item">
                    <span class="shortcut-media avatar rounded-circle bg-gradient-purple">
                      <i class="ni ni-pin-3"></i>
                    </span>
                    <small>Maps</small>
                  </a>
                  <a href="#!" class="col-4 shortcut-item">
                    <span class="shortcut-media avatar rounded-circle bg-gradient-yellow">
                      <i class="ni ni-basket"></i>
                    </span>
                    <small>Shop</small>
                  </a>
                </div>
              </div>
            </li> --}}
          </ul>
          <ul class="navbar-nav align-items-center  ml-auto ml-md-0 ">
            <li class="nav-item dropdown">
              <a class="nav-link pr-0" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <div class="media align-items-center">
                  <span>
                    {!! Auth::user()->avatarNav() !!}
                  </span>
                  <div class="media-body  ml-2  d-none d-lg-block">
                    <span class="mb-0 text-sm  font-weight-bold">{{ Auth::user()->name }}</span>
                  </div>
                </div>
              </a>
              {{-- <div class="dropdown-menu  dropdown-menu-right ">
                <div class="dropdown-header noti-title">
                  <h6 class="text-overflow m-0">Welcome!</h6>
                </div> --}}
                {{-- <a href="#!" class="dropdown-item">
                  <i class="ni ni-single-02"></i>
                  <span>My profile</span>
                </a>
                <a href="#!" class="dropdown-item">
                  <i class="ni ni-settings-gear-65"></i>
                  <span>Settings</span>
                </a>
                <a href="#!" class="dropdown-item">
                  <i class="ni ni-calendar-grid-58"></i>
                  <span>Activity</span>
                </a>
                <a href="#!" class="dropdown-item">
                  <i class="ni ni-support-16"></i>
                  <span>Support</span>
                </a>
                <div class="dropdown-divider"></div> --}}
                {{-- <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('frm-logout').submit();" class="dropdown-item">
                  <i class="ni ni-user-run"></i>
                  <span>Logout</span>
                </a>
                <form id="frm-logout" action="{{ route('logout') }}" method="POST" style="display: none;">
                  {{ csrf_field() }}
                </form> --}}

              {{-- </div> --}}

              @include('dropdown-menu')

            </li>
          </ul>
        </div>
      </div>
    </nav>
    <!-- Header -->
    <!-- Header -->
    <div class="header bg-primary pb-6">
      <div class="container-fluid">
        <div class="header-body">
          <div class="row align-items-center py-4">
            <div class="col-lg-6 col-7">
              <h6 class="h2 text-white d-inline-block mb-0">Help Desk</h6>
              <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4">
                <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                <li class="breadcrumb-item"><a href="{{ URL('tickets')}}"><i class="fas fa-home"></i></a></li>
                  {{-- <li class="breadcrumb-item"><a href="#">Tables</a></li> --}}
                  {{-- <li class="breadcrumb-item active" aria-current="page">Email Templates</li> --}}

                  @if ( Request::is('email-templates') )

                  <li class="breadcrumb-item active" aria-current="page">Email Templates</li>

                  @elseif ( Request::is('channels/email') )

                    <li class="breadcrumb-item active" aria-current="page">Email</li>

                  @elseif ( Request::is('channels/facebook') )

                    <li class="breadcrumb-item active" aria-current="page">Facebook</li>

                  @elseif ( Request::is('dashboard') )

                    <li class="breadcrumb-item active" aria-current="page">Dashboard</li>

                  @elseif ( Request::is('users') )

                    <li class="breadcrumb-item active" aria-current="page">Users</li>

                  @endif

                </ol>
              </nav>
            </div>
            <div class="col-lg-6 col-5 text-right">

              <span class="text-white mr-1" style="font-size: 14px;vertical-align: super;font-weight: 600;">Chat Available</span>
              <label class="custom-toggle">

                @if ( Auth::user()->is_online == true )

                  <input class="chat-available" type="checkbox" checked>

                @else
                    
                  <input class="chat-available" type="checkbox">

                @endif

                <span class="custom-toggle-slider rounded-circle bg-white" data-label-off="No" data-label-on="Yes"></span>
              </label>
              {{-- <a href="#" class="btn btn-sm btn-neutral">New</a> --}}
              {{-- <a href="#" class="btn btn-sm btn-neutral btnFilter" data-toggle="modal" data-target="#modalFilterTicket">Filters</a> --}}
            </div>
          </div>
        </div>
      </div>
    </div>

    @include('modal.view-searched-order-details')
    @include('modal.view-agent-performance-modal')
    @include('modal.preview-ticket-modal')
    <!-- Page content -->
    @yield('content')

  </div>

  <!-- Argon Scripts -->
  <!-- Core -->
    <!-- this overrides the popover functions in argon.js -->
  <script src="{{ asset('js/popper.min.js') }}"></script>
  <script src="{{ asset('js/jquery/dist/jquery.min.js') }}"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script src="{{ asset('js/select2totree.js') }}"></script>
  <script src="https://cdn.tiny.cloud/1/jp9hrx8k3czd9cd8wbkhwzxgxjwr7ymqq2yo7bc1bdxg0u4d/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
  <script src="{{ asset('js/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('js/dropzone-3.min.js').'?v='.time() }}"></script>

  <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
  <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

  @if( Request::is('export') )

  <script src="{{ asset('js/chart.js/dist/Chart.min.js') }}"></script>
  <script src="{{ asset('js/chart.js/dist/Chart.extension.js') }}"></script>
  {{-- <script src="{{ asset('js/argon-dashboard.min.js') }}"></script> --}}

  @endif

  <script src="{{ asset('js/custom.js').'?v='.time() }}"></script>

  <!-- Latest compiled and minified JavaScript -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>
  <script src="{{ asset('js/js-cookie/js.cookie.js') }}"></script>
  <script src="{{ asset('js/jquery.scrollbar/jquery.scrollbar.min.js') }}"></script>
  <script src="{{ asset('js/jquery-scroll-lock/dist/jquery-scrollLock.min.js') }}"></script>
  <script src="//cdn.jsdelivr.net/npm/sweetalert2@10"></script>
  <!-- Optional: include a polyfill for ES6 Promises for IE11 -->
  <script src="//cdn.jsdelivr.net/npm/promise-polyfill@8/dist/polyfill.js"></script>
  <!-- Argon JS -->
  <script src="{{ asset('js/argon.js?v='.time().'') }}"></script>



  <script type="text/javascript">
    var APP_URL         = '{{URL::to('/')}}';
    var AUTH_USER_NAME  = "{{ Auth::user()->name }}";
    var AUTH_USER_EMAIL = "{{ Auth::user()->email }}";
    var BLACK_EDGE_LOGO = '{{ asset('/images/black-edge-digital-logo.jpg') }}';
  </script>

  <script src="https://js.pusher.com/7.0/pusher.min.js"></script>
  <script type="text/javascript">
    var pusher = new Pusher('6b22d14c25a2e912298d', {
        encrypted: true,
        app_id: '1088021',
        secret: '638b4312a2f0c54ff3ba',
        cluster: 'ap1'
    });

    let $_chatMessagesDataWrapper      = $('.chat-messages-wrapper');
    let $_chatConversationsDataWrapper = $('.chat-conversations-wrapper');
    let activeChatId                   = $('.chat.chat-listing .message-block.active-conversation').data('chat-id');

    if ( typeof activeChatId == 'undefined' )
    {
      activeChatId = 0;
    }

    // Pusher.logToConsole = true;

    // Subscribe to the channel we specified in our Laravel Event
    var channel = pusher.subscribe('customer-messaged');

    // Bind a function to a Event (the full Laravel class)
    channel.bind('App\\Events\\CustomerMessaged', function(data) { 

      if ( $('.chatStatusFilter').val() == 1 ) //unassigned
      {

        if ( activeChatId == data.chat_id)
        {
          //sync active chat messages if the user receives a message on the active message content..
          syncActiveChatMessages(data.chat_id);
        }

        // add special condition when on unread message filter, do not re-render chat conversation
        if ( $('.chatStatusFilter').val() != 'unread' )
        {
          //sync chat conversations
          syncConversations(data.chat_id);
        }

        setTimeout(() => {

          $('.chat-content .chat-messages-wrapper').scrollTop($('.chat-content .chat-messages-wrapper')[0].scrollHeight);

        }, 200);

      }

      $.ajax({
          url:APP_URL+'/ajaxGetUnreadMessagesCount',
          method:"POST",
          success:function(data)
          {
              $('.unread-messages-count').html('<i class="fas fa-envelope"></i> ' + data.unreadMessagesCount);
          }
      });

      document.getElementById('chord-notification').muted = false;
      document.getElementById('chord-notification').play();

    });

    function syncActiveChatMessages(chat_id)
    {

      $.ajax({
        type:'POST',
        url:APP_URL+'/ajaxGetChatMessages',
        data: {
          chatId : chat_id
        },
        beforeSend: function() {
          // console.log(chat_id);
        },
        success:function(data){
          
          // console.log(data);
          $_chatMessagesDataWrapper.html(data);

          $('.chat-content .chat-messages-wrapper').scrollTop($('.chat-content .chat-messages-wrapper')[0].scrollHeight);

          //the users received a new message while still active on the chat, automatically set the message to read = true.
          $.ajax({
            type:'POST',
            url:APP_URL+'/ajaxSeenMessages',
            data: {
              chatId : chat_id
            },
            beforeSend: function() {
              // console.log(chat_id);
            },
            success:function(data){
              //the users received a new message while still active on the chat, automatically set it to read.
              // console.log(data);

            },
            error: function (error) {
            }

          });


        },
        error: function (error) {
        }

      });

    }

    function syncConversations(chat_id)
    {

      $.ajax({
        type:'POST',
        url:APP_URL+'/ajaxGetChatConversations',
        data: {
          chatId               : chat_id,
          setActiveConversation: false,
          activeChatId         : activeChatId,
          status_id            : $('.chatStatusFilter').val()
        },
        beforeSend: function() {
          // console.log('status_id: '+$('.chatStatusFilter').val());
        },
        success:function(data){
          
          // console.log(data);
          $_chatConversationsDataWrapper.html(data);

          let _chatRequester      = $('.chat-conversations-wrapper .active-conversation').data('chat-requester');
          let _chatRequesterEmail = $('.chat-conversations-wrapper .active-conversation').data('chat-requester-email');

          $('.chat-content .chat-requester').text( _chatRequester );
          $('.chat-content .chat-requester-email').text( _chatRequesterEmail );

          if ( $('.message-block.active-conversation').data('status-id') == 4 ) // closed
          {

            $('body').find('.chatStatusFilter [value='+$('.chatStatusFilter').val()+']').attr('selected', false);

            $('body').find('.chatStatusFilter [value=4]').attr('selected', true);

            $('.inputChatReply').attr('disabled', true);
            $('#end-chat').attr('disabled', true);
            $('.chatReplyBlock ').hide();
            $('.chat-messages-wrapper').css({'height' : '100%', 'padding-bottom' : '100px'});

            syncActiveChatMessages(chat_id);

            /*$.ajax({
                type:'POST',
                url:APP_URL+'/ajaxGetChatMessages',
                data: {
                    chatId : chatId
                },
                beforeSend: function() {
                    $('.chat-content .loader').show();
                    $('.chat-content .card-body').addClass('opacity-4');
                    $('.chat-conversation-status-wrapper .card-body').addClass('opacity-4');
                    $('.inputChatReply').val('');
                    $('.inputChatReply').attr('disabled', true);
                    $('#end-chat').attr('disabled', true);
                },
                success:function(data){

                    $_chatMessagesDataWrapper.html(data);
                    
                    //enable input chat box if there is a existing message
                    if ( !$('.chat-messages-wrapper .start-chat').length && !$('.agent-took-over-the-chat').length && !$('.agent-no-response').length )
                    {
                        $('.inputChatReply').attr('disabled', false);
                        $('#end-chat').attr('disabled', false);
                    }


                    // $('.chatRequesterName, .chat-content .chat-requester').text(eventTarget.currentTarget.dataset.chatRequester);
                    $('.chat-content .chat-requester').text(_chatRequester);
                    $('.chat-content .chat-requester-email').text(_chatRequesterEmail);

                    
                },
                error: function (error) {
                }
        
            });*/

          }

        },
        error: function (error) {
        }
  
      });

    }

    //receive unread message count
    // var _pusher = new Pusher('6b22d14c25a2e912298d', {
    //     encrypted: true,
    //     app_id: '1088021',
    //     secret: '638b4312a2f0c54ff3ba',
    //     cluster: 'ap1'
    // });

    // Subscribe to the channel we specified in our Laravel Event
    var _channel = pusher.subscribe('receive-unread-message-count');

    // Bind a function to a Event (the full Laravel class)
    _channel.bind('App\\Events\\ReceiveUnreadMessageCount', function(data) { 

      console.log(data.unreadMessageCount);
      var unreadMessagesCount = (typeof data.unreadMessageCount != 'undefined') ? data.unreadMessageCount : 0;
      $('.unread-messages-count').html('<i class="fas fa-envelope"></i> ' + unreadMessagesCount);

    });


    //set start chat
    // var startChatPusher = new Pusher('6b22d14c25a2e912298d', {
    //     encrypted: true,
    //     app_id: '1088021',
    //     secret: '638b4312a2f0c54ff3ba',
    //     cluster: 'ap1'
    // });

    // Subscribe to the channel we specified in our Laravel Event
    var _channel = pusher.subscribe('agent-start-chat');

    // Bind a function to a Event (the full Laravel class)
    _channel.bind('App\\Events\\AgentStartChat', function(data) { 

      // console.log('AgentStartChatID: ' + data.chatId);
      // console.log('AgentStartChatUserID: ' + data.userId);
      // console.log('auth-user-id: ' + $('.auth-user-id').val());
      
      //if the user is not the one who started chat. prevent from being able to send by showing msg at remain dsiable the chat inputs
      if ( $('.auth-user-id').val() != data.userId )
      {
        $('.start-chat[data-chat-id="'+data.chatId+'"]').remove();
        $('.chat-content .chat-messages-wrapper').append('<p class="mt-2 ml-2 agent-took-over-the-chat">An agent took over the chat.</p>');
      }
      else
      {
        //render to right ticket view
      }
      
    });

    //customer updated information
    var _channel = pusher.subscribe('customer-updated-information');

    // Bind a function to a Event (the full Laravel class)
    _channel.bind('App\\Events\\CustomerUpdatedInformation', function(data) { 

      // add special condition when on unread message filter, do not re-render chat conversation
      if ( $('.chatStatusFilter').val() != 'unread' )
      {
        //sync chat conversations
        syncConversations(activeChatId);
      }

      // $('.chat-content .chat-requester').text( $('.chat-conversations-wrapper .active-conversation').data('chat-requester') );
      // $('.chat-content .chat-requester-email').text( $('.chat-conversations-wrapper .active-conversation').data('chat-requester-email') );

    });


    //customer ended chat
    var _channel = pusher.subscribe('customer-ended-chat');

    // Bind a function to a Event (the full Laravel class)
    _channel.bind('App\\Events\\CustomerEndedChat', function(data) { 

      // add special condition when on unread message filter, do not re-render chat conversation
      if ( $('.chatStatusFilter').val() != 'unread' )
      {
        //sync chat conversations
        syncConversations(activeChatId);
      }

    });

    //agent no response
    var _channel = pusher.subscribe('agent-no-response');

    // Bind a function to a Event (the full Laravel class)
    _channel.bind('App\\Events\\AgentNoResponse', function(data) { 

      // add special condition when on unread message filter, do not re-render chat conversation
      if ( $('.chatStatusFilter').val() != 'unread' )
      {
        //sync chat conversations
        syncConversations(activeChatId);
      }

    });



  </script>

  {{-- Recent Addition --}}
  {{-- Google Platform Library --}}
  <script src="https://apis.google.com/js/platform.js?onload=renderButton" async defer></script>
  <script type="text/javascript">
    function onSuccess(googleUser) {
      console.log('Logged in as: ' + googleUser.getBasicProfile());
    }
    function onFailure(error) {
      console.log(error);
    }

    function signOut() {
      var auth2 = gapi.auth2.getAuthInstance();
      auth2.signOut().then(function () {
        console.log('User signed out.');
      });
    }
    function renderButton() {
      gapi.signin2.render('my-signin2', {
        'scope': 'profile email',
        'width': 240,
        'height': 50,
        'longtitle': true,
        'theme': 'dark',
        'onsuccess': onSuccess,
        'onfailure': onFailure
      });
    }

    //dropzone
    var totalsize   = 0.0;
    var maxFileSize = 25;
    Dropzone.options.imageUpload = {
        addRemoveLinks: true,
        maxFilesize: maxFileSize,
        accept: function(file, done) {
          if (totalsize >= maxFileSize) {
              file.status = Dropzone.CANCELED;
              this._errorProcessing([file],  "Max limit reached", null);
          }
          else
          { 
              done();
          }
        },
        init: function ()
        {
            this.on("removedfile", function (file)
            {
                // console.log(file);

                //$.post("delete-file.php?id=" + file.serverId); // Send the file id along
            });

            this.on("addedfile", function(file) { 
              totalsize += parseFloat((file.size / (1024*1024)).toFixed(2));
            });
            this.on("removedfile", function(file) {
              if(file.upload.progress != 0){
                totalsize -= parseFloat((file.size / (1024*1024)).toFixed(2));
              }
            });
            this.on("error", function(file) {
              totalsize -= parseFloat((file.size / (1024*1024)).toFixed(2));
            });

        },
        success: function(file, response){
          file.previewElement.lastElementChild.dataset.filename = response.filename;
          
          //on send message, loop through below to get current uploaded files
          // $('.dz-remove').each(function(){

          //   if ( typeof $(this).data('filename') != 'undefined' )
          //   {
          //       console.log( $(this).data('filename') );
          //   }

          // });
          // console.log(totalsize);
        },
    }

    Dropzone.options.imageUploadCompose = {
        addRemoveLinks: true,
        maxFilesize: maxFileSize,
        accept: function(file, done) {
          if (totalsize >= maxFileSize) {
              file.status = Dropzone.CANCELED;
              this._errorProcessing([file],  "Max limit reached", null);
          }
          else
          { 
              done();
          }
        },
        init: function ()
        {
            this.on("removedfile", function (file)
            {
                // console.log(file);

                //$.post("delete-file.php?id=" + file.serverId); // Send the file id along
            });

            this.on("addedfile", function(file) { 
              totalsize += parseFloat((file.size / (1024*1024)).toFixed(2));
            });
            this.on("removedfile", function(file) {
              if(file.upload.progress != 0){
                totalsize -= parseFloat((file.size / (1024*1024)).toFixed(2));
              }
            });
            this.on("error", function(file) {
              totalsize -= parseFloat((file.size / (1024*1024)).toFixed(2));
            });

        },
        success: function(file, response){
          file.previewElement.lastElementChild.dataset.filename = response.filename;
          
          //on send message, loop through below to get current uploaded files
          // $('.dz-remove').each(function(){

          //   if ( typeof $(this).data('filename') != 'undefined' )
          //   {
          //       console.log( $(this).data('filename') );
          //   }

          // });
          // console.log(totalsize);
        },
    }
  </script>

</body>

</html>