{{-- @extends('layouts.users') --}}
@extends('layouts.emailTemplates')

@section('content')

<meta name="csrf-token" content="{{ csrf_token() }}" />
    <!-- Page content -->

    <div class="container-fluid mt--6">

        {{-- chart --}}
        <div class="row">
          <div class="col-xl-9 mb-5 mb-xl-0">
            <div class="card bg-gradient-default shadow chart-card">

              <div class="loadOverlay" style="border-radius: 6px;">
                <img src="{{ asset('images/ajax-bar-loader.gif') }}">
              </div>

              <div class="card-header bg-transparent">
                <div class="row align-items-center">
                  <div class="col">
                    <h6 class="text-uppercase text-light ls-1 mb-1">Overview</h6>
                    <h2 class="text-white mb-0">Tickets</h2>
                    {{-- <span class="text-muted text-sm">by categories</span> --}}
                  </div>
                  <div class="col">
                    <ul class="nav nav-pills justify-content-end">
                      <li>
                        <input type="text" class="form-control-sm mr-3 mt-1" id="daterange-chartjs" placeholder="Date Range" autocomplete="off" style="color: #6b6b6b; border: 1px solid #d4d4d4;">
                      </li>
                      <li class="nav-item mr-2 mr-md-0 chart-select" data-action="current_week">
                        <a href="#" class="nav-link py-2 px-3 active" data-toggle="tab">
                          <span class="d-none d-md-block">Current Week</span>
                          <span class="d-md-none">CW</span>
                        </a>
                      </li>
                      <li class="nav-item chart-select" data-action="last_week">
                        <a href="#" class="nav-link py-2 px-3" data-toggle="tab">
                          <span class="d-none d-md-block">Last Week</span>
                          <span class="d-md-none">LW</span>
                        </a>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
              <div class="card-body">
                <!-- Chart -->
                <div class="chart chart-tickets-wrapper">
                  <!-- Chart wrapper -->
                  <canvas id="chart-tickets" class="chart-canvas"></canvas>
                </div>
              </div>
            </div>
          </div>

          <div class="col-xl-3 mb-5 mb-xl-0">
            <div class="card shadow">
              <div class="card-header bg-transparent">
                <h3 class="mb-0">Tickets</h3>
                <span class="text-muted text-sm">by categories</span>
              </div>
              <div class="card-body">
                <div class="row hover-data-display-wrapper" style="max-height: 350px; overflow: auto;">
                </div>
              </div>
            </div>
          </div>
        </div>
        {{-- end chart --}}
        
        <div class="row">

          <div class="col">

            <div class="row">
              
              <div class="col">

                <div class="ticket-alerts">
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <span class="alert-text"></span>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <span class="alert-text"></span>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
  
                <div class="user-performance-table-data-wrapper">
                    {{-- agents table data --}}

                    <div class="card">
                      <div class="loadOverlay">
                          <img src="{{ asset('images/ajax-bar-loader.gif') }}">
                      </div>
                      <!-- Card header -->
                      <div class="card-header border-0 rounded">
                          <div class="float-left">
                              <h3 class="mb-0">Agents</h3>
                          </div>
                          <div class="float-right">

                              <div class="search-agents-block">

                                {{-- <div class="mr-3" style="display: inline;">
                                  <input class="form-check-input emailReport mt-2" type="checkbox" value="" id="flexCheckDefault">
                                  <label class="form-check-label text-sm" for="flexCheckDefault">
                                    Email
                                  </label>
                                </div> --}}

                                <input type="text" class="form-control-sm mr-3" id="daterange-export" placeholder="Date Range" autocomplete="off">

                                <a href="/export/agent_performance?userIds={{ Auth::id() }}&dateRange={{ \Carbon\Carbon::now()->format('m-d-Y') }} - {{ \Carbon\Carbon::now()->format('m-d-Y') }}" target="_blank" data-user-id="{{ Auth::id() }}" class="btn btn-icon btn-primary btn-sm export-my-performance text-white">
                                  <span class="btn-inner--icon"><i class="fas fa-file-download"></i></i></span>
                                  <span class="btn-inner--text ml-0">My Performance</span>
                                </a>

                                {{-- @if(Auth::id() == 1 || Auth::id() == 5)

                                  <a href="" target="_blank" data-user-id="{{ Auth::id() }}" class="btn btn-icon btn-primary btn-sm export-users-performance-summary text-white">
                                    <span class="btn-inner--icon"><i class="fas fa-users"></i></span>
                                    <span class="btn-inner--text ml-0">Export Performance Summary</span>
                                  </a>

                                @endif --}}

                                {{-- <a href="" target="_blank" data-user-id="{{ Auth::id() }}" class="btn btn-icon btn-primary btn-sm export-selected-user-performance text-white">
                                  <span class="btn-inner--icon"><i class="fas fa-users"></i></span>
                                  <span class="btn-inner--text ml-0">Export Selected</span>
                                </a> --}}

                                <div class="dropdown export-actions disabled">
                                  <a href="#" class="btn dropdown-toggle btn btn-sm btn-primary" data-toggle="dropdown" id="navbarDropdownMenuLink2" aria-expanded="false">
                                    <i class="fas fa-file mr-2"></i>Export
                                  </a>
                                  <ul class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink2" style="">

                                    @if(Auth::id() == 1 || Auth::id() == 5) {{-- miss HR or dev rdc--}}

                                      <li>
                                        <a class="dropdown-item export-users-performance-summary" href="#">
                                          <i class="fas fa-users mr-2"></i>Performance Summary
                                        </a>
                                      </li>

                                    @endif

                                    <li>
                                      <a class="dropdown-item export-selected-user-performance" href="#">
                                        <i class="fas fa-download mr-2"></i>Download Export
                                      </a>
                                    </li>
                                    <li>
                                      <a class="dropdown-item export-view-report" href="#">
                                        <i class="fa fa-paper-plane mr-2" aria-hidden="true"></i>Email Report
                                      </a>
                                    </li>
                                  </ul>
                                </div>

                                <a href="" target="_blank" class="btn btn-icon btn-primary btn-sm view-selected-agents-performance text-white">
                                  <span class="btn-inner--icon"><i class="fas fa-users"></i></span>
                                  <span class="btn-inner--text ml-0">View Users Report</span>
                                </a>

                                {{-- @if ( Auth::user()->rolesByIdExists([\App\Role::MANAGER, \App\Role::ADMIN, \App\Role::DEVELOPER]) )

                                  <select class="form-control-sm" id="select-agents" multiple></select>
                                  <a href="#" class="btn btn-primary btn-sm export-selected-agents" data-toggle="tooltip" title="Export">
                                    <i class="fas fa-file-export"></i>
                                  </a>

                                @endif --}}

                              </div>

                          </div>
                      </div>
                      <!-- Light table -->
                      <div class="table-responsive">

                          <table class="table align-items-center table-flush email-templates-listing">
                              <thead class="thead-light">
                              <tr>
                                  <th class="text-right"><input class="form-check-input mt--5 agent-check-all" type="checkbox"></th>
                                  <th scope="col text-left">Name</th>
                                  <th scope="col text-left">Email</th>
                                  <th scope="col text-center" class="text-center" width="250">Action</th>
                              </tr>
                              </thead>
                              <tbody class="list">

                              @php
                                  $footerStartPageRowCount = $startPageRowCount = ($users->currentPage() - 1) * $users->perPage();
                              @endphp

                              @forelse($users as $user)

                                  @php
                                      $startPageRowCount++;
                                  @endphp

                                  <tr>
                                      <td class="text-right">
                                        <input class="form-check-input mt--5 agent-checkbox" data-ticket-id="{{ $user->id }}" type="checkbox">
                                      </td>

                                      <td>{{ $user->name }}</td>

                                      <td>{{ $user->email }}</td>

                                      <td class="text-center">
                                          <a target="_blank" href="/export/agent_performance?userIds={{ $user->id }}&dateRange={{ \Carbon\Carbon::now()->format('m-d-Y') }} - {{ \Carbon\Carbon::now()->format('m-d-Y') }}" class="btn btn-secondary btn-sm btn-export-report" data-user-id="{{ $user->id }}" data-toggle="tooltip" title="Export Report">
                                              <i class="fas fa-file-export"></i>
                                          </a>

                                          <a href="#" class="btn btn-secondary btn-sm view-agent-performance" data-user-id="{{ $user->id }}" data-toggle="tooltip" title="View Report">
                                            <i class="fas fa-eye"></i>
                                          </a>
                                      </td>
                                  </tr>

                              @empty
                                  <tr>
                                      <td colspan="3">No records found.</td>
                                  </tr>
                                  
                              @endforelse

                              </tbody>
                          </table>

                      </div>
                      <!-- Card footer -->
                      <div class="card-footer py-4">

                          <div class="row">
                              <div class="col-md-6">
                                  <span class="show-no-entries">Showing {{ $footerStartPageRowCount += 1 }} to 

                                      @if($users->currentPage() !== $users->lastPage())
                                          {{ $users->perPage() * $users->currentPage() }}
                                      @else
                                       {{ $users->total() }}
                                      @endif
                      
                                      of {{ $users->total() }} entries</span>
                              </div>

                              <div class="col-md-6">
                                  <div class="float-right pagination justify-content-end mb-0">
                                      {!! $users->onEachSide(2)->links() !!}
                                  </div>
                              </div>

                          </div>
                          
                      </div>
                  </div>

                    {{-- end agents table data --}}
                </div>
                
              </div>
              
            </div>
            
            <div class="row">
              
              <div class="col">

                <div class="settings-page-alerts">
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <span class="alert-text"></span>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <span class="alert-text"></span>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                </div>
  
              </div>

            </div>
            
          </div>

        </div>

        {{-- <div class="row">

            <div class="col">

              <div class="ticket-alerts">
                  <div class="alert alert-success alert-dismissible fade show" role="alert">
                      <span class="alert-text"></span>
                      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                      </button>
                  </div>
                  
                  <div class="alert alert-danger alert-dismissible fade show" role="alert">
                      <span class="alert-text"></span>
                      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                      </button>
                  </div>
              </div>

              <div class="signatures-table-data-wrapper">
                  @include('users.signatures_table_data')
              </div>
              
            </div>

            <div class="col">

              <div class="settings-page-alerts">
                  <div class="alert alert-success alert-dismissible fade show" role="alert">
                      <span class="alert-text"></span>
                      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                          <span aria-hidden="true">×</span>
                      </button>
                  </div>
                  
                  <div class="alert alert-danger alert-dismissible fade show" role="alert">
                      <span class="alert-text"></span>
                      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                          <span aria-hidden="true">×</span>
                      </button>
                  </div>
              </div>

              <div class="user-pages-table-data-wrapper">
                  @include('users.custom_pages_table_data')
              </div>

            </div>

        </div> --}}

    </div>

    <!-- Footer -->
    <div class="container-fluid mt-6 footer-container">
        <footer class="footer pt-0">
            <div class="row align-items-center justify-content-lg-between">
                <div class="col-lg-6">
                    <div class="copyright text-center  text-lg-left  text-muted">
                    &copy; 2020 <a href="#" class="font-weight-bold ml-1" target="_blank">Black Edge Digital</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>




@endsection