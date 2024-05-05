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
            // $tmpMessage = str_replace(array("\n", "\r"), '&nbsp;', $tmpMessage);
            $tmpMessage = trim($tmpMessage);
        @endphp

        <h4 title="{{ $ticket->subject }}">{!! substr( $ticket->subject, 0, 70 ) !!}..</h4>

        {{-- {!! $tmpMessage !!} --}}
        {!! substr( $tmpMessage, 0, 350) !!}

        {{-- @if ( !empty($ticket->status_badge()) ) --}}
        @if ( !is_null($ticket->messages->last()) && $ticket->origin_id != \App\TicketOrigin::ORIGIN_EBAY && $ticket->messages->count() > 1 )

            @php
                $tmpLastMessage = $ticket->messages->last();
                $tmpMessage     = base64_decode(str_replace(array('-', '_'), array('+', '/'), $tmpLastMessage->message));
                $tmpMessage     = preg_replace('/<\s*style.+?<\s*\/\s*style.*?>/si', ' ', $tmpMessage );
                $tmpMessage     = preg_replace('/<\s*meta.+?<\s*\/\s*meta.*?>/si', ' ', $tmpMessage );
                $tmpMessage     = strip_tags($tmpMessage);
                // $tmpMessage  = str_replace(array("\n", "\r"), '&nbsp;', $tmpMessage);
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