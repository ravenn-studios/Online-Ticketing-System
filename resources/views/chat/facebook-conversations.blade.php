<div class="row">

    <div class="col-xl-3 facebook-chat ticket-listing pr-0 pl-0">

        <div class="card border-radius-bl-0 mb-0">
            
            <div class="form-group mb-1 pt-1 pb-1 pl-2 pr-2">

                <div class="input-group">

                    <div class="input-group-prepend">
                        <span class="input-group-text border-and-radius-none" id="inputGroup-sizing-default"><i class="fas fa-search"></i></span>
                    </div>

                    <input type="text" class="form-control search-facebook-conversations border-and-radius-none" placeholder="Search Inbox.." aria-label="Search Inbox" aria-describedby="inputGroup-sizing-default">

                </div>

            </div>

        </div>

        <div class="card custom-h border-radius-tl-0 chat-conversations-wrapper">

            {{-- @include('chat.facebook-conversations-data') --}}

            <!-- please update chat.facebook-conversations-data whenever you updated anything inside this chat-conversations-wrapper, it is being used to render the search results list of conversations -->

            @php
                $timeAgoUpdatedAt         = '';
                $facebookPageName         = '';
                $facebookPageDisplayPhoto = '';
                $ticketRequester          = '';
                $ticketId                 = 0;
                $threadId                 = 0;
            @endphp

            @forelse($tickets as $ticket)
                
                @if ( $loop->index == 0 )

                    @php
                        $facebookPageName         = $ticket->facebookPage->name;
                        $facebookPageDisplayPhoto = $ticket->facebookPage->displayPhoto();
                        $ticketRequester = $ticket->requester;
                        $ticketId        = $ticket->id;
                        $threadId        = $ticket->thread_id;
                    @endphp

                    <a class="message-block active-conversation" data-ticket-id="{{ $ticket->id }}" data-thread-id="{{ $ticket->thread_id }}" data-ticket-requester="{{ $ticket->requester }}" href="#">

                @else
                    <a class="message-block" data-ticket-id="{{ $ticket->id }}" data-thread-id="{{ $ticket->thread_id }}" data-ticket-requester="{{ $ticket->requester }}" href="#">
                @endif
                        <div class="card-body">

                            <h4 class="card-title mb-2 float-left ticket-requester">{{ $ticket->requester }}</h4>
                            {{-- <span class="chat-time-ago pt-1 float-right">43 minutes ago</span> --}}

                            <span class="chat-time-ago pt-1 float-right">{{ App\Ticket::get_time_ago( strtotime($ticket->updated_at) ) }}</span>

                            <div class="clear-both"></div>

                            {{-- <h6 class="card-subtitle mb-2 float-left">{{ $ticket->requester }}</h6> --}}
                            <p class="card-text">{{ $ticket->snippet }}</p>

                            <span class="badge badge-pill badge-primary mr-2">{{ $ticket->status->name }}</span>

                            <span class="badge badge-pill badge-info">{{ $ticket->type->name }}</span>

                            {!! $ticket->facebookPage->displayPhotoSm($ticket->facebookPage->name) !!}

                        </div>
                    </a>

            @empty
                <p class="p-3 font-weight-bold">No messages found.</p>
            @endforelse

        </div>

    </div>

    <div class="col-xl-7 ticket-content p-0">
        <div class="card custom-h border-radius-0">
            <div class="card-header border-0 border-b-gray">
                <div class="row align-items-center">
                    <div class="col-xl-12">
                        <div>
                            <h2 class="mb-0 float-left ticket-requester">{{ $ticketRequester }}</h2>
                            {{-- <a href="#" class="btn btn-sm btn-primary text-white float-right syncFacebookConversation">Sync <i class="fas fa-sync"></i></a> --}}
                            <a href="#" class="btn btn-sm btn-primary text-white float-right syncFacebookConversation">Sync All <i class="fas fa-sync"></i></a>
                            {{-- <span class="email-time-ago pt-1 float-right">43 minutes ago</span>  --}}
                        </div>
                        <div class="clear-both"></div>
                        {{-- <div class="mt-1">
                            <h3 class="mb-0 email-snippet">
                                <span class="content">"Hey, I need help with Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation"</span>
                            </h3>
                        </div> --}}
                    </div>
                    {{-- <div class="col-xl-1 text-right">
                        
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
                    </div> --}}
                </div>
            </div>

            <div class="card-body p-0">

                <div class="facebook-chat-messages-wrapper pl-2 pr-2">
                    
                    @include('chat.facebook-messages')

                </div>

                <div class="clear-both"></div>

                <div class="form-group facebookReplyBlock">
                    
                    {{-- <form id="sendFacebookReply"> --}}
                        {{-- {{ csrf_field() }} --}}
                        <div class="input-group pl-2 pr-2">
                            <input type="hidden" class="ticketId" value="{{ $ticketId }}" />
                            <input type="hidden" class="threadId" value="{{ $threadId }}" />
                            <textarea class="form-control inputFacebookReply" placeholder="Write a reply..." autocomplete="off"></textarea>
                            <div class="input-group-append">
                                <button class="btn btn-outline-primary" type="submit" id="sendFacebookReply">Send</button>
                            </div>
                        </div>
                    {{-- </form> --}}
                    
                </div>

            </div>
            
        </div>
    </div>

    <div class="col-xl-2 facebook-conversation-status-wrapper right-sidebar p-0">

        <div class="card custom-h border-radius-l-0">

            <div class="card-header facebookPageInfoWrapper">
                {{-- <h3 class="mb-0 float-left">Status</h3> --}}
                {!! $facebookPageDisplayPhoto !!}
                <h4 class="facebook-page-name ml-2">{{ $facebookPageName }}</h4>
            </div>

            <div class="card-body">

                <div class="alert alert-success" role="alert">
                    <strong>Status</strong> has been updated.
                </div>

                <div class="alert alert-danger" role="alert">
                    Something went wrong, please try again.
                </div>

                <div class="form-group mb-3">

                    <label class="form-control-label" for="input-address">Status</label>

                    <div class="input-group input-group-merge input-group-alternative">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-bars"></i></span>
                        </div>
                        <select class="form-control form-control-sm ticketStatus">
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
                        <select class="form-control form-control-sm ticketType">
                            <option hidden>Type</option>
                            @foreach($ticketTypes as $ticketType)
                                <option value="{{ $ticketType->id }}">{{ $ticketType->name }}</option>
                            @endforeach
                        </select>
                    </div>

                </div>

                <div class="form-group">
                    <button class="btn btn-primary btn-sm update-facebook-conversation-status float-right">Update</button>
                </div>
                
            </div>

        </div>
        
    </div>

</div>