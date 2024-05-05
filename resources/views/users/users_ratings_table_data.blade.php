<div class="card">
    <div class="loadOverlay">
        <img src="{{ asset('images/ajax-bar-loader.gif') }}">
    </div>
    <!-- Card header -->
    <div class="card-header border-0">
        <div class="float-left">
            <h3 class="mb-0">Users</h3>
        </div>
        {{-- <div class="float-right">
            <a class="btn btn-icon btn-primary btn-sm create-email-template" data-toggle="modal" data-target="#modalRegisterUser" type="button">
                <span class="btn-inner--icon"><i class="fas fa-user-plus"></i></span>
                <span class="btn-inner--text ml-0">Create User</span>
            </a>
        </div> --}}
    </div>
    <!-- Light table -->
    <div class="table-responsive">

        <table class="table align-items-center table-flush email-templates-listing">
            <thead class="thead-light">
            <tr>
                <th scope="col text-left" width="700">Name</th>
                <th scope="col text-center" width="400">Ratings</th>
                {{-- <th scope="col text-center" width="250">Date</th> --}}
                <th scope="col text-center" class="text-center" width="250">Action</th>
            </tr>
            </thead>
            <tbody class="list">

            @php
                $footerStartPageRowCount = $startPageRowCount = ($users->currentPage() - 1) * $users->perPage();
            @endphp

            @forelse($users as $user)

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
                        
                        <a href="#" class="viewReviews" data-user-id="{{ $user->id }}" data-chat-ids="{{ implode(',', $userChatIds) }}" data-toggle="modal" data-target="#modalViewReviews" title="View">

                            <div>{{ $countReviews }} Reviews</div>

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
                    {{-- <td>{{ $user->created_at->format('M d, Y') }}</td> --}}
                    <td class="text-center">
                        <a href="#" class="viewReviews" data-user-id="{{ $user->id }}" data-chat-ids="{{ implode(',', $userChatIds) }}" data-toggle="modal" data-target="#modalViewReviews" title="View">
                            <i class="fas fa-eye"></i>
                        </a>

                        {{-- <a href="#" class="updateUser" data-user-id="{{ $user->id }}" data-toggle="modal" data-target="#modalUpdateUser" title="Update">
                            <i class="far fa-edit"></i>
                        </a> --}}

                        {{-- <a href="#" class="deleteUser text-warning ml-2" data-user-id="{{ $user->id }}" data-toggle="modal" data-target="#modalConfirmDeleteUser" title="Delete">
                            <i class="far fa-trash-alt"></i>
                        </a> --}}
                    </td>
                </tr>

            @empty
                <tr>
                    <td colspan="3">No records found.</td>
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