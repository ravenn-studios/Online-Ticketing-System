<div class="modal fade" id="modalUpdateuserInfo" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">

            <form action="{{ route('ajaxAddEmailSupport') }}" id="formUpdateUserInfo" enctype="multipart/form-data" method="POST">
                {{-- {{ csrf_field() }} --}}
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Update</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="update-user-info-alerts">
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

                    <div class="card">

                        <div class="card-body">

                            <div class="pl-lg-4 pt-4">

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="form-control-label" for="input-address">Name</label>
                                            <div class="input-group input-group-merge input-group-alternative">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="far fa-address-card"></i></span>
                                                </div>
                                                <input type="text" class="form-control name" placeholder="Name" name="name" value="{{ Auth::user()->name }}"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="form-control-label" for="input-address">Email</label>
                                            <div class="input-group input-group-merge input-group-alternative">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="far fa-envelope"></i></span>
                                                </div>
                                                <input type="text" class="form-control email" placeholder="Email" name="email" value="{{ Auth::user()->email }}"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group"> 
                                            <label class="form-control-label" for="input-address">Avatar</label>

                                            {{-- @php
                                                $_image = \Storage::get('images/'.Auth::user()->avatar);
                                                $_image = base64_encode($_image);
                                            @endphp --}}
                                            <div class="text-center mb-3">
                                                {{-- <img src="data:image/jpeg;charset=utf-8;base64,{{ $_image }}" width="64"> --}}
                                                {!! Auth::user()->avatar() !!}
                                            </div>

                                            <div class="input-group input-group-merge input-group-alternative">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="far fa-file-alt"></i></span>
                                                </div>
                                                <input type="file" class="form-control avatar" name="avatar">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            
                        </div>
                        
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary btn-create-email-support">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>