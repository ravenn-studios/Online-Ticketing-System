@extends('layouts.users')

@section('content')

<meta name="csrf-token" content="{{ csrf_token() }}" />
    <!-- Page content -->

    <div class="container-fluid mt--6">

        <!-- Modal -->
        @include('modal.create-custom-page-modal')

        @include('modal.update-custom-page-modal')

        @include('modal.create-signature-modal')
        
        @include('modal.update-signature-modal')

        @include('modal.update-user-info-modal')

        @include('modal.modal-change-password')

        @include('modal.update-user-ticket-limit-modal')

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

                {{-- <div class="users-ticket-limit-table-data-wrapper">
                    @include('users.user_reminders')
                </div> --}}

                <div class="users-ticket-limit-table-data-wrapper">
                    @include('users.user_ticket_limit')
                </div>
  
                {{-- <div class="signatures-table-data-wrapper">
                    @include('users.signatures_table_data')
                </div> --}}
                
              </div>
              
            </div>
            
            {{-- <div class="row">
              
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

            {{-- <div class="row">
              <div class="col">
                <div class="card">
                  <div class="card-header">
                    <h3>Auto Distribute Tickets</h3>
                  </div>
                  <div class="card-body">
                    <div class="custom-control custom-checkbox">
                      <input type="checkbox" class="custom-control-input tickets-auto-distribution" id="customCheck1" {{ ($autoTicketDistribution) ? 'checked' : '' }}>
                      <label class="custom-control-label tickets-auto-distribution" for="customCheck1">Enable Auto Distribution</label>
                    </div>
                  </div>
                </div>
              </div>
            </div> --}}
            
          </div>

          <div class="col-lg-3">

              <div class="card card-profile">
                  <img src="{{ URL('/').'/images/skyscraper.png' }}" alt="Image placeholder" class="card-img-top">
                  <div class="row justify-content-center">
                    <div class="col-lg-3 order-lg-2">
                      <div class="card-profile-image">
                        <a href="#">
                          {{-- <img src="{{ URL('/').'/images/user.png' }}" class="rounded-circle"> --}}
                          {!! Auth::user()->roundedAvatar() !!}
                        </a>
                      </div>
                    </div>
                  </div>
                  <div class="card-header text-center border-0 pt-8 pt-md-4 pb-0 pb-md-4">
                    <div class="d-flex justify-content-between">
                      {{-- <a href="#" class="btn btn-sm btn-info  mr-4 ">Connect</a> --}}
                      <a href="#" class="btn btn-sm btn-default btn-upload-avatar float-right" data-toggle="modal" data-target="#modalUpdateuserInfo">Update</a>
                    </div>
                  </div>
                  <div class="card-body pt-0 mt-2">
                    <div class="text-center">
                      <h5 class="h3">
                        {{ Auth::user()->name }}
                      </h5>
                      <div class="h5 font-weight-500">
                        <i class="ni location_pin mr-2"></i>{{ Auth::user()->email }}
                      </div>
                      {{-- <div class="h5 mt-4">
                        <i class="ni business_briefcase-24 mr-2"></i>Solution Manager - Creative Tim Officer
                      </div>
                      <div>
                        <i class="ni education_hat mr-2"></i>University of Computer Science
                      </div> --}}
                    </div>
                  </div>
                </div>

          </div>

        </div>

        <div class="row">

          {{-- <div class="col-lg-7">
            <div class="users-ticket-limit-table-data-wrapper">
                @include('users.user_ticket_limit')
            </div>
          </div> --}}

          <div class="col-lg-6">
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


          <div class="col-lg-6">
            <div class="signatures-table-data-wrapper">
              @include('users.signatures_table_data')
            </div>
          </div>

          @if(Auth::user()->rolesByIdExists([\App\Role::ADMIN, \App\Role::DEVELOPER]))
          <div class="col-lg-6">
            <div class="card" style="min-height: 170px;">
              <div class="card-header">
                <h3>Auto Distribute Tickets</h3>
              </div>
              <div class="card-body">
                <div class="custom-control custom-checkbox">
                  <input type="checkbox" class="custom-control-input tickets-auto-distribution" id="customCheck1" {{ ($autoTicketDistribution) ? 'checked' : '' }}>
                  <label class="custom-control-label tickets-auto-distribution" for="customCheck1">Enable Auto Distribution</label>
                </div>
              </div>
            </div>
          </div>
          @endif

          @if ( in_array(Auth::user()->roles->first()->id, [\App\Role::ADMIN, \App\Role::DEVELOPER]) ) <!--16 = test account -->

            <div class="col-lg-6">
            <div class="card" style="min-height: 170px;">
              <div class="card-header">
                <h3>eBay Connection</h3>
              </div>
              <div class="card-body">
                <div class="custom-control custom-checkbox">
                  <a href="{{ url('ebay/connect') }}" target="_blank" type="button" class="btn btn-primary">Connect</a>
                  <a href="{{ url('ebay/refreshTokens') }}" target="_blank" type="button" class="btn btn-primary btn-neutral">Refresh Token</a>
                </div>
              </div>
            </div>
          </div>

          @endif


          
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