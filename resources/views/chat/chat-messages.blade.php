@php
    $isAgentsCustomer = '';
@endphp
@foreach($chats as $chat)

    {{-- @php
        $internalDate = ''; // configure display condition of message time ago. by not displaying multiple rows of message time ago if the following messages has the same time ago
    @endphp --}}
    {{-- @if ($ticket->id == 3821) --}}
    @if ($loop->index == 0)
        
        @php
            $agentChatLog     = \App\AgentChatLog::find($chat->id);
            $isAgentsCustomer = ($agentChatLog->user_id == Auth::user()->id) ? true : false;
        @endphp

        @if ( !$chat->chatMessages->count() && !$chat->agent_start_chat && Auth::user()->is_online == true )

            <button class="btn btn-primary start-chat" data-chat-id="{{ $chat->id }}">Start Chat</button>
       
        @elseif( $chat->agent_start_chat && !$chat->chatMessages->count() && !$isAgentsCustomer && !$chat->agent_start_chat == \App\Chat::START_CHAT_AGENT_NO_RESPONSE )

            <p class="mt-2 ml-2 agent-took-over-the-chat">An agent took over the chat.</p>
            
        @elseif( $chat->agent_start_chat == \App\Chat::START_CHAT_AGENT_NO_RESPONSE )
            
            <p class="mt-2 ml-2 agent-no-response">The customer waited for too long.</p>
            {{-- <button class="btn btn-primary start-chat" data-chat-id="{{ $chat->id }}" disabled="disabled">Start Chat</button> --}}

        @endif

        @foreach($chat->chatMessages as $key => $message)

            <!-- tmp condition for now -->
            {{-- @if ( strpos($message->from, 'rodney@frankiesautoelectrics.com.au') !== false ) --}}
            @if ( $message->from == 'agent' )
            
                <div class="message-content-block my-3">
                    
                    {{-- @if ( empty($internalDate) || ( !empty($internalDate) && $internalDate == App\Ticket::get_time_ago( strtotime($message->internal_date) ) ) )
                        
                    @endif --}}

                    @if ( empty($timeAgoUpdatedAt) || $timeAgoUpdatedAt != date( 'M d, Y', strtotime($message->created_at) ) )

                        {{-- <div class="text-muted text-center mb-2"><small>{{ App\Ticket::chat_get_time_ago( $message->created_at ) }}</small></div> --}}
                        <div class="text-muted text-center mb-2"><small>{{ \Carbon\Carbon::parse($message->created_at)->format('F d, Y h:i:sA') }}</small></div>

                    @endif

                    {{-- @php
                        $timeAgoUpdatedAt = App\Ticket::chat_get_time_ago( $message->internal_date );
                    @endphp --}}

                    <div class="col-right text-left">

                        @php

                            $msg = base64_decode($message->message);

                            if ( base64_decode($msg, true) && $message->file_id != NULL )
                            {
                                $msg = base64_decode($msg);
                            }

                            //this is for cases where agent copy pasted an image to send.
                            if( empty($message->message) && $message->file_id != NULL )
                            {
                                $file     = \App\File::find($message->file_id);
                                $fileName = $file->name.$file->extension;
                                $image    = '<img src="data:image/jpeg;charset=utf-8;base64,'.base64_encode(\Storage::get('public/images/'.$fileName)).'" />';

                                $msg = $image;
                            }

                        @endphp

                        @if ( preg_match( '/<img/', $msg ) )
                        
                            {!! $msg !!}

                        @else

                            {{-- <p>{{ $msg }}</p> --}}
                            <p class="mb-0" style="display: inline-block;">{!! $msg !!}</p>

                        @endif

                        <span class="ml-2" title="{{ $message->user->name }}" style="display: inline-block;">
                            {!! $message->user->chatAvatar()  !!}
                        </span>

                    </div>

                    <div class="clearfix"></div>

                    <div class="text-muted text-right" style="margin-right: 60px;">
                        <small>{{ \Carbon\Carbon::parse($message->created_at)->format('h:i:s A') }}</small>
                    </div>

                </div>

            @else

                <div class="message-content-block my-3">

                    @if ( empty($timeAgoUpdatedAt) || $timeAgoUpdatedAt != date( 'M d, Y', strtotime($message->created_at) ) )

                        {{-- <div class="text-muted text-center mb-2"><small>{{ App\Ticket::chat_get_time_ago( $message->created_at ) }}</small></div> --}}

                        <div class="text-muted text-center mb-2"><small>{{ \Carbon\Carbon::parse($message->created_at)->format('F d, Y h:i:s A') }}</small></div>

                    @endif
                    
                    {{-- @php
                        $timeAgoUpdatedAt = App\Ticket::chat_get_time_ago( $message->internal_date );
                    @endphp --}}

                    <div class="col-left text-left">

                        @php

                            // $msg = decode($message->message);
                            $msg = base64_decode($message->message);

                            if ( base64_decode($msg, true) && $message->file_id != NULL )
                            {
                                $msg = base64_decode($msg);
                            }

                        @endphp
                        
                        @if ( preg_match( '/<img/', $msg ) )
                        
                            {!! $msg !!}

                        @else

                            {{-- <p>{{ $msg }}</p> --}}
                            <p class="mb-0">{!! $msg !!}</p>

                        @endif
                        
                    </div>

                    <div class="clearfix"></div>

                    <div class="text-muted text-left">
                        <small>{{ \Carbon\Carbon::parse($message->created_at)->format('h:i:s A') }}</small>
                    </div>

                </div>

            @endif

            @php
                $timeAgoUpdatedAt = date( 'M d, Y', strtotime($message->created_at) );
            @endphp

            <div class="clearfix"></div>

            {{-- @php
                $internalDate = App\Ticket::get_time_ago( strtotime($message->internal_date) );
            @endphp --}}

        @endforeach

        @if ($chat->status_id == \App\Chat::STATUS_CLOSED)
            <div class="message-content-block text-muted text-center mb-2">
                <small>{{ \Carbon\Carbon::parse($chat->updated_at)->format('F d, Y h:i:s A') }}</small>
                <p style="width: 100%;max-width: 100%;">*** {{ strtoupper( $chat->agent() ? $chat->agent()->user->name : 'customer') }} left the chat ***</p>
            </div>
        @endif

    @endif

@endforeach