@extends('layouts.users')

@section('content')

<meta name="csrf-token" content="{{ csrf_token() }}" />
    <!-- Page content -->

    <div class="container-fluid mt--6">

        <!-- Modal -->

        @include('modal.create-reminder-modal')

        @include('modal.update-reminder-modal')

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

                <div class="users-ticket-limit-table-data-wrapper">
                    @include('users.user_reminders')
                </div>

                {{-- <div class="users-ticket-limit-table-data-wrapper">
                    @include('users.user_ticket_limit')
                </div> --}}
  
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

        </div>

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