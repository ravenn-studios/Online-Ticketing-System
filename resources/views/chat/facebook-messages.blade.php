@foreach($tickets as $ticket)

    {{-- @php
        $internalDate = ''; // configure display condition of message time ago. by not displaying multiple rows of message time ago if the following messages has the same time ago
    @endphp --}}
    {{-- @if ($ticket->id == 3821) --}}

    @php
        $facebookPageModel = new \App\FacebookPage;  
    @endphp

    @if ($loop->index == 0)

        @foreach($ticket->facebookMessages as $key => $message)

            <!-- tmp condition for now -->
            @if ( in_array($message->from, $facebookPageModel->getFacebookPagesNames()) )
            
                <div class="message-content-block">
                    
                    {{-- @if ( empty($internalDate) || ( !empty($internalDate) && $internalDate == App\Ticket::get_time_ago( strtotime($message->internal_date) ) ) )
                        
                    @endif --}}

                    @if ( empty($timeAgoUpdatedAt) || $timeAgoUpdatedAt != date( 'M d, Y', strtotime($message->internal_date) ) )

                        <div class="text-muted text-center mb-2"><small>{{ App\Ticket::chat_get_time_ago( $message->internal_date ) }}</small></div>

                    @endif

                    {{-- @php
                        $timeAgoUpdatedAt = App\Ticket::chat_get_time_ago( $message->internal_date );
                    @endphp --}}

                    <div class="col-right text-left">

                        @if ( preg_match( '/<img/', base64_decode($message->message) ) || preg_match( '/<video/', base64_decode($message->message) ) )
                        
                            {!! base64_decode($message->message) !!}

                        @else

                            <p>{{ base64_decode($message->message) }}</p>

                        @endif

                    </div>

                </div>

            @else

                <div class="message-content-block">

                    @if ( empty($timeAgoUpdatedAt) || $timeAgoUpdatedAt != date( 'M d, Y', strtotime($message->internal_date) ) )

                        <div class="text-muted text-center mb-2"><small>{{ App\Ticket::chat_get_time_ago( $message->internal_date ) }}</small></div>

                    @endif
                    
                    {{-- @php
                        $timeAgoUpdatedAt = App\Ticket::chat_get_time_ago( $message->internal_date );
                    @endphp --}}

                    <div class="col-left text-left">
                        
                        @if ( preg_match( '/<img/', base64_decode($message->message) ) || preg_match( '/<video/', base64_decode($message->message) ) )
                        
                            {!! base64_decode($message->message) !!}

                        @else

                            <p>{{ base64_decode($message->message) }}</p>

                        @endif
                        
                    </div>

                </div>

            @endif

            @php
                $timeAgoUpdatedAt = date( 'M d, Y', strtotime($message->internal_date) );
            @endphp

            <div class="clearfix"></div>

            {{-- @php
                $internalDate = App\Ticket::get_time_ago( strtotime($message->internal_date) );
            @endphp --}}

        @endforeach

    @endif

@endforeach