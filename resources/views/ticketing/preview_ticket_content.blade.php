<div class="card-header border-0">
  <div class="row align-items-center">
    <div class="col-xl-12">
      <div>
        <h3 class="mb-0 float-left email-subject">{{ $ticket->subject }}</h3>
        <span class="email-time-ago pt-1 float-right">{{ $ticket->thread_started_at }}</span>
      </div>
      <div class="clear-both"></div>
      <div class="mt-2">
        <span class="float-left reply-icon mr-1">
          <i class="fas fa-reply"></i>
        </span>
        <h3 class="mb-0 requester-email float-left">{{ $ticket->requester }}</h3>
      </div>
    </div>
  </div>
</div>

<div class="card-body">

    <hr>

    <div class="message-history">

        @foreach($ticket->messages as $message)

        <div class="row">
          <div class="col-md-10 ">
            <div>
              <i class="ni ni-circle-08"></i>
              <h4 class="ml-2">{{ $message->from }}</h4>
            </div>
          </div>
          <div class="col-md-2 ">
            <div>
              <h5 class="text-right mt-2">{{ \Carbon\Carbon::parse($message->created_at)->format('M d H:i') }}</h5>
            </div>
          </div>
          <div class="col-md-12 mt-3 message-content ">
            {!! $message->message !!}
          </div>
        </div>

        <br>

        @endforeach

    </div>
</div>