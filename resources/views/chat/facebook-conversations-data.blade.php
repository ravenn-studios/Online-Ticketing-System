@php
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