@extends('layouts.emailTemplates')

@section('content')

    <!-- Page content -->

    <div class="container-fluid mt--6">

        @include('modal.modal-change-password')
        @include('chat.facebook-conversations')

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