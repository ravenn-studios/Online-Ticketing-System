<div class="modal fade" id="modalComposeMessage" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Compose Message</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

                <div class="custom-page-alerts">
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

                {{-- <div class="card">
                    <div class="card-body">
                        
                        <div class="row compose-message">
                            <div class="col-md-12">

                                <div class="form-group">
                                    <form id="composeMessage">
                                        {{ csrf_field() }}
                                        <textarea class="form-control" id="emailContentEditor" rows="6"></textarea>
                                        <br>
                                        <button type="submit" class="btn btn-primary btn-sm float-right">Send</button>
                                        <br>
                                    </form>
                                </div>

                            </div>

                        </div>

                    </div>
                </div> --}}

                <div class="row">
                    <div class="col-md-3">
                        <div class="nav-wrapper">
                            <ul class="nav nav-pills nav-fill flex-column flex-md-row" id="tabs-icons-text" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link mb-sm-3 mb-md-0 active" id="modal-tabs-icons-text-1-tab" data-toggle="tab" href="#modal-tabs-icons-text-1" role="tab" aria-controls="tabs-icons-text-1" aria-selected="true">
                                        <i class="far fa-envelope"></i> Message
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link mb-sm-3 mb-md-0" id="modal-tabs-icons-text-2-tab" data-toggle="tab" href="#modal-tabs-icons-text-2" role="tab" aria-controls="tabs-icons-text-2" aria-selected="false">
                                        <i class="far fa-file-alt"></i> Notes
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    {{-- <div class="col-md-9">
                        <div class="select-tags-block">
                            <select class="form-control btn-select-ticket-tags selectpicker float-right mt-3 pr-1" style="display: inline-block;" data-none-selected-text="Select Ticket Tags" data-width="300px" data-live-search="true" multiple="multiple">
                                <option hidden="">Tags</option>
                                @foreach($tags as $tag)
                                    <option data-slug="{{ $tag->slug }}" value="{{ $tag->id }}">{{ $tag->name }}</option>
                                @endforeach
                                <option value="custom">CUSTOM</option>
                            </select>
                        </div>
                    </div> --}}
                </div>

                <div class="card shadow">
                    <div class="card-body">
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="modal-tabs-icons-text-1" role="tabpanel" aria-labelledby="modal-tabs-icons-text-1-tab">
                                <div class="row mb-3 customVariablesBlock">

                                    <div class="col-xl-3">
                                        <input type="email" class="form-control modalComposeMessageEmail" placeholder="Send To:">
                                    </div>
                                    <div class="col-xl-3">
                                        <input type="text" class="form-control modalComposeMessageSubject" placeholder="Subject">
                                    </div>

                                    @if ( count($emailTemplates) )
                                    <div class="col-xl-4">
                                        <div class="input-group input-group-merge input-group-alternative">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">
                                                    <i class="far fa-file-alt"></i>
                                                </span>
                                            </div>
                                            <select class="form-control modalSelectEmailTemplates" id="modalSelectEmailTemplates">
                                                <option value="" hidden>Email Templates</option>
        
                                                @foreach ($emailTemplates as $emailTemplate)
        
                                                    <option value="{{ $emailTemplate->id }}">{{ $emailTemplate->name }}</option>
        
                                                @endforeach
        
                                            </select>
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-primary modalSetTemplate" id="modalSetTemplate" type="button">Set</button>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                    {{-- <div class="col-xl-3">
                                        <input type="text" id="hiddenCustomVariableInput" />
                                    </div> --}}
        
                                </div>
                                <div class="form-group">
                                    <form id="sendComposedMessage">
                                        <div class="loadOverlay" style="display: none;">
                                            <img src="https://ots.blackedgedigital.com/images/ajax-bar-loader.gif">
                                        </div>
                                        
                                        {{ csrf_field() }}
                                        <textarea class="form-control" id="modalEmailContentEditor" rows="6"></textarea>
                                        <br>
                                        <button type="submit" class="btn btn-primary btn-sm float-right">Send</button>
                                        <br>
                                    </form>
                                </div>
                                
                                {{-- @if ( !Auth::user()->rolesByIdExists([\App\Role::AGENT_EBAY]) ) --}}

                                    <div class="dropzone-block">
                                        <form method="POST" action="{{ route('dropzone.upload')  }}" accept-chartset="UTF-8" enctype="multipart/form-data" class="dropzone dz-clickable" id="image-upload-compose">
                                            @csrf
                                            <div class="dz-default dz-message">
                                                <span>Drag Files here to upload</span>
                                            </div>
                                        </form>
                                    </div>

                                {{-- @endif --}}
                                
                            </div>
                            <div class="tab-pane fade" id="modal-tabs-icons-text-2" role="tabpanel" aria-labelledby="modal-tabs-icons-text-2-tab">
                                <textarea class="form-control" id="modalNotesContentEditor" rows="6" placeholder="Notes..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>

        </div>
    </div>
</div>