@extends('layouts.emailTemplates')

@section('content')

<meta name="csrf-token" content="{{ csrf_token() }}" />
    <!-- Page content -->

    <div class="container-fluid mt--6">

        @include('modal.modal-change-password')
        
        <!-- Modal -->
        {{-- <div class="modal fade" id="modalFilterTicket" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">

                    <form role="form" id="formFilterTicket">

                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Apply Filter</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                        
                            <div class="card-body px-lg-5 py-lg-5 filterInputsBlock">

                                <div class="form-group mb-3">
                                    <div class="input-group input-group-merge input-group-alternative">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-bars"></i></span>
                                        </div>
                                        <select class="form-control filterTicketStatus">
                                            <option value="" hidden>Status</option>
                                            @foreach($ticketStatus as $val)
                                                <option value="{{ $val->id }}">{{ $val->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <div class="input-group input-group-merge input-group-alternative">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-list"></i></span>
                                        </div>
                                        <select class="form-control filterTicketType">
                                            <option value="" hidden>Type</option>
                                            @foreach($ticketTypes as $ticketType)
                                                <option value="{{ $ticketType->id }}">{{ $ticketType->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                            </div>

                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-secondary refreshTable">Refresh Table</button>
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>

                    </form>
                </div>
            </div>
        </div> --}}

        <div class="modal fade" id="modalCreateEmailTemplate" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
                <div class="modal-content">

                    <form id="formCreateEmailTemplate">

                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Create Email Template</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>

                        <div class="modal-body">

                            <div class="card">

                                <div class="card-body">

                                    <!-- email template info -->
                                    {{-- <h6 class="heading-small text-muted mb-4">Email Template information</h6> --}}
                                    
                                    <div class="pl-lg-4 pt-4">

                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label class="form-control-label" for="input-address">Template Name</label>
                                                    <div class="input-group input-group-merge input-group-alternative">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i class="far fa-address-card"></i></span>
                                                        </div>
                                                        <input type="text" class="form-control templateName" placeholder="Template 1 - Acknowledgement Template"/>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row customVariablesBlock">
                                            <div class="col-md-12">
                                                {{-- <div class="form-group">
                                                    <input type="text" id="hiddenCustomVariableInput" />
                                                    <label class="form-control-label" for="input-address">Template Content</label>

                                                    <div class="input-group input-group-merge input-group-alternative" style="width: 40%;">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">
                                                                <i class="fas fa-code"></i>
                                                            </span>
                                                        </div>
                                                        <select class="form-control selectCustomVariable" id="selectCustomVariable">
                                                            <option hidden="">Custom Variables</option>
                                                            @foreach ($customVariables as $customVariable)
                                                                <option value="&#123;&#123;{{ $customVariable->name }}&#125;&#125;">&#123;&#123;{{ $customVariable->name }}&#125;&#125;</option>
                                                            @endforeach
                                                            <option value="&#123;&#123;AGENT_NAME&#125;&#125;">&#123;&#123;AGENT_NAME&#125;&#125;</option>
                                                            <option value="&#123;&#123;AGENT_EMAIL&#125;&#125;">&#123;&#123;AGENT_EMAIL&#125;&#125;</option>
                                                        </select>
                                                        <div class="input-group-append">
                                                            <button class="btn btn-outline-primary copyVariable" id="copyVariable" type="button" onclick="copyCustomVariable()">Copy</button>
                                                        </div>
                                                    </div>
                                                </div> --}}

                                                <div class="form-group">
                                                    <label class="form-control-label" for="input-address">Template Content</label>
                                                    <textarea class="form-control" id="emailTemplateContentEditor" rows="8"></textarea>
                                                </div>

                                            </div>
                                        </div>

                                    </div>

                                    
                                </div>
                                
                            </div>
                            
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modalUpdateEmailTemplate" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
                <div class="modal-content">

                    <form id="formUpdateEmailTemplate">

                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Update Email Template</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>

                        <div class="modal-body">

                            <div class="card">

                                <div class="card-body">

                                    <!-- email template info -->
                                    {{-- <h6 class="heading-small text-muted mb-4">Email Template information</h6> --}}
                                    
                                    <div class="pl-lg-4 pt-4">

                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label class="form-control-label" for="input-address">Template Name</label>
                                                    <div class="input-group input-group-merge input-group-alternative">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i class="far fa-address-card"></i></span>
                                                        </div>
                                                        <input type="text" class="form-control templateName" placeholder="Template 1 - Acknowledgement Template"/>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row customVariablesBlock">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label class="form-control-label" for="input-address">Template Content</label>
                                                    <input type="text" id="hiddenCustomVariableInput" />

                                                    <div class="input-group input-group-merge input-group-alternative" style="width: 40%;">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">
                                                                <i class="fas fa-code"></i>
                                                            </span>
                                                        </div>
                                                        <select class="form-control selectCustomVariable" id="selectCustomVariable">
                                                            <option hidden="">Custom Variables</option>
                                                            {{-- <option value="1">&#123;&#123;CUSTOMER_NAME&#125;&#125;</option>
                                                            <option value="2">Pending</option>
                                                            <option value="3">Solved</option>
                                                            <option value="4">Closed</option> --}}
                                                            @foreach ($customVariables as $customVariable)
                                                                <option value="&#123;&#123;{{ $customVariable->name }}&#125;&#125;">&#123;&#123;{{ $customVariable->name }}&#125;&#125;</option>
                                                            @endforeach
                                                            <option value="&#123;&#123;AGENT_NAME&#125;&#125;">&#123;&#123;AGENT_NAME&#125;&#125;</option>
                                                            <option value="&#123;&#123;AGENT_EMAIL&#125;&#125;">&#123;&#123;AGENT_EMAIL&#125;&#125;</option>
                                                        </select>
                                                        <div class="input-group-append">
                                                            <button class="btn btn-outline-primary copyVariable" id="copyVariable" type="button" onclick="copyCustomVariable()">Copy</button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <textarea class="form-control" id="updateEmailTemplateContentEditor" rows="8"></textarea>
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

                <div class="email-templates-data-wrapper">
                    @include('emailTemplates.email_templates_table_data')
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