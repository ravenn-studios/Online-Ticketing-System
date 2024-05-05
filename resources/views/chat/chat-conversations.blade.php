<div class="row">
    
    <div class="col-xl-3 chat chat-listing pr-0 pl-0">

        <div class="card border-radius-bl-0 mb-0">
            
            <div class="form-group mb-1 pt-1 pb-1 pl-2 pr-2">

                <div class="input-group">

                    <div class="input-group-prepend">
                        <span class="input-group-text border-and-radius-none" id="inputGroup-sizing-default"><i class="fas fa-search"></i></span>
                    </div>

                    <input type="text" class="form-control search-chat-conversations border-and-radius-none" placeholder="Search Inbox.." aria-label="Search Inbox" aria-describedby="inputGroup-sizing-default">

                    <select class="form-control chatStatusFilter">
                        
                        @foreach( $ticketStatus as $status )

                            @if( $loop->index == 0 )
                                <option value="{{ $status->id }}" selected>{{ $status->name }}</option>
                            @else
                                <option value="{{ $status->id }}">{{ $status->name }}</option>
                            @endif
    
                        @endforeach
                        
                        <option value="unread">Unread</option>

                        {{-- <option value="agent_no_response">Agent No Response</option> --}}

                    </select>

                </div>

            </div>

            <div class="form-group mb-1 pt-1 pb-1 pl-2 pr-2 all-my-chats-block display-n">
                <div class="btn-group btn-group-toggle" data-toggle="buttons" style="width: 100%;">
                    <label class="btn btn-outline-primary active">
                      <input class="all-chats" type="radio" name="chats-radio" id="all-chats" autocomplete="off" checked="checked"> All
                    </label>
                    <label class="btn btn-outline-primary">
                      <input class="my-chats" type="radio" name="chats-radio" id="my-chats" autocomplete="off"> My Chats
                    </label>
                </div>
            </div>

        </div>

        <div class="card custom-h border-radius-tl-0 chat-conversations-wrapper">

            {{-- @include('chat.facebook-conversations-data') --}}

            <!-- please update chat.facebook-conversations-data whenever you updated anything inside this chat-conversations-wrapper, it is being used to render the search results list of conversations -->

            @php
                $timeAgoUpdatedAt          = '';
                $facebookPageName          = '';
                $facebookPageDisplayPhoto  = '';
                $chatRequester             = '';
                $chatRequesterEmail        = '';
                $chatId                    = 0;
                $chatStatusId              = 0;
                $threadId                  = 0;
                $_chat                     = '';
                $isAgentsCustomer          = '';
                $chatRequesterDisplay      = '';
                $chatRequesterEmailDisplay = '';
            @endphp

            @forelse($chats as $chat)

                @php
                    $chatRequester      = $chat->customer->name;
                    $chatRequesterEmail = $chat->customer->email;
                @endphp
                
                @if ( $loop->index == 0 )

                    @php
                        $chatId                    = $chat->id;
                        $chatStatusId              = $chat->status_id;
                        $_chat                     = $chat;
                        $agentChatLog              = \App\AgentChatLog::find($chat->id);
                        $isAgentsCustomer          = ($agentChatLog->user_id == Auth::user()->id) ? true : false;
                        $chatRequesterDisplay      = $chat->customer->name;
                        $chatRequesterEmailDisplay = $chat->customer->email;
                    @endphp

                    <a class="message-block active-conversation" data-chat-id="{{ $chat->id }}" data-status-id="{{ $chat->status_id }}" data-chat-requester="{{ $chatRequester }}" data-chat-requester-email="{{ $chatRequesterEmail }}" href="#">

                @else
                    <a class="message-block" data-chat-id="{{ $chat->id }}" data-status-id="{{ $chat->status_id }}" data-chat-requester="{{ $chatRequester }}" data-chat-requester-email="{{ $chatRequesterEmail }}" href="#">
                @endif
                        <div class="card-body">

                            <h4 class="card-title mb-2 float-left chat-requester">{{ $chatRequester }}</h4>
                            {{-- <span class="chat-time-ago pt-1 float-right">43 minutes ago</span> --}}

                            {{-- <span class="chat-time-ago pt-1 float-right">{{ App\Ticket::get_time_ago( strtotime($chat->chatMessages->last()->created_at) ) - $chat->chatMessages->last()->created_at }}</span> --}}
                            <span class="chat-time-ago pt-1 float-right">
                                @if ( $chat->chatMessages->count() )
                                    {{ App\Ticket::get_time_ago( strtotime($chat->chatMessages->last()->created_at) ) }}
                                @else
                                    {{ App\Ticket::get_time_ago( strtotime($chat->updated_at) ) }}
                                @endif
                            </span>

                            <div class="clear-both"></div>

                            {{-- <h6 class="card-subtitle mb-2 float-left">{{ $chat->requester }}</h6> --}}
                            {{-- <p class="card-text">{{ $chat->snippet }}</p> --}}

                            <span class="badge badge-pill badge-primary mr-2">{{ $chat->status->name }}</span>

                            @if ( $chat->status_id == \App\TicketStatus::STATUS_CLOSED )
                                <span class="chat-rating">
                                    @for ($i = 1; $i <= 5; $i++)
                                        
                                        @if ( $i <= $chat->rating )
                                            <i class="fas fa-star orange"></i>
                                        @else                                    
                                            <i class="fas fa-star faded-orange"></i>
                                        @endif

                                    @endfor
                                </span>
                            @endif

                            @if ( !empty($chat->remarks) )
                                <span class="chat-remarks">
                                    <i class="fas fa-comment-dots ml-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ $chat->remarks }}"></i>
                                </span>
                            @endif

                            <!-- $loop->index is to avoid showing the new message badge if this block is set to active.. -->
                            @php
                                $chatMessagesCount = $chat->chatMessages()->where('read', false)->count()
                            @endphp

                            @if ( $loop->index != 0 )

                                @if ( $chatMessagesCount > 1 )

                                    <span class="badge badge-pill badge-warning new-message-warning">{{ $chatMessagesCount }} New Messages</span>

                                @elseif ( $chatMessagesCount == 1 )

                                    <span class="badge badge-pill badge-warning new-message-warning">{{ $chatMessagesCount }} New Message</span>

                                    @elseif ( !$chat->chatMessages()->count() && !$chat->agent_start_chat )

                                    <span class="badge badge-pill badge-warning new-message-warning">New Message Request</span>

                                @endif

                            @else

                                <!-- add query here to set all messages as read = true  -->

                            @endif

                            {{-- @if ( !$chat->chatMessages->count() )

                                <span class="badge badge-pill badge-warning new-message-warning">1 New Message</span>

                            @endif --}}

                            {{-- <span class="badge badge-pill badge-info">{{ $chat->type->name }}</span> --}}

                            {{-- {!! $chat->facebookPage->displayPhotoSm($chat->facebookPage->name) !!} --}}

                        </div>
                    </a>

            @empty
                <p class="p-3 font-weight-bold">No messages found.</p>
            @endforelse

        </div>

    </div>

    <div class="col-xl-7 chat-content p-0">
        <div class="card custom-h border-radius-0">

            <div class="loader spinner-grow text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>

            <div class="card-header border-0 border-b-gray">
                <div class="row align-items-center">
                    <div class="col-xl-12">

                        <div class="float-left">
                            <h2 class="mb-0 chat-requester">{{ $chatRequesterDisplay }}</h2>
                            <span class="text-muted text-sm chat-requester-email valign-m">{{ $chatRequesterEmailDisplay }}</span>
                        </div>

                        <div class="pl-2 pr-2 all-my-chats-block display-n float-right" style="display: block;width: 40%;">
                            <div class="btn-group btn-group-toggle" data-toggle="buttons" style="width: 100%;">
                                <label class="btn btn-outline-primary">
                                  <input class="display-current-chats" type="radio" name="display-chats-radio" autocomplete="off" checked="checked"> Current
                                </label>
                                <label class="btn btn-outline-primary active">
                                  <input class="display-past-chats" type="radio" name="display-chats-radio" autocomplete="off"> Past Chats
                                </label>
                            </div>
                        </div>

                        <div class="clear-both"></div>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">

                <div class="chat-messages-wrapper pl-2 pr-2">
                    
                    @include('chat.chat-messages')

                </div>

                {{-- <div class="clear-both"></div> --}}

                <div class="form-group chatReplyBlock mt-5">

                    {{-- <form id="sendFacebookReply"> --}}
                        {{-- {{ csrf_field() }} --}}
                        <div class="input-group pl-2 pr-2">

                            <button class="btn btn-warning btn-sm" id="end-chat" @if( (empty($_chat) || ($_chat ? ($_chat->chatMessages->count() <= 0) : 0)) && !$isAgentsCustomer) disabled="disabled" @endif>End Chat</button>

                            <input type="hidden" class="ticketId" value="{{ $chatId }}"/>
                            {{-- <input type="hidden" class="threadId" value="{{ $threadId }}" /> --}}

                            {{-- @if ( !empty($_chat)) --}}

                                {{-- @if ( $_chat->chatMessages->count() )
                                    <textarea class="form-control inputChatReply" placeholder="Write a reply..." autocomplete="off"></textarea>
                                @else
                                    <textarea class="form-control inputChatReply" placeholder="Write a reply..." autocomplete="off" disabled="disabled"></textarea>
                                @endif --}}

                                <textarea class="form-control inputChatReply" placeholder="Write a reply..." autocomplete="off" @if( (empty($_chat) || ($_chat ? ($_chat->chatMessages->count() <= 0) : 0)) && !$isAgentsCustomer) disabled="disabled" @endif></textarea>

                            {{-- @endif --}}
                            
                            <div class="input-group-append">
                                <button class="btn btn-outline-primary" type="submit" id="sendChatReply">Send</button>
                            </div>
                        </div>
                    {{-- </form> --}}
                    
                </div>

            </div>
            
        </div>
    </div>

    <div class="col-xl-2 chat-conversation-status-wrapper right-sidebar p-0">

        <div class="card custom-h border-radius-l-0">

            <div class="card-header chatInfoWrapper">
                {{-- <h3 class="mb-0 float-left">Status</h3> --}}
                {{-- {!! $facebookPageDisplayPhoto !!} --}}
                {{-- <h4 class="chatRequesterName ml-2">{{ $chatRequester }}</h4> --}}
                <h4 class="chatRequesterName ml-2">Update Status</h4>
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

                        <select class="form-control form-control-sm chatStatus">

                            <option hidden>Status</option>

                            @foreach($ticketStatus as $val)

                                @if ( $chatStatusId == $val->id )
                                
                                    <option value="{{ $val->id }}" selected>{{ $val->name }}</option>

                                @else

                                    <option value="{{ $val->id }}">{{ $val->name }}</option>

                                @endif

                            @endforeach

                        </select>
                    </div>

                </div>

                {{-- <div class="form-group mb-3">

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

                </div> --}}

                <div class="form-group">
                    <button class="btn btn-primary btn-sm update-chat-conversation-status float-right">Update</button>
                </div>
                
            </div>

        </div>
        
    </div>

</div>