<div class="card">
    <div class="loadOverlay">
        <img src="{{ asset('images/ajax-bar-loader.gif') }}">
    </div>
    <!-- Card header -->
    <div class="card-header border-0 rounded">
        <div class="float-left">
            <h3 class="mb-0">Users Tickets Limit</h3>
        </div>
    </div>
    <!-- Light table -->
    <div class="table-responsive">

        <table class="table align-items-center table-flush email-templates-listing">
            <thead class="thead-light">
            <tr>
                <th scope="col text-left" width="700">Name</th>
                <th scope="col text-center">Assigned Tickets</th>
                <th scope="col text-center">Limit</th>
                <th scope="col text-center" style="text-align: center;">Date</th>

                @if(Auth::user()->rolesByIdExists([\App\Role::MANAGER, \App\Role::ADMIN, \App\Role::DEVELOPER]))
                    <th scope="col text-center" class="text-center" width="250">Action</th>
                @endif

            </tr>
            </thead>
            <tbody class="list">

            @php
                $footerStartPageRowCount = $startPageRowCount = ($users->currentPage() - 1) * $users->perPage();
            @endphp

            @forelse($users as $_user)

                @php
                    $startPageRowCount++;
                @endphp

                <tr>
                    <td>{{ $_user->name }}</td>

                    <td class="text-center">{{ $_user->assignedTickets }}</td>

                    <td class="text-center">{{ $_user->ticketLimit->first()->limit }}</td>

                    <td class="text-center">{{ $_user->updated_at->format('M d, Y') }}</td>

                    @if(Auth::user()->rolesByIdExists([\App\Role::MANAGER, \App\Role::ADMIN, \App\Role::DEVELOPER]))
                        <td class="text-center">
                            <a href="#" class="updateUserTicketLimit" data-user-id="{{ $_user->id }}" data-ticket-limit="{{ $_user->ticketLimit->first()->limit }}" data-ticket-limit-id="{{ $_user->ticketLimit->first()->id }}" data-toggle="modal" data-target="#updateUserTicketLimit" title="Update">
                                <i class="far fa-edit"></i>
                            </a>
                        </td>
                    @endif
                    
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