<div class="table-responsive">

  <table class="table align-items-center table-flush">
      <thead class="thead-light">
        <tr>
          <th scope="col">name</th>
          <th scope="col">Ratings</th>
          <th scope="col">Chat Availability</th>
          <th class="text-center" scope="col">Action</th>
        </tr>
      </thead>
      <tbody>

        @php
          $footerStartPageRowCount = $startPageRowCount = ($users->currentPage() - 1) * $users->perPage();
        @endphp

        @forelse ($users as $user)

          @php
            $startPageRowCount++;
          @endphp
          {{-- {{ dump($user->chatLogs()->where('ended_at','<>', NULL)->get()) }} --}}
          <tr>
              <td>{{ $user->name }}</td>
              <td>

                  @php

                      $chatLogs     = $user->chatLogs()->where('ended_at','<>', NULL)->get('chat_id');
                      $avgRatings   = 0;
                      $countReviews = 0;
                      $userChatIds  = Array();
                      if ( $chatLogs->count() )
                      {
                          // $countChats   = $chatLogs->count();
                          $sumOfRatings = 0;
                          $avgRatings   = 0;
                          
                          foreach($chatLogs as $chatLog)
                          {
                              
                              $chat = \App\Chat::find($chatLog->chat_id);

                              if ( $chat->rating > 0 )
                              {
                                  $countReviews++;
                                  
                                  $sumOfRatings += $chat->rating;

                                  array_push($userChatIds, $chat->id);
                              }

                          }

                          $avgRatings = $sumOfRatings / $countReviews;

                      }

                  @endphp
                  
                  <a href="#" onclick="return false;" @if( $countReviews ) class="viewReviews" data-user-id="{{ $user->id }}" data-chat-ids="{{ implode(',', $userChatIds) }}" data-toggle="modal" data-target="#modalViewAgentReviews" title="View" @endif>

                      <div>{{ $countReviews }} @if ($countReviews > 1) Reviews @else Review @endif</div>

                      <span class="chat-rating">
                          @for ($i = 1; $i <= 5; $i++)
                              
                              @if ( $i <= $avgRatings )
                                  <i class="fas fa-star orange"></i>
                              @else                                    
                                  <i class="fas fa-star faded-orange"></i>
                              @endif

                          @endfor
                      </span>
                      
                  </a>

                  </div>

              </td>
              <td>
                <span class="badge badge-dot mr-4">
                    <i @if($user->is_online) class="bg-success" @else class="bg-warning" @endif></i>
                    <span class="status">@if($user->is_online) online @else offline @endif</span>
                  </span>
              </td>
              <td class="text-center">
                  <a href="#" onclick="return false;" title="View" @if( $countReviews ) class="viewReviews" data-user-id="{{ $user->id }}" data-chat-ids="{{ implode(',', $userChatIds) }}" data-toggle="modal" data-target="#modalViewAgentReviews" title="View" @endif>
                      <i class="fas fa-eye"></i>
                  </a>
              </td>
          </tr>
            
        @empty
            <p>No results found.</p>
        @endforelse

        
      </tbody>
    </table>

    <!-- Card footer -->
    <div class="card-footer py-4">

      <div class="row">
          <div class="col-md-6">
              <span class="show-no-entries">Showing {{ $footerStartPageRowCount += 1 }} to 

                  @if($users->currentPage() !== $users->lastPage())
                      {{ $users->perPage() * $users->currentPage() }}
                  @else
                    {{ $users->total() }}
                  @endif

                  of {{ $users->total() }} entries</span>
          </div>

          <div class="col-md-6">
              <div class="float-right pagination justify-content-end mb-0">
                  {!! $users->onEachSide(2)->links() !!}
              </div>
          </div>

      </div>
        
    </div>
    
</div>