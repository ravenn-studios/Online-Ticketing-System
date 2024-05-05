{{-- @extends('layouts.users') --}}
@extends('layouts.emailTemplates')

@section('content')

<meta name="csrf-token" content="{{ csrf_token() }}" />
    <!-- Page content -->

    <div class="container-fluid mt--6">

        @include('modal.modal-change-password')
        
        <!-- Modal -->
        <div class="modal fade" id="modalRegisterUser" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">

                    <form role="form" id="formRegisterUser">

                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Register</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        
                        <div class="modal-body">

                            <div class="row justify-content-center">
                                <div class="col-md-12">
                                    <div class="card border-0">
                                        <div class="card-body px-lg-5 py-lg-5">
                                            <div class="form-group">
                                                <div class="input-group input-group-merge input-group-alternative mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">
                                                            <i class="ni ni-hat-3"></i>
                                                        </span>
                                                    </div>
                                                    <input class="form-control name" placeholder="Name" type="text" name="name">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <div class="input-group input-group-merge input-group-alternative mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">
                                                            <i class="ni ni-email-83"></i>
                                                        </span>
                                                    </div>
                                                    <input class="form-control email" placeholder="Email" type="email" name="email">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <div class="input-group input-group-merge input-group-alternative">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">
                                                            <i class="ni ni-lock-circle-open"></i>
                                                        </span>
                                                    </div>
                                                    <input class="form-control password" placeholder="Password" type="password" name="password">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <div class="input-group input-group-merge input-group-alternative">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">
                                                            <i class="ni ni-lock-circle-open"></i>
                                                        </span>
                                                    </div>
                                                    <input class="form-control password-confirm" placeholder="Confirm Password" type="password" name="password_confirmation">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Create</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modalUpdateUser" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">

                    <form role="form" id="formUpdateUser">

                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Update User</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        
                        <div class="modal-body">

                            <div class="row justify-content-center">
                                <div class="col-md-12">
                                    <div class="card border-0">
                                        <div class="card-body px-lg-5 py-lg-5">
                                            <div class="form-group">
                                                <div class="input-group input-group-merge input-group-alternative mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">
                                                            <i class="ni ni-hat-3"></i>
                                                        </span>
                                                    </div>
                                                    <input class="form-control name" placeholder="Name" type="text" name="name">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <div class="input-group input-group-merge input-group-alternative mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">
                                                            <i class="ni ni-email-83"></i>
                                                        </span>
                                                    </div>
                                                    <input class="form-control email" placeholder="Email" type="email" name="email">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>

                    </form>
                    
                </div>
            </div>
        </div>

        <div class="modal fade" id="modalConfirmDeleteUser" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">

                    <form role="form" id="formDeleteUser">

                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Delete User</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        
                        <div class="modal-body">
                            <h4>Confirm to delete a user.</h4>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Confirm</button>
                        </div>

                    </form>
                    
                </div>
            </div>
        </div>


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

                <div class="users-table-data-wrapper">
                    @include('users.users_table_data')
                </div>
                
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