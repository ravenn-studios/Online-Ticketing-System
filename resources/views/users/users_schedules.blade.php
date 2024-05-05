@extends('layouts.users')

@section('content')

<meta name="csrf-token" content="{{ csrf_token() }}" />
    <!-- Page content -->

    <div class="container-fluid mt--6">

        <!-- Modal -->
        @include('modal.create-custom-page-modal')

        @include('modal.update-custom-page-modal')

        @include('modal.create-signature-modal')

        @include('modal.create-reminder-modal')

        @include('modal.update-reminder-modal')
        
        @include('modal.update-signature-modal')

        @include('modal.update-user-info-modal')

        @include('modal.modal-change-password')

        @include('modal.update-user-ticket-limit-modal')

        <div class="users-schedules-wrapper">

          @include('users.users_schedules_data')

        </div>
        {{-- <div class="row">

          <div class="users-ticket-limit-table-data-wrapper">
            @include('users.user_schedules_data')
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