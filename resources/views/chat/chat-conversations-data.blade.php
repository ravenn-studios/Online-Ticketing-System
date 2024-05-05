@php
    $timeAgoUpdatedAt         = '';
    $facebookPageName         = '';
    $facebookPageDisplayPhoto = '';
    $chatRequester            = '';
    $chatRequesterEmail            = '';
    $chatId                   = 0;
    $threadId                 = 0;
@endphp

@forelse($chats as $chat)
    
    @php
        $chatRequester      = $chat->customer->name;
        $chatRequesterEmail = $chat->customer->email;
        $classInaccessible = 'inaccessible';

        if ( in_array( $chat->id, $chatIdsHaveAccess) )
        {
            $classInaccessible = '';
        }
        
        if ( $chat->status_id == \App\TicketStatus::STATUS_CLOSED || $chat->status_id == \App\TicketStatus::STATUS_UNASSIGNED || $chat->status_id == \App\TicketStatus::STATUS_SOLVED )
        {
            $classInaccessible = '';
        }

    @endphp
    
    @if ( $loop->index == 0 && $setActiveConversation == true )

        @php
            $chatRequester = $chat->customer->name;
            $chatId        = $chat->id;
            // $threadId        = $chat->thread_id;
        @endphp

       <a class="message-block active-conversation {{ $classInaccessible }}" data-chat-id="{{ $chat->id }}" data-status-id="{{ $chat->status_id }}" data-chat-requester="{{ $chatRequester }}" data-chat-requester-email="{{ $chatRequesterEmail }}" href="#">

        {{-- <a class="message-block active-conversation" data-chat-id="{{ $chat->id }}" data-chat-requester="{{ $chat->name }}" href="#"> --}}

    @else

        @if ( $activeChatId != 0 && $activeChatId == $chat->id )
            <a class="message-block active-conversation {{ $classInaccessible }}" data-chat-id="{{ $chat->id }}" data-status-id="{{ $chat->status_id }}" data-chat-requester="{{ $chatRequester }}" data-chat-requester-email="{{ $chatRequesterEmail }}" href="#">
        @else
            <a class="message-block {{ $classInaccessible }}" data-chat-id="{{ $chat->id }}" data-status-id="{{ $chat->status_id }}" data-chat-requester="{{ $chatRequester }}" data-chat-requester-email="{{ $chatRequesterEmail }}" href="#">
        @endif

    @endif
            <div class="card-body">

                <h4 class="card-title mb-2 float-left chat-requester">{{ $chatRequester }}</h4>
                {{-- <span class="chat-time-ago pt-1 float-right">43 minutes ago</span> --}}

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
                
                @php
                    $chatMessagesCount = $chat->chatMessages()->where('read', false)->count()
                @endphp

                @if ( $activeChatId != $chat->id )

                    @if ( $chatMessagesCount > 1 )

                        <span class="badge badge-pill badge-warning new-message-warning">{{ $chatMessagesCount }} New Messages</span>

                    @elseif ( $chatMessagesCount == 1 )

                        <span class="badge badge-pill badge-warning new-message-warning">{{ $chatMessagesCount }} New Message</span>

                    @elseif ( !$chat->chatMessages()->count() && !$chat->agent_start_chat )

                        <span class="badge badge-pill badge-warning new-message-warning">New Message Request</span>

                    @endif

                    <!-- maybe another elseif for chat messages initiated but to real messages yet. -->

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