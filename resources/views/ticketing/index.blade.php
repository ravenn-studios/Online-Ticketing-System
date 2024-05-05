{{-- @extends('layouts.ticketing') --}}
@extends('layouts.emailTemplates')

@section('content')

<meta name="csrf-token" content="{{ csrf_token() }}" />
    <!-- Page content -->

    <div class="container-fluid mt--6">

        @include('modal.modal-change-password')
        @include('modal.attachment-preview-modal')
        
        {{-- <div class="row">

            <div class="col-xl-4 ticket-listing pr-0">
                <div class="card custom-h">
                    @foreach($tickets as $ticket)
                        <a class="message-block" data-ticket-id="{{ $ticket->id }}" data-thread-id="{{ $ticket->thread_id }}" href="#">
                            <div class="card-body">
                                <h4 class="card-title">{{ $ticket->subject }}</h4>
                                <h6 class="card-subtitle mb-2">{{ $ticket->thread_starter }}</h6>
                                <p class="card-text">{{ $ticket->snippet }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="col-xl-8 ticket-content pl-0">
                <div class="card custom-h">
                    <div class="card-header border-0">
                        <div class="row align-items-center">
                          <div class="col-xl-11">
                              <div>
                                  <h3 class="mb-0 float-left email-subject">Email Subject</h3>
                                  <span class="email-time-ago pt-1 float-right">43 minutes ago</span> 
                              </div>
                              <div class="clear-both"></div>
                              <div class="mt-1">
                                  <h3 class="mb-0 email-snippet">
                                      <span class="content">"Hey, I need help with Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation"</span>
                                  </h3>
                              </div>
                          </div>
                          <div class="col-xl-1 text-right">
                              
                            <div class="dropdown">
                                <a class="btn btn-sm btn-icon-only text-light" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-ellipsis-v"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right dropdown-menu-arrow" style="">
                                    <a class="dropdown-item" href="#">Action</a>
                                    <a class="dropdown-item" href="#">Another action</a>
                                    <a class="dropdown-item" href="#">Something else here</a>
                                </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="card-body">
                        <div class="form-group">
                            
                            <form id="sendEmail">
                                {{ csrf_field() }}
                                <textarea class="form-control" id="emailContentEditor" rows="6"></textarea>
                                <input type="hidden" class="ticketId" />
                                <input type="hidden" class="threadId" />
                                <br>
                                <button type="submit" class="btn btn-primary btn-sm float-right">Send</button>
                                <br>
                            </form>
                        </div>

                        <hr>

                        <div class="message-history"></div>

                    </div>
                </div>
            </div>

        </div> --}}

        <!-- Modal -->

        @include('modal.assign-ticket-to-modal')
        @include('modal.confirm-assign-ticket-modal')
        @include('modal.confirm-bulk-update-modal')
        @include('modal.modal-compose-message')

        <div class="modal fade" id="modalFilterTicket" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">

                    <form role="form" id="formFilterTicket">

                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Apply Ticket Filter</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                        
                            <div class="card-body px-lg-5 py-lg-5 filterInputsBlock">

                                <div class="form-group mb-3">
                                    <label class="form-control-label">Status</label>
                                    <div class="input-group input-group-merge input-group-alternative">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-bars"></i></span>
                                        </div>
                                        <select class="form-control filterTicketStatus">
                                            <option value="" hidden>Status</option>

                                            @foreach($ticketStatus as $val)
                                                
                                                {{-- @if( $val->id != App\TicketStatus::STATUS_UNASSIGNED ) --}}

                                                    <option value="{{ $val->id }}">{{ $val->name }}</option>

                                                {{-- @endif --}}

                                            @endforeach

                                        </select>
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="form-control-label">Type</label>
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

                                <div class="form-group mb-3">
                                    <label class="form-control-label">Priority</label>
                                    <div class="input-group input-group-merge input-group-alternative">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-list"></i></span>
                                        </div>
                                        <select class="form-control filterTicketPriority">
                                            <option value="" hidden>Priority</option>
                                            @foreach($ticketPriorities as $ticketPriority)
                                                <option value="{{ $ticketPriority->id }}">{{ $ticketPriority->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                            </div>

                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            {{-- <button type="submit" class="btn btn-secondary refreshTable">Refresh Table</button> --}}
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
        
        <div class="modal fade" id="modalUpdateTicket" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">

                    <form role="form" id="formUpdateTicket">

                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Update Ticket Status</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                        
                            <div class="card-body px-lg-5 py-lg-5">

                                <div class="form-group mb-3">

                                    <label class="form-control-label" for="input-address">Status</label>

                                    <div class="input-group input-group-merge input-group-alternative">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-bars"></i></span>
                                        </div>
                                        <select class="form-control ticketStatus">
                                            <option hidden>Status</option>
                                            @foreach($ticketStatus as $val)
                                                <option value="{{ $val->id }}">{{ $val->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group mb-3">

                                    <label class="form-control-label" for="input-address">Type</label>

                                    <div class="input-group input-group-merge input-group-alternative">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-list"></i></span>
                                        </div>
                                        <select class="form-control ticketType">
                                            <option hidden>Type</option>
                                            @foreach($ticketTypes as $ticketType)
                                                <option value="{{ $ticketType->id }}">{{ $ticketType->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                @php

                                    $isManager   = 0;
                                    $classHidden = 'hidden';

                                    if ( Auth::user()->roles->first()->id != \App\Role::AGENT )
                                    {
                                        $isManager = 1;
                                        $classHidden = '';
                                    }
                                    
                                @endphp

                                <div class="form-group mb-3 {{ $classHidden }}">

                                    <label class="form-control-label" for="input-address">Priority</label>

                                    <div class="input-group input-group-merge input-group-alternative">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-level-up-alt"></i></span>
                                        </div>
                                        <select class="form-control ticketPriority" data-is-manager="{{ $isManager }}">

                                            <option hidden>Priority</option>

                                            @foreach($ticketPriorities as $ticketPriority)

                                                <option value="{{ $ticketPriority->id }}">{{ $ticketPriority->name }}</option>
                                                
                                            @endforeach

                                        </select>
                                    </div>
                                </div>

                            </div>

                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save changes</button>
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

                <div class="ticketing-table-data-wrapper">
                    @include('ticketing.ticketing_table_data')
                </div>
                
            </div>
        </div>

        <div class="row ticket-content-block mb-5">
            {{-- <div class="col-xl-3 col-1">
                <div class="card">
                    <div class="card-body">
                        <div class="form-group">
                            <label class="label">Priority</label>
                            <select class="form-control">
                                <option selected hidden>-</option>
                                <option value="1">Low</option>
                                <option value="2">Normal</option>
                                <option value="3">High</option>
                                <option value="4">Urgent</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="label">Type</label>
                            <select class="form-control">
                                <option selected hidden>-</option>
                                <option value="1">Question</option>
                                <option value="2">Incident</option>
                                <option value="3">Problem</option>
                                <option value="4">Other</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>  --}}
            <div class="col-xl-12 col-2">
                <div class="loadOverlay">
                    <img src="{{ asset('images/ajax-bar-loader.gif') }}">
                </div>
                <div class="card custom-h mb-0">
                    <div class="card-header border-0">
                        <div class="row align-items-center">
                            <div class="col-xl-12">
                                {{-- <div class="dropdown action-dropdown">
                                    <a class="btn btn-sm btn-icon-only text-light" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-right dropdown-menu-arrow" style="">
                                        <a class="dropdown-item updateTicket" href="#" data-toggle="modal" data-target="#modalUpdateTicket">Update</a>
                                    </div>
                                </div> --}}
                                <div>
                                    <h3 class="mb-0 float-left email-subject"></h3>

                                    <span class="email-time-ago pt-1 float-right"></span> 
                                </div>
                                <div class="clear-both"></div>
                                <div class="my-2 tags-block"></div>
                                <div class="my-2 categories-block"></div>
                                <div class="mt-2">
                                    <span class="float-left reply-icon mr-1">
                                        <i class="fas fa-reply"></i>
                                    </span>
                                    <h3 class="mb-0 requester-email float-left"></h3>
                                    <a class="change-requester-email float-left mr-3 ml-1" href="#">(change)</a>
                                </div>
                                <div class="mt-2">
                                    <span class="float-left reply-icon mr-1">
                                        <i class="fas fa-clipboard"></i>
                                    </span>
                                    <h3 class="mb-0 linked-order-number float-left"></h3>
                                    <a class="update-linked-order-number float-left mr-3 ml-1" href="#" title="Link Order Number">(link)</a>
                                </div>
                            </div>

                        </div>
                    </div>
                      <div class="card-body">

                        <div class="row">

                            <div class="col-md-9"></div>

                            {{-- <div class="col-md-3 select-categories-block">
                                
                                <span class="input-group-btn mt-3 float-right">
                                    <button class="btn btn-outline-primary btn-save-categories" type="button" tabindex="-1"><i class="far fa-save"></i></button>
                                </span>
                                <select class="form-control btn-select-ticket-categories selectpicker float-right mt-3 pr-1" style="display: inline-block;" data-none-selected-text="Select Ticket Category" data-width="300px" data-live-search="true" multiple="multiple">

                                    @foreach ($categories as $parent)

                                        <optgroup label="{{ $parent->name }}">

                                          @if ($parent->children->count())

                                              @foreach ($parent->children as $child)

                                                <option value="{{ $child->id }}" data-parent-category-id="{{ $child->parent_category_id }}">{{ $child->name }}</option>

                                              @endforeach

                                          @endif

                                        </optgroup>

                                    @endforeach

                                </select>

                                <select class="form-control float-right mt-3 pr-1" id="sel_2" style="display: inline-block; width: 100%;" multiple>

                                   @foreach($categories as $category)
                                        <option data-category-id="{{ $category->id }}" value="{{ $category->parent_category_id }}" 
                                            @if( strlen((string)$category->parent_category_id) > 1 ) 
                                                data-pup="{{ substr((string)$category->parent_category_id, 0, strlen((string)$category->parent_category_id) - 1) }}" 
                                            @endif 
                                            class="l{{ ($category->parent_category_id ? strlen((string)$category->parent_category_id) : 1) }} {{ ($category->with_child ? 'non-leaf' : '') }}">
                                            {{ $category->name }}
                                        </option>
                                    @endforeach

                                </select>

                            </div> --}}
                        </div>

                        <div class="row">
                            <div class="col-md-2">
                                <div class="nav-wrapper">
                                    <ul class="nav nav-pills nav-fill flex-column flex-md-row" id="tabs-icons-text" role="tablist">

                                        {{-- @if ( Auth::user()->roles->first()->id == \App\Role::AGENT_EBAY ) --}}
                                            <li class="nav-item">
                                                <a class="nav-link mb-sm-3 mb-md-0 active" id="tabs-icons-text-1-tab" data-toggle="tab" href="#tabs-icons-text-1" role="tab" aria-controls="tabs-icons-text-1" aria-selected="true"><i class="far fa-envelope"></i> Message</a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link mb-sm-3 mb-md-0" id="tabs-icons-text-2-tab" data-toggle="tab" href="#tabs-icons-text-2" role="tab" aria-controls="tabs-icons-text-2" aria-selected="false"><i class="far fa-file-alt"></i> Notes</a>
                                            </li>
                                        {{-- @else
                                            <li class="nav-item">
                                                <a class="nav-link mb-sm-3 mb-md-0 active" id="tabs-icons-text-2-tab active" data-toggle="tab" href="#tabs-icons-text-2" role="tab" aria-controls="tabs-icons-text-2" aria-selected="false"><i class="far fa-file-alt"></i> Notes</a>
                                            </li>
                                        @endif --}}

                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-4"></div>

                            <div class="col-md-2 inline-update-ticket">

                                <div class="form-group">

                                    <div class="input-group input-group-merge input-group-alternative mt-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-bars"></i></span>
                                        </div>
                                        <select class="form-control ticketStatus" data-action="ticket-status">
                                            <option hidden>Status</option>
                                            @foreach($ticketStatus as $val)
                                                <option value="{{ $val->id }}">{{ $val->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                </div>

                            </div>

                            <div class="col-md-4 select-categories-block">
                                
                                <span class="input-group-btn mt-3 float-right" data-toggle="tooltip" data-placement="top" title="Save">
                                    <button class="btn btn-outline-primary btn-save-categories" type="button" tabindex="-1"><i class="far fa-save"></i></button>
                                </span>
                                {{-- <select class="form-control btn-select-ticket-categories selectpicker float-right mt-3 pr-1" style="display: inline-block;" data-none-selected-text="Select Ticket Category" data-width="300px" data-live-search="true" multiple="multiple">

                                    @foreach ($categories as $parent)

                                        <optgroup label="{{ $parent->name }}">

                                          @if ($parent->children->count())

                                              @foreach ($parent->children as $child)

                                                <option value="{{ $child->id }}" data-parent-category-id="{{ $child->parent_category_id }}">{{ $child->name }}</option>

                                              @endforeach

                                          @endif

                                        </optgroup>

                                    @endforeach

                                </select> --}}

                                <select class="form-control float-right mt-3 pr-1" id="sel_2" style="display: inline-block; width: 100%;" multiple>

                                   @foreach($categories as $category)
                                        <option data-category-id="{{ $category->id }}" value="{{ $category->parent_category_id }}" 
                                            @if( strlen((string)$category->parent_category_id) > 1 ) 
                                                data-pup="{{ substr((string)$category->parent_category_id, 0, strlen((string)$category->parent_category_id) - 1) }}" 
                                            @endif 
                                            class="l{{ ($category->parent_category_id ? strlen((string)$category->parent_category_id) : 1) }} {{ ($category->with_child ? 'non-leaf' : '') }}">
                                            {{ $category->name }}
                                        </option>
                                    @endforeach

                                </select>

                            </div>
                            {{-- <div class="col-md-2 inline-update-ticket">
                                <div class="form-group">

                                    <div class="input-group input-group-merge input-group-alternative mt-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-list"></i></span>
                                        </div>
                                        <select class="form-control ticketType" data-action="ticket-type">
                                            <option hidden>Type</option>
                                            @foreach($ticketTypes as $ticketType)
                                                <option value="{{ $ticketType->id }}">{{ $ticketType->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 inline-update-ticket">
                                @php

                                    $isManager   = 0;
                                    $classHidden = 'hidden';

                                    if ( Auth::user()->roles->first()->id != \App\Role::AGENT )
                                    {
                                        $isManager = 1;
                                        $classHidden = '';
                                    }
                                    
                                @endphp

                                <div class="form-group {{ $classHidden }}">

                                    <div class="input-group input-group-merge input-group-alternative mt-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-level-up-alt"></i></span>
                                        </div>
                                        <select class="form-control ticketPriority" data-is-manager="{{ $isManager }}" data-action="ticket-priority">

                                            <option hidden>Priority</option>

                                            @foreach($ticketPriorities as $ticketPriority)

                                                <option value="{{ $ticketPriority->id }}">{{ $ticketPriority->name }}</option>
                                                
                                            @endforeach

                                        </select>
                                    </div>
                                </div>
                            </div> --}}

                            {{-- <div class="col-md-3 select-tags-block">
                                
                                <span class="input-group-btn mt-3 float-right">
                                    <button class="btn btn-outline-primary btn-save-tags" type="button" tabindex="-1"><i class="far fa-save"></i></button>
                                </span>
                                <select class="form-control btn-select-ticket-tags selectpicker float-right mt-3 pr-1" style="display: inline-block;" data-none-selected-text="Select Ticket Tags" data-width="300px" data-live-search="true" multiple="multiple">
                                    <option hidden="">Tags</option>
                                    @foreach($tags as $tag)
                                        <option data-slug="{{ $tag->slug }}" value="{{ $tag->id }}">{{ $tag->name }}</option>
                                    @endforeach
                                    <option value="custom">CUSTOM</option>
                                </select>

                            </div> --}}
                        </div>

                        <div class="card shadow">
                            <div class="card-body">
                                <div class="tab-content" id="myTabContent">

                                    {{-- @if ( Auth::user()->roles->first()->id == \App\Role::AGENT_EBAY ) --}}
                                        <div class="tab-pane fade show active" id="tabs-icons-text-1" role="tabpanel" aria-labelledby="tabs-icons-text-1-tab">
                                            <div class="row customVariablesBlock">
                                                <div class="col-xl-3 mb-3">
                                                    <div class="input-group input-group-merge input-group-alternative">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">
                                                                <i class="far fa-file-alt"></i>
                                                            </span>
                                                        </div>
                                                        
                                                        <select class="form-control selectEmailTemplates" id="selectEmailTemplates" {{ $emailTemplates ? '' : 'disabled' }}>
                                                            <option value="" hidden>Email Templates</option>
                    
                                                            @foreach ($emailTemplates as $emailTemplate)
                    
                                                                <option value="{{ $emailTemplate->id }}">{{ $emailTemplate->name }}</option>
                    
                                                            @endforeach
                    
                                                        </select>
                                                        <div class="input-group-append">
                                                            <button class="btn btn-outline-primary setTemplate" id="setTemplate" type="button">Set</button>
                                                        </div>
                                                    </div>
                                                </div>
                    
                                                {{-- <div class="col-xl-3 mb-3">
                                                    <div class="input-group input-group-merge input-group-alternative">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">
                                                                <i class="fas fa-code"></i>
                                                            </span>
                                                        </div>
                                                        <select class="form-control selectCustomVariable" id="selectCustomVariable">
                                                            <option hidden>Custom Variables</option>
                    
                                                            @foreach ($customVariables as $customVariable)
                    
                                                                <option value="&#123;&#123;{{ $customVariable->name }}&#125;&#125;">&#123;&#123;{{ $customVariable->name }}&#125;&#125;</option>
                    
                                                            @endforeach
                    
                                                            <option value="&#123;&#123;AGENT_NAME&#125;&#125;">&#123;&#123;AGENT_NAME&#125;&#125;</option>
                                                            <option value="&#123;&#123;AGENT_EMAIL&#125;&#125;">&#123;&#123;AGENT_EMAIL&#125;&#125;</option>
                    
                                                        </select>
                                                        <div class="input-group-append">
                                                            <button class="btn btn-outline-primary copyVariable" id="copyVariable" type="button" onclick="copyCustomVariable();">Copy</button>
                                                        </div>
                                                    </div>
                                                </div> --}}
                    
                                                <div class="col-xl-3">
                                                    <input type="text" id="hiddenCustomVariableInput" />
                                                </div>
                    
                                            </div>

                                            <div class="form-group">
                                                <form id="sendEmail">
                                                    {{ csrf_field() }}
                                                    <textarea class="form-control" id="emailContentEditor" rows="6"></textarea>
                                                    <input type="hidden" class="ticketId" />
                                                    <input type="hidden" class="threadId" />
                                                    <br>
                                                    <button type="submit" class="btn btn-primary btn-sm float-right">Send</button>
                                                    <br>
                                                </form>
                                            </div>

                                            {{-- @if ( !Auth::user()->rolesByIdExists([\App\Role::AGENT_EBAY]) ) --}}

                                                <div class="dropzone-block">
                                                    <form method="POST" action="{{ route('dropzone.upload')  }}" accept-chartset="UTF-8" enctype="multipart/form-data" class="dropzone dz-clickable" id="image-upload">
                                                        @csrf
                                                        <div class="dz-default dz-message">
                                                            <span>Drag Files here to upload</span>
                                                        </div>
                                                    </form>
                                                </div>
                                            
                                            {{-- @endif --}}

                                        </div>
                                        <div class="tab-pane fade" id="tabs-icons-text-2" role="tabpanel" aria-labelledby="tabs-icons-text-2-tab">
                                            <textarea class="form-control" id="notesContentEditor" rows="6" placeholder="Notes..."></textarea>
                                        </div>
                                    {{-- @else
                                        <div class="tab-pane fade show active" id="tabs-icons-text-2" role="tabpanel" aria-labelledby="tabs-icons-text-2-tab">
                                            <textarea class="form-control" id="notesContentEditor" rows="6" placeholder="Notes..."></textarea>
                                        </div>
                                    @endif --}}
                                </div>
                            </div>
                        </div>

                        {{-- <div class="row customVariablesBlock">
                            <div class="col-xl-3 mb-3">
                                <div class="input-group input-group-merge input-group-alternative">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="far fa-file-alt"></i>
                                        </span>
                                    </div>
                                    <select class="form-control selectEmailTemplates" id="selectEmailTemplates">
                                        <option value="" hidden>Email Templates</option>

                                        @foreach ($emailTemplates as $emailTemplate)

                                            <option value="{{ $emailTemplate->id }}">{{ $emailTemplate->name }}</option>

                                        @endforeach

                                    </select>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-primary setTemplate" id="setTemplate" type="button">Set</button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-3 mb-3">
                                <div class="input-group input-group-merge input-group-alternative">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fas fa-code"></i>
                                        </span>
                                    </div>
                                    <select class="form-control selectCustomVariable" id="selectCustomVariable">
                                        <option hidden>Custom Variables</option>

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

                            <div class="col-xl-3">
                                <input type="text" id="hiddenCustomVariableInput" />
                            </div>

                        </div>
                        <div class="form-group">
                            <form id="sendEmail">
                                {{ csrf_field() }}
                                <textarea class="form-control" id="emailContentEditor" rows="6"></textarea>
                                <input type="hidden" class="ticketId" />
                                <input type="hidden" class="threadId" />
                                <br>
                                <button type="submit" class="btn btn-primary btn-sm float-right">Send</button>
                                <br>
                            </form>
                        </div> --}}

                        <hr>

                        <div class="message-history"></div>

                    </div>
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