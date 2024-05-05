<div class="card">
    <div class="loadOverlay">
        <img src="{{ asset('images/ajax-bar-loader.gif') }}">
    </div>
    <!-- Card header -->
    <div class="card-header border-0">
        <h3 class="mb-0 float-left mr-3">Tickets</h3>

        <a href="#" class="float-left btn btn-primary btn-sm ml-3 btn-check-all-link">
            <input type="checkbox" class="ticket-check-all2">
            <span>Check All</span>
        </a>

        <a href="#" class="float-left btn btn-primary btn-sm ml-2 bulk-solve-tickets" data-action="solve">Solve Tickets</a>
        <a href="#" class="float-left btn btn-primary btn-sm ml-2 bulk-close-tickets" data-action="close">Close Tickets</a>

        {{-- <select class="form-control-sm btn btn-primary bulk-update-ticket-type float-left" data-action="update-ticket-type">
            <option hidden="">Type</option>
            <option value="{{ \App\TicketType::TYPE_QUESTION }}">Question</option>
            <option value="{{ \App\TicketType::TYPE_PROBLEM }}">Problem</option>
        </select> --}}

        
        {{-- <input type="text" class="form-control-sm search-tickets" placeholder="Search"> --}}
        <div class="float-left relative">
            <div class="loadOverlay2">
                <img src="{{ asset('images/ajax-bar-loader.gif') }}">
            </div>
            <input class="form-control form-control-sm search-tickets float-left" type="text" placeholder="Search">
            <input class="general-search" type="checkbox" data-toggle="tooltip" data-placement="top" title="Search All" {{ ( isset($general_search) && $general_search ) ? 'checked' : '' }}>
        </div>

        <div class="float-left ml-3">
            <span class="text-sm text-muted">Search Categories:</span>
            {{-- <span class="text-sm text-muted">Search Category:</span> --}}
        </div>
        <div class="float-left ml-2" style="width: 450px;">
            {{-- <select class="form-control-sm search-tags" multiple="multiple">
                <option value="1">One</option>
                <option value="2">Two</option>
                <option value="3">Three</option>
            </select> --}}
            {{-- <select class="search-tags" multiple="multiple">
                @foreach($tags as $tag)
                    <option data-slug="{{ $tag->slug }}" value="{{ $tag->id }}">{{ $tag->name }}</option>
                @endforeach
            </select> --}}

            <select class="form-control float-right mt-3 pr-1" id="sel_3" style="display: inline-block; width: 100%;" multiple>

               @foreach($categories as $category)
                    <option data-category-id="{{ $category->id }}" data-category-slug="{{ $category->slug }}" value="{{ $category->parent_category_id }}" 
                        @if( strlen((string)$category->parent_category_id) > 1 ) 
                            data-pup="{{ substr((string)$category->parent_category_id, 0, strlen((string)$category->parent_category_id) - 1) }}" 
                        @endif 
                        class="l{{ ($category->parent_category_id ? strlen((string)$category->parent_category_id) : 1) }} {{ ($category->with_child ? 'non-leaf' : '') }}">
                        {{ $category->name }}
                    </option>
                @endforeach

            </select>
            {{-- <select class="form-control-sm search-categories" multiple="multiple">
                <option></option>
                @foreach($categories as $category)
                    @if($category->parent_category_id != null)
                        <option data-slug="{{ $category->slug }}" value="{{ $category->id }}">{{ $category->name }}</option>
                    @endif
                @endforeach
            </select> --}}
        </div>

        @if ( isset($myAgentTickets) )

            @if ( $myAgentTickets )
                <select class="form-control-sm btn btn-primary select-agent float-left ml-2">

                    @foreach ( $agents as $agent )
                        @if ($loop->index == 0)
                            <option value="{{ $agent->id }}" selected="selected">{{ $agent->name }} [{{ $agent->tickets_count }}]</option>
                        @else
                            <option value="{{ $agent->id }}">{{ $agent->name }} [{{ $agent->tickets_count }}]</option>
                        @endif
                    @endforeach

                </select>

                <select class="form-control-sm btn btn-primary select-agent-view float-left text-left ml-2">

                    <option value="view-tickets" selected="selected">View Tickets</option>
                    <option value="pending" >Pending</option>
                    <option value="solved" >Solved</option>
                    <option value="closed">Closed</option>

                </select>
            @endif

        @endif

        <a href="#" class="btn btn-sm btn-primary btnFilter float-right refreshTable ml-3"><i class="fas fa-redo-alt"></i></a>

        {{-- <a href="#" class="btn btn-sm btn-primary btnFilter float-right viewMyTickets">View My Tickets</a> --}}
        
        {{-- @if ( Request::is('tickets/my-tickets') )
        
        <a href="{{ url('tickets/unassigned') }}" class="btn btn-sm btn-primary btnFilter float-right ml-3 viewUnassignedTickets"><i class="far fa-eye"></i> Unassigned Tickets</a>
        
        @endif --}}

        <a class="btn btn-sm btn-primary text-white float-right mr-3 btn-compose-message" data-toggle="modal" data-target="#modalComposeMessage"><i class="far fa-envelope"></i> Compose</a>

    </div>
    <!-- Light table -->
    <div class="table-responsive">

        <table class="table align-items-center table-flush ticket-listing" id="ticket-listing">
            <thead class="thead-light">
            <tr>
                <th class="text-right"><input class="form-check-input mt--5 ticket-check-all" type="checkbox"></th>
                <th scope="col" class="sort" data-sort="name" width="310" onclick="sortTable(1)">Subject</th>
                <th scope="col" class="sort"></th>
                <th scope="col" class="sort text-center" data-sort="name" onclick="sortTable(3)">Assigned To</th>
                <th scope="col" class="sort text-center" data-sort="name" onclick="sortTable(4)">Requester</th>
                <th scope="col" class="sort text-center" data-sort="name" onclick="sortTable(5)">Origin</th>
                <th scope="col" class="sort text-center" data-sort="completion" onclick="sortTable(6)">Status</th>
                <th scope="col" class="sort text-center" data-sort="completion" onclick="sortTable(7)">Type</th>
                {{-- <th scope="col" class="sort text-center" data-sort="completion">Priority</th> --}}

                @if ( Request::is('tickets/solved') )
                    <th scope="col" class="sort text-center" data-sort="completion" onclick="sortTable(8)">Solved</th>
                @elseif( Request::is('tickets/closed') )
                    <th scope="col" class="sort text-center" data-sort="completion" onclick="sortTable(8)">Closed</th>
                @else
                    <th scope="col" class="sort text-center" data-sort="completion" onclick="sortTable(8)">Date</th>
                @endif
                
                {{-- @if ( Auth::user()->rolesByIdExists([\App\Role::ADMIN, \App\Role::MANAGER, \App\Role::DEVELOPER]) )rolesAdminManagerDeveloperExists --}}
                {{-- Auth id = 9 => Anne, special access for re-assigning tickets to an agent --}}
                {{-- @if ( $rolesAdminManagerDeveloperExists || Auth::id() == 9 ) --}}
                {{-- tmp condition for althey --}}
                @if ( !in_array(Auth::id(), [25, 26, 29]) ) 
                    <th scope="col"></th>
                @endif
            </tr>
            </thead>
            <tbody class="list">

            @php
                $footerStartPageRowCount = $startPageRowCount = ($tickets->currentPage() - 1) * $tickets->perPage();
            @endphp

            @forelse($tickets as $ticket)

                @php
                    $startPageRowCount++;
                    $classMarkedHigh = '';

                    $ticket->requester = explode("<", $ticket->requester)[0];
                @endphp

                @if ( $ticket->priority_id == \App\TicketPriority::PRIORITY_HIGH || $ticket->priority_id == \App\TicketPriority::PRIORITY_URGENT )
                    @php
                        $classMarkedHigh = 'marked-high';
                    @endphp
                @endif

            <tr class="message-block {{ $classMarkedHigh }} {{ $ticket->is_read() ? '' : 'ticket-unread-bg' }}" data-ticket-id="{{ $ticket->id }}" data-thread-id="{{ $ticket->thread_id }}">
                <td class="text-right">
                    <input class="form-check-input mt--5 ticket-checkbox" data-ticket-id="{{ $ticket->id }}" type="checkbox">
                </td>
                <td style="font-weight: 600;" title="{!! $ticket->subject !!}">
                    {{-- <div class="media align-items-center"> --}}
                        {{-- <a href="#" class="avatar rounded-circle mr-3">
                        <img alt="Image placeholder" src="{{ asset('images/theme/sketch.jpg') }}">
                        </a> --}}
                        {{-- <div class="media-body"> --}}
                            
                            {{-- @if ( $ticket->is_read() ) --}}

                                {{-- tmp, optimize this --}}
                                {{-- @php
                                    // $_user         = Auth::user();
                                    // $_message      = $ticket->messages->reverse()->first();
                                    $_message      = $ticket->getLastMessage();
                                    $userNameEmail = $user->name .' <'. $user->email .'>';
                                @endphp

                                @if ( !empty($_message) && ( strpos($_message->from, $emailSupportAddress) !== false || strpos($_message->from, $user->email) !== false || $_message->from == 'Brandbeast' || $_message->from == $userNameEmail ) )
                                    <span class="name mb-0 text-sm">
                                        <span class="badge badge-secondary custom-badge" style="background: none;" data-toggle="tooltip" data-placement="top" title="" data-original-title="Replied"><i class="fas fa-reply"></i></span>
                                    </span>
                                @else
                                    <span class="name mb-0 text-sm hidden">
                                        <span class="badge badge-secondary custom-badge" style="background: none;" data-toggle="tooltip" data-placement="top" title="" data-original-title="Replied"><i class="fas fa-reply"></i></span>
                                    </span>
                                @endif --}}

                                @php
                                    $initialMessageId = (isset($ticket->messages->first()->id)) ? $ticket->messages->first()->id : '';
                                @endphp

                                <div class="popover-wrapper" data-initial-message-id="{{ $initialMessageId }}"></div>

                               {{--  @if ( !is_null($ticket->messages->first()) )
                                    <div class="hide d-none" id="{{ $initialMessageId }}" style="min-width: 400px;">
                                      <div class="popover-heading">

                                        @if( !empty($ticket->status_badge()) )
                                            {!! $ticket->status_badge() !!}
                                        @elseif( $ticket->status_id == \App\TicketStatus::STATUS_PENDING )
                                            <span class="badge badge-warning custom-badge mr-1" data-toggle="tooltip" data-placement="top" title="New Message">Pending</span>
                                        @elseif( $ticket->status_id == \App\TicketStatus::STATUS_SOLVED )
                                            <span class="badge badge-warning custom-badge mr-1" data-toggle="tooltip" data-placement="top" title="New Message">Solved</span>
                                        @elseif( $ticket->status_id == \App\TicketStatus::STATUS_CLOSED )
                                            <span class="badge badge-warning custom-badge mr-1" data-toggle="tooltip" data-placement="top" title="New Message">Closed</span>
                                        @endif

                                        <span class="text-muted">Ticket #{{ $ticket->id }}</span>

                                      </div>

                                        <div class="popover-body">

                                            @php
                                                $tmpMessage = base64_decode(str_replace(array('-', '_'), array('+', '/'), $ticket->messages->first()->message));
                                                $tmpMessage = preg_replace('/<\s*style.+?<\s*\/\s*style.*?>/si', ' ', $tmpMessage );
                                                $tmpMessage = preg_replace('/<\s*meta.+?<\s*\/\s*meta.*?>/si', ' ', $tmpMessage );
                                                $tmpMessage = strip_tags($tmpMessage);
                                                $tmpMessage = trim($tmpMessage);
                                            @endphp

                                            <h4 title="$ticket->subject">{!! substr( $ticket->subject, 0, 70 ) !!}..</h4>

                                            {!! substr( $tmpMessage, 0, 350) !!}

                                            @if ( !is_null($ticket->messages->last()) && $ticket->origin_id != \App\TicketOrigin::ORIGIN_EBAY && $ticket->messages->count() > 1 )

                                                @php
                                                    $tmpLastMessage = $ticket->messages->last();
                                                    $tmpMessage     = base64_decode(str_replace(array('-', '_'), array('+', '/'), $tmpLastMessage->message));
                                                    $tmpMessage     = preg_replace('/<\s*style.+?<\s*\/\s*style.*?>/si', ' ', $tmpMessage );
                                                    $tmpMessage     = preg_replace('/<\s*meta.+?<\s*\/\s*meta.*?>/si', ' ', $tmpMessage );
                                                    $tmpMessage     = strip_tags($tmpMessage);
                                                    $tmpMessage     = trim($tmpMessage);
                                                @endphp

                                                <div class="popover-footer mt-5">

                                                    <span class="text-muted" style="margin: 0px!important;">Latest Reply</span>

                                                    <hr>

                                                    <div class="row mb-2">
                                                        <div class="col-md-8 text-bold text-left">{{ $tmpLastMessage->from }}</div>
                                                        <div class="col-md-4 text-right" title="{{ $tmpLastMessage->created_at->format('M d, Y H:i') }}">{{ $tmpLastMessage->created_at->format('M d, Y') }}</div>
                                                    </div>

                                                    @if( !empty($tmpLastMessage->notes) )
                                                        <div class="preview-notes mb-3">{{ $tmpLastMessage->notes }}</div>
                                                    @endif

                                                    {!! substr( $tmpMessage, 0, 350) !!}

                                                </div>
                                            @endif

                                        </div>
                                    </div>
                                @endif --}}

                                <span class="name mb-0 text-sm ticket-subject mr-2 {{ $ticket->is_read() ? '' : 'text-bold' }}" data-ticket-id="{{ $ticket->id }}" data-toggle="popover" data-popover-content="#{{ $initialMessageId }}" data-placement="right">

                                    @if($ticket->type_id == \App\TicketType::TYPE_SPAM)

                                        <span class="badge badge-warning custom-badge mr-1" data-toggle="tooltip" data-placement="top" title="Spam">Spam</span>

                                    @else

                                        {!! $ticket->status_badge() !!}

                                    @endif

                                    {!! substr( $ticket->subject, 0, 50) !!}

                                </span>

                                @if ( $ticket->categories->count() )

                                    <div class="row pl-3">

                                        @foreach( $ticket->categories as $category )

                                            <span class="badge badge-pill badge-primary font-weight-bold mr-2 mt-2">{{ $category->name }}</span>

                                        @endforeach

                                    </div>

                                @endif

                            {{-- @else

                                <span class="name mb-0 text-sm"><b>{!! substr( $ticket->subject, 0, 60) !!}</b></span>

                            @endif --}}

                            {{-- <span class="badge badge-pill {{ \App\TicketPriority::PRIORITY_LIST[$ticket->priority_id]['badge_class'] }}">{{ $ticket->priority->name }}</span> --}}

                            {{-- @if ( isset($ticket->durationUnassignedStr) )

                                <span class="badge badge-pill badge-info">{{ $ticket->durationUnassignedStr }}</span>

                            @endif --}}


                        {{-- </div>
                    </div> --}}
                </td>

                <td>
                    @if ($ticket->priority_id != 0)

                        {{-- <span class="badge badge-pill {{ \App\TicketPriority::PRIORITY_LIST[$ticket->priority_id]['badge_class'] }}">{{ $ticket->priority->name }}</span> --}}

                    @else

                        <span class="badge badge-pill badge-primary" style="width: 52.2px;">--</span>

                    @endif
                    

                </td>
                {{-- <td class="budget" width="100" title="{!! $ticket->snippet !!}">
                    {!! substr($ticket->snippet, 0, 50) !!}
                </td> --}}
                {{-- <td class="requester text-center" data-toggle="tooltip" data-placement="top" title="{{ $ticket->assignedTo->user->name }}"> --}}
                <td class="text-center" data-toggle="tooltip" data-placement="top" title="{{ ($ticket->assignedTo()->count()) ? $ticket->assignedTo->user->name : '-' }}">
                    {{-- {{ $ticket->assignedTo->user->name }} --}}
                    {{ ($ticket->assignedTo()->count()) ? $ticket->assignedTo->user->name : '-' }}
                    {{-- {!! $ticket->assignedTo->user->roundedAvatar('sm') !!} --}}
                    {{-- <i class="far fa-user-circle" style="font-size: 25px;"></i> --}}
                </td>
                <td class="requester text-center" data-toggle="tooltip" data-placement="top" title="{{ $ticket->requester }}">
                    {{-- {{ substr($ticket->requester, 0, 25) }} --}}
                    <i class="far fa-user-circle" style="font-size: 25px;"></i>
                </td>
                <td class="text-center">
                    {{ $ticket->origin?$ticket->origin->name:'--' }}
                </td>
                <td class="text-center">
                    {{ $ticket->status->name }}
                </td>
                <td class="text-center">
                    {{ $ticket->type->name }}
                </td>
                {{-- <td class="requester text-center">
                    {{ \App\TicketPriority::find($ticket->priority)->name }}
                </td> --}}
                @if ( Request::is('tickets/solved') || Request::is('tickets/closed') || Request::is('my-agent-tickets') || isset($myAgentTickets) )
                    {{-- <td class="text-center" data-toggle="tooltip" data-placement="top" title="{{ \Carbon\Carbon::parse($ticket->updated_at)->format('M d H:ia') }}"> --}}
                    <td class="text-center" title="{{ \Carbon\Carbon::parse($ticket->updated_at)->format('M d H:ia') }}">
                        {{-- {{ \Carbon\Carbon::parse($ticket->updated_at)->format('M d H:i') }} --}}
                        {{ \App\Ticket::chat_get_time_ago2($ticket->updated_at) }}
                    </td>
                @else
                    {{-- <td class="text-center" data-toggle="tooltip" data-placement="top" title="{{ \Carbon\Carbon::parse($ticket->thread_started_at)->format('M d H:ia') }}">
                        {{ \Carbon\Carbon::parse($ticket->thread_started_at)->format('M d') }}
                    </td> --}}
                    <td class="text-center" data-toggle="tooltip" data-placement="top" title="{{ \Carbon\Carbon::parse($ticket->updated_at)->format('M d H:ia') }}">
                        {{ \Carbon\Carbon::parse($ticket->updated_at)->format('M d') }}
                    </td>
                @endif

                {{-- Auth id = 9 => Anne, special access for re-assigning tickets to an agent --}}
                {{-- @if ( Auth::user()->rolesByIdExists([\App\Role::ADMIN, \App\Role::MANAGER, \App\Role::DEVELOPER]) || Auth::id() == 9 ) --}}
                {{-- tmp condition for althey --}}
                @if ( !in_array(Auth::id(), [25, 26, 29]) ) 

                    <td class="text-right">
                        <div class="dropdown">
                            <a class="btn btn-sm btn-icon-only text-light" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-ellipsis-v"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right dropdown-menu-arrow">

                                {{-- <a class="dropdown-item updateTicket" href="#" data-toggle="modal" data-target="#modalUpdateTicket" data-ticket-id="{{ $ticket->id }}">Update</a> --}}
                            

                                {{-- @if ( Auth::user()->rolesByIdExists([\App\Role::MANAGER, \App\Role::ADMIN, \App\Role::DEVELOPER]) || Auth::id() == 9 ) --}}
                                    <a class="dropdown-item assignToMe" href="#" data-ticket-id="{{ $ticket->id }}">Assign to me</a>
                                {{-- @endif --}}

                                {{-- @if ( $ticket->status_id == \App\TicketStatus::STATUS_UNASSIGNED && $user->roles->first()->id !== \App\Role::AGENT  ) --}}
                                {{-- @if ( $ticket->status_id == \App\TicketStatus::STATUS_UNASSIGNED && Auth::user()->roles->first()->id !== \App\Role::AGENT  ) --}}
                            

                                <a class="dropdown-item assignTo" href="#" data-ticket-id="{{ $ticket->id }}" data-toggle="modal" data-target="#modalAssignTicket">Assign To</a>

                                @if( $ticket->channel_id != \App\TicketOrigin::ORIGIN_EBAY && $ticket->type_id != \App\TicketType::TYPE_SPAM )

                                 <a class="dropdown-item mark-ticket-as-spam" href="#" data-ticket-id="{{ $ticket->id }}" data-ticket-requester="{{ $ticket->requester }}">Mark as Spam</a>
                                 
                                @endif

                                {{-- @if( $ticket->type_id == \App\TicketType::TYPE_SPAM )

                                 <a class="dropdown-item unmark-ticket-as-spam" href="#" data-ticket-id="{{ $ticket->id }}" data-ticket-requester="{{ $ticket->requester }}">Remove as Spam</a>
                                 
                                @endif --}}

                            </div>
                        </div>
                    </td>

                @endif

            </tr>

            @empty

            <tr>
                <td colspan="6">No records found.</td>
            </tr>

            @endforelse

            </tbody>
        </table>

    </div>
    <!-- Card footer -->
    <div class="card-footer py-4">

        <div class="row">
            <div class="col-md-6">
                <span class="show-no-entries">Showing {{ $footerStartPageRowCount += 1 }} to 

                    @if($tickets->currentPage() !== $tickets->lastPage())
                        {{ $tickets->perPage() * $tickets->currentPage() }}
                    @else
                     {{ $tickets->total() }}
                    @endif
    
                    of {{ $tickets->total() }} entries</span>
            </div>

            <div class="col-md-6">
                <div class="float-right pagination justify-content-end mb-0">
                    {!! $tickets->onEachSide(2)->links() !!}
                </div>
            </div>

        </div>
        
    </div>
</div>