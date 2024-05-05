@extends('layouts.users')

@section('content')

<meta name="csrf-token" content="{{ csrf_token() }}" />
    <!-- Page content -->

    <div class="container-fluid mt--6">

        @include('modal.modal-view-logged-message')

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
  
                <div class="signatures-table-data-wrapper">
                    @include('activity.logs_table_data')
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