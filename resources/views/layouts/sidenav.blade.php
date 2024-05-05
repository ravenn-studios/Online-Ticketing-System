  <!-- Sidenav -->
  <nav class="sidenav navbar navbar-vertical  fixed-left  navbar-expand-xs navbar-light bg-white" id="sidenav-main">
    <div class="scrollbar-inner">
      <!-- Brand -->
      <div class="sidenav-header d-flex align-items-center">
        
        <a class="navbar-brand" href="javascript:void(0)">
          <img src="{{ asset('images/black-edge-logo.png') }}" class="navbar-brand-img" alt="..." style="max-height: 3.2rem;">
        </a>
        
        <div class="ml-auto">
          <div class="sidenav-toggler d-none d-xl-block" data-action="sidenav-unpin" data-target="#sidenav-main">
            <div class="sidenav-toggler-inner">
              <i class="sidenav-toggler-line"></i>
              <i class="sidenav-toggler-line"></i>
              <i class="sidenav-toggler-line"></i>
            </div>
          </div>
        </div>

      </div>

      <div class="navbar-inner">
        <!-- Collapse -->
        <div class="collapse navbar-collapse" id="sidenav-collapse-main">
          <!-- Nav items -->
          <ul class="navbar-nav">
            {{-- <li class="nav-item">
              <a class="nav-link" href="dashboard.html">
                <i class="ni ni-tv-2 text-primary"></i>
                <span class="nav-link-text">Dashboard</span>
              </a>
            </li> --}}
            {{-- <li class="nav-item">
              <a class="nav-link" href="icons.html">
                <i class="ni ni-planet text-orange"></i>
                <span class="nav-link-text">Icons</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="map.html">
                <i class="ni ni-pin-3 text-primary"></i>
                <span class="nav-link-text">Google</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="profile.html">
                <i class="ni ni-single-02 text-yellow"></i>
                <span class="nav-link-text">Profile</span>
              </a>
            </li> --}}

            {{-- @if ( Auth::user()->roles->first()->id !== \App\Role::AGENT ) --}}

            {{-- @if ( \App\Role::AGENT && Auth::user()->id !== 16 ) <!--16 = test account --> --}}
            @if ( in_array(Auth::user()->roles->first()->id, [\App\Role::ADMIN, \App\Role::MANAGER, \App\Role::DEVELOPER]) && Auth::user()->id !== 16 ) <!--16 = test account -->

            <li class="nav-item">
              <a class="nav-link {{ Request::is('dashboard') ? 'active' : '' }}" href="{{ url('dashboard') }}">
                <i class="ni ni-tv-2 text-primary"></i>
                <span class="nav-link-text">Dashboard</span>
              </a>
            </li>
            
            @endif
            
            {{-- @if ( Auth::user()->roles->first()->id !== \App\Role::AGENT && Auth::user()->id !== 16 ) <!--16 = test account --> --}}
            @if ( in_array(Auth::user()->roles->first()->id, [\App\Role::ADMIN, \App\Role::MANAGER, \App\Role::DEVELOPER]) && Auth::user()->id !== 16 ) <!--16 = test account -->

            <li class="nav-item">
              <a class="nav-link {{ Request::is('users') ? 'active' : '' }}" href="{{ url('users') }}">
                <i class="fas fa-user-friends"></i>
                <span class="nav-link-text">Users</span>
              </a>
            </li>

            @endif

            {{-- <li class="nav-item">
              <a class="nav-link {{ Request::is('tickets') ? 'active' : '' }}" href="{{ url('tickets') }}">
                <i class="ni ni-bullet-list-67 text-default"></i>
                <span class="nav-link-text">Tickets</span>
              </a>
            </li> --}}

            @php
                $ticketModel = new \App\Ticket;
                $categories  = \App\Category::where('parent_category_id', '<', 9)->orderBy('id', 'ASC')->get();
            @endphp

            {{-- <li class="nav-item dropdown {{ Request::is('tickets') || Request::is('tickets/*') ? 'show' : '' }}"> --}}
            <li class="nav-item dropdown show">
              <a class="nav-link dropdown-toggle {{ Request::is('tickets') || Request::is('tickets/*') ? 'active' : '' }}" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="ni ni-bullet-list-67 text-default"></i>
                <span class="nav-link-text">Views</span>
              </a>
              {{-- <div aria-labelledby="navbarDropdown" class="dropdown-menu {{ Request::is('tickets') || Request::is('tickets/*') ? 'show' : '' }}"> --}}
              <div aria-labelledby="navbarDropdown" class="dropdown-menu show">
                {{-- <a class="dropdown-item nav-link {{ Request::is('tickets') ? 'active' : '' }}" href="{{ url('tickets') }}">List</a> --}}

                {{-- @if ( in_array(Auth::user()->roles->first()->id, [\App\Role::ADMIN, \App\Role::DEVELOPER]) ) --}}

                  <a class="dropdown-item nav-link {{ Request::is('tickets/awaiting-fulfillment') ? 'active' : '' }}" href="{{ url('tickets/awaiting-fulfillment') }}">
                    Awaiting Fulfillment
                    <span class="badge badge-warning ml-1 ticket-counter awaiting-fulfillment-ctr"></span>
                  </a>

                  <a class="dropdown-item nav-link {{ Request::is('tickets/awaiting-shipment') ? 'active' : '' }}" href="{{ url('tickets/awaiting-shipment') }}">
                    Awaiting Shipment
                    <span class="badge badge-warning ml-1 ticket-counter awaiting-shipment-ctr"></span>
                  </a>

                {{-- @endif --}}

                @if ( \App\Role::MANAGER && \App\Role::AGENT && Auth::user()->id !== 16 ) <!--16 = test account -->
                  <a class="dropdown-item nav-link {{ Request::is('tickets/needs-urgent-attention') ? 'active' : '' }}" href="{{ url('tickets/needs-urgent-attention') }}">
                    Needs Urgent Attention
                    {{-- <span class="badge badge-warning ml-1 ticket-counter needs-urgent-attention-ctr">{{ $ticketModel->count_tickets_needs_urgent_attention() }}</span> --}}
                    <span class="badge badge-warning ml-1 ticket-counter needs-urgent-attention-ctr"></span>
                  </a>
                  <a class="dropdown-item nav-link {{ Request::is('tickets/over-4-hours') ? 'active' : '' }}" href="{{ url('tickets/over-4-hours') }}">
                    Tickets Over 4 Hours
                    {{-- <span class="badge badge-warning ml-1 ticket-counter over-four-hours-ctr">{{ $ticketModel->count_tickets_over_four_hours() }}</span> --}}
                    <span class="badge badge-warning ml-1 ticket-counter over-four-hours-ctr"></span>
                  </a>
                  <a class="dropdown-item nav-link {{ Request::is('tickets/under-4-hours') ? 'active' : '' }}" href="{{ url('tickets/under-4-hours') }}">
                    Tickets Under 4 Hours
                    {{-- <span class="badge badge-warning ml-1 ticket-counter under-four-hours-ctr">{{ $ticketModel->count_tickets_under_four_hours() }}</span> --}}
                    <span class="badge badge-warning ml-1 ticket-counter under-four-hours-ctr"></span>
                  </a>
                  
                  <div class="dropdown-divider"></div>

                  <a class="dropdown-item nav-link {{ Request::is('tickets/unassigned') ? 'active' : '' }}" href="{{ url('tickets/unassigned') }}">
                    Unassigned
                    {{-- <span class="badge badge-warning ml-1 ticket-counter unassigned-tickets-ctr">{{ $ticketModel->count_tickets_unassigned() }}</span> --}}
                    <span class="badge badge-warning ml-1 ticket-counter unassigned-tickets-ctr"></span>
                  </a>

                  <div aria-labelledby="navbarDropdown2" class="dropdown-menu show">

                      <a class="dropdown-item nav-link {{ Request::is('tickets/unassigned/new-orders') ? 'active' : '' }}" href="{{ url('tickets/unassigned/new-orders') }}" style="border-left: 2px solid #d5d5d5;">
                        New Orders
                      </a>

                  </div>

                  <a class="dropdown-item nav-link {{ Request::is('tickets/my-tickets') ? 'active' : '' }}" href="{{ url('tickets/my-tickets') }}">
                    My Tickets
                    {{-- @if ( Auth::user()->rolesByIdExists([\App\Role::MANAGER]) )
                      <span class="badge badge-warning ml-1 ticket-counter">{{ $ticketModel->count_tickets_needs_urgent_attention() }}</span>
                    @else --}}
                      {{-- <span class="badge badge-warning ml-1 ticket-counter my-tickets-ctr">{{ $ticketModel->count_my_tickets() }}</span> --}}
                      <span class="badge badge-warning ml-1 ticket-counter my-tickets-ctr"></span>
                    {{-- @endif --}}
                  </a>

                  @if( $categories->count() )

                    <div aria-labelledby="navbarDropdown2" class="dropdown-menu show">

                      @foreach( $categories as $key => $category )

                        <a class="dropdown-item nav-link {{ Request::is('tickets/category/'.$category->slug.'') ? 'active' : '' }}" href="{{ url('tickets/category/'.$category->slug.'') }}" style="border-left: 2px solid #d5d5d5;">
                          {{ $category->name }}
                          {{-- <span class="badge badge-warning ml-1 ticket-counter my-tickets-ctr"></span> --}}
                        </a>

                      @endforeach

                    </div>

                  @endif

                  <a class="dropdown-item nav-link {{ Request::is('tickets/important') ? 'active' : '' }}" href="{{ url('tickets/important') }}">
                    Important
                    <span class="badge badge-warning ml-1 ticket-counter my-important-tickets-ctr"></span>
                  </a>

                  <div class="dropdown-divider"></div>

                  {{-- enable view for admins, managers, developers, anne, and karina --}}
                  @if ( (Auth::user()->rolesByIdExists([\App\Role::MANAGER, \App\Role::ADMIN, \App\Role::DEVELOPER]) || Auth::id() == 9 || Auth::id() == 21) && (Auth::id() != 25 && Auth::id() != 26 && Auth::id() != 28 && Auth::id() != 29) ) 

                    <a class="dropdown-item nav-link {{ Request::is('my-agent-tickets') ? 'active' : '' }}" href="{{ url('my-agent-tickets') }}">
                      Users
                      {{-- <span class="badge badge-warning ml-1 ticket-counter all-agents-ctr">{{ \App\User::allAgents()->count() }}</span> --}}
                      <span class="badge badge-warning ml-1 ticket-counter all-agents-ctr"></span>
                    </a>

                  @endif
                  {{-- <a class="dropdown-item nav-link {{ Request::is('tickets/unassigned') ? 'active' : '' }}" href="{{ url('tickets/unassigned') }}">Unassigned</a> --}}
                  
                  {{-- @if ( Auth::user()->roles->first()->id !== \App\Role::AGENT )
                    <a class="dropdown-item nav-link {{ Request::is('tickets/pending') ? 'active' : '' }}" href="{{ url('tickets/pending') }}">Pending</a>
                  @endif --}}
                  
                  <a class="dropdown-item nav-link {{ Request::is('tickets/solved') ? 'active' : '' }}" href="{{ url('tickets/solved') }}">
                    Recently Solved Tickets
                    {{-- <span class="badge badge-warning ml-1 ticket-counter recently-solved-ctr">{{ $ticketModel->count_recently_solved_tickets() }}</span> --}}
                    <span class="badge badge-warning ml-1 ticket-counter recently-solved-ctr"></span>
                  </a>
                  <a class="dropdown-item nav-link {{ Request::is('tickets/closed') ? 'active' : '' }}" href="{{ url('tickets/closed') }}">
                    Recently Closed Tickets
                    {{-- <span class="badge badge-warning ml-1 ticket-counter recently-closed-ctr">{{ $ticketModel->count_recently_closed_tickets() }}</span> --}}
                    <span class="badge badge-warning ml-1 ticket-counter recently-closed-ctr"></span>
                  </a>
                  <a class="dropdown-item nav-link {{ Request::is('tickets/sent') ? 'active' : '' }}" href="{{ url('tickets/sent') }}">
                    Sent
                  </a>

                  @if(Auth::id() == 1 )
                  <a class="dropdown-item nav-link {{ Request::is('tickets/spam') ? 'active' : '' }}" href="{{ url('tickets/spam') }}">
                    Spam
                  </a>
                  @endif
                    {{-- <span class="badge badge-warning ml-1 ticket-counter">{{ $ticketModel->count_recently_closed_tickets() }}</span> --}}
                @endif
                {{-- <div class="dropdown-divider"></div> --}}
                {{-- <a class="dropdown-item" href="#">Something else here</a> --}}

                @php
                    $user             = Auth::user();
                    $users            = \App\User::all();
                    $userModel        = new \App\User;
                    $onlineUsersCount = $userModel->getUsersOnlineCount();
                    $fromEbayCtr      = 0;
                @endphp

                {{-- @foreach ( $users as $_user )

                  @if ( Cache::has('user-is-online-' . $_user->id) )

                    @php
                      $onlineUsersCount++;
                    @endphp

                  @endif

                @endforeach --}}

                @if ( \App\Role::AGENT && Auth::user()->id !== 16 ) <!--16 = test account -->
                  @foreach ( $user->customPages as $userPage )
                    @if ($loop->index == 0)
                      <div class="dropdown-divider"></div>
                    @endif

                    @php
                        $ticketModel = new \App\Ticket;
                    @endphp

                    {{-- @if ( $userPage->name == 'Ebay' )

                      <li class="nav-item dropdown {{ Request::is('tickets/custom/ebay') || Request::is('tickets/custom/from-ebay') ? 'show' : '' }}">
                        <a class="nav-link dropdown-toggle {{ Request::is('tickets/custom/ebay') || Request::is('tickets/custom/from-ebay') ? 'active' : '' }}" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                          <i class="ni ni-app"></i>
                          <span class="nav-link-text">eBay</span>
                        </a>
                        <div aria-labelledby="navbarDropdown" class="dropdown-menu {{ Request::is('tickets/custom/ebay') || Request::is('tickets/custom/from-ebay') ? 'show' : '' }}">

                          <a class="dropdown-item nav-link {{ Request::is('tickets/custom/ebay') ? 'active' : '' }}" href="{{ url('tickets/custom/ebay') }}">All Messages <span class="badge badge-warning ml-1 ticket-counter">{{ $ticketModel->count_custom_page_tickets($userPage->slug) }}</span></a>
                          <a class="dropdown-item nav-link {{ Request::is('tickets/custom/from-ebay') ? 'active' : '' }}" href="{{ url('tickets/custom/from-ebay') }}">From eBay <span class="badge badge-warning ml-1 ticket-counter">{{ $ticketModel->count_custom_page_tickets('from-ebay') }}</span></a>
                        </div>
                      </li>

                    @else --}}

                      <a class="dropdown-item nav-link {{ Request::is('tickets/custom/'.$userPage->slug.'') ? 'active' : '' }}" href="{{ url('tickets/custom/'.$userPage->slug.'') }}">
                        {{ (strtolower($userPage->name) == 'ebay') ? 'All eBay Messages' : $userPage->name }}

                        <span class="badge badge-warning ml-1 ticket-counter {{ (strtolower($userPage->name) == 'ebay') ? 'ebay-ctr' : '' }}">{{ (strtolower($userPage->name) != 'ebay') ? $ticketModel->count_custom_page_tickets($userPage->slug) : '' }}</span>
                      </a>

                      @if ( strpos(strtolower($userPage->name), 'ebay') !== false && !$fromEbayCtr )

                        @php $fromEbayCtr++ @endphp

                        <a class="dropdown-item nav-link {{ Request::is('tickets/custom/from-ebay') ? 'active' : '' }}" href="{{ url('tickets/custom/from-ebay') }}">
                          From eBay

                          {{-- <span class="badge badge-warning ml-1 ticket-counter from-ebay-ctr">{{ $ticketModel->count_custom_page_tickets('from-ebay') }}</span> --}}
                          <span class="badge badge-warning ml-1 ticket-counter from-ebay-ctr"></span>
                        </a>

                      @endif

                    {{-- @endif --}}
                  
                  @endforeach

                  <div class="dropdown-divider"></div>
                @endif

                {{-- <a class="facebook-chat-link dropdown-item nav-link {{ Request::is('chat/facebook') ? 'active' : '' }}" href="{{ url('chat/facebook') }}">
                  Facebook
                </a> --}}

                @if ( \App\Role::AGENT && Auth::user()->id !== 16 ) <!--16 = test account -->
                <a class="chats-link dropdown-item nav-link {{ Request::is('chats') ? 'active' : '' }}" href="{{ url('chats') }}">
                  Chats
                  <span class="badge badge-default online-users-count"><i class="fas fa-user-friends"></i> {{ $onlineUsersCount }}</span>
                  <span class="badge badge-danger unread-messages-count"><i class="fa fa-envelope"></i></span>
                </a>
                @endif

                <div class="dropdown-divider"></div>
              </div>
            </li>

            {{-- @if ( Auth::user()->roles->first()->id !== \App\Role::AGENT ) --}}

            @if ( Auth::user()->id !== 16 ) <!--16 = test account -->
              <li class="nav-item">
                <a class="nav-link {{ Request::is('email-templates') ? 'active' : '' }}" href="{{ url('email-templates') }}">
                  <i class="ni ni-email-83 text-default"></i>
                  <span class="nav-link-text">Email Templates</span>
                </a>
              </li>
            @endif

            {{-- @endif --}}

            @if ( Auth::user()->roles->first()->id !== \App\Role::AGENT && Auth::user()->id !== 22 && Auth::user()->id !== 23 && Auth::user()->id !== 24 ) <!-- exclude for ram and trevor for now 22,23 -->

            @if ( \App\Role::AGENT && Auth::user()->id !== 16 ) <!--16 = test account -->
              <li class="nav-item dropdown {{ Request::is('channels') || Request::is('channels/*') ? 'show' : '' }}">
                <a class="nav-link dropdown-toggle {{ Request::is('channels') || Request::is('channels/*') ? 'active' : '' }}" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="fas fa-th"></i>
                  <span class="nav-link-text">Channels</span>
                </a>
                <div aria-labelledby="navbarDropdown" class="dropdown-menu {{ Request::is('channels') || Request::is('channels/*') ? 'show' : '' }}">
                  {{-- <a class="dropdown-item nav-link {{ Request::is('tickets') ? 'active' : '' }}" href="{{ url('tickets') }}">List</a> --}}
                  @if ( \App\Role::AGENT && Auth::user()->id !== 16 ) <!--16 = test account -->
                    <a class="dropdown-item nav-link {{ Request::is('channels/email') ? 'active' : '' }}" href="{{ url('channels/email') }}">Email</a>
                  @endif
                  {{-- <a class="dropdown-item nav-link {{ Request::is('tickets/unassigned') ? 'active' : '' }}" href="{{ url('tickets/unassigned') }}">Unassigned</a> --}}
                  {{-- <a class="dropdown-item nav-link {{ Request::is('channels/facebook') ? 'active' : '' }}" href="{{ url('channels/facebook') }}">Facebook</a> --}}
                  {{-- <a class="dropdown-item nav-link {{ Request::is('channels/ebay') ? 'active' : '' }}" href="{{ url('channels/ebay') }}">Ebay</a> --}}
                  {{-- <div class="dropdown-divider"></div> --}}
                  {{-- <a class="dropdown-item" href="#">Something else here</a> --}}
                  <div class="dropdown-divider"></div>
                </div>
              </li>
            @endif

            @endif

            {{-- <li class="nav-item">
              <a class="nav-link" href="login.html">
                <i class="ni ni-key-25 text-info"></i>
                <span class="nav-link-text">Login</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="register.html">
                <i class="ni ni-circle-08 text-pink"></i>
                <span class="nav-link-text">Register</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="upgrade.html">
                <i class="ni ni-send text-dark"></i>
                <span class="nav-link-text">Upgrade</span>
              </a>
            </li> --}}
          </ul>
          {{-- <!-- Divider -->
          <hr class="my-3">
          <!-- Heading -->
          <h6 class="navbar-heading p-0 text-muted">
            <span class="docs-normal">Documentation</span>
          </h6>
          <!-- Navigation -->
          <ul class="navbar-nav mb-md-3">
            <li class="nav-item">
              <a class="nav-link" href="https://demos.creative-tim.com/argon-dashboard/docs/getting-started/overview.html" target="_blank">
                <i class="ni ni-spaceship"></i>
                <span class="nav-link-text">Getting started</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="https://demos.creative-tim.com/argon-dashboard/docs/foundation/colors.html" target="_blank">
                <i class="ni ni-palette"></i>
                <span class="nav-link-text">Foundation</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="https://demos.creative-tim.com/argon-dashboard/docs/components/alerts.html" target="_blank">
                <i class="ni ni-ui-04"></i>
                <span class="nav-link-text">Components</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="https://demos.creative-tim.com/argon-dashboard/docs/plugins/charts.html" target="_blank">
                <i class="ni ni-chart-pie-35"></i>
                <span class="nav-link-text">Plugins</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link active active-pro" href="upgrade.html">
                <i class="ni ni-send text-dark"></i>
                <span class="nav-link-text">Upgrade to PRO</span>
              </a>
            </li>
          </ul> --}}
        </div>
      </div>
    </div>
  </nav>