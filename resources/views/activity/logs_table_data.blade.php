<div class="card">
    <div class="loadOverlay">
        <img src="{{ asset('images/ajax-bar-loader.gif') }}">
    </div>
    <!-- Card header -->
    <div class="card-header border-0 rounded">
        <div class="float-left">
            <h3 class="mb-0">Logs</h3>
        </div>
    </div>
    <!-- Light table -->
    <div class="table-responsive">

        <table class="table align-items-center table-flush email-templates-listing">
            <thead class="thead-light">
            <tr>
                <th scope="col text-left">Section</th>
                <th scope="col text-left" width="500">Description</th>
                <th scope="col text-center">Action By</th>
                <th scope="col text-center">Date</th>
            </tr>
            </thead>
            <tbody class="list">

            @php
                $footerStartPageRowCount = $startPageRowCount = ($logs->currentPage() - 1) * $logs->perPage();
            @endphp

            @forelse($logs as $log)

                @php
                    $startPageRowCount++;
                    $actionBy = \App\User::find($log->causer_id);
                @endphp

                <tr class="row-log" data-log-id="{{ $log->id }}">
                    <td>{{ $log->log_name }}</td>
                    <td>{{ $log->description }}</td>
                    {{-- <td>{{ $log->created_at->format('M d, Y h:ia') }}</td> --}}

                    @if ( !empty($log->causer_id) && $log->causer_id != null )
                        <td> {{ $actionBy->name }} <br> <span class="text-muted"> {{ $actionBy->email }} </span></td>
                    @else
                        
                        @if ( $log->log_name == 'Ticket' || $log->log_name == 'Message' )
                            <td>Automation</td>
                        @else
                            <td>-</td>
                        @endif

                    @endif

                    <td>{{ $log->created_at->format('M d, Y h:ia') }}</td>

                    <input class="hidden-log-property" type="hidden" value="{{ $log->properties }}">
                    
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

                    @if($logs->currentPage() !== $logs->lastPage())
                        {{ $logs->perPage() * $logs->currentPage() }}
                    @else
                     {{ $logs->total() }}
                    @endif
    
                    of {{ $logs->total() }} entries</span>
            </div>

            <div class="col-md-6">
                <div class="float-right pagination justify-content-end mb-0">
                    {!! $logs->onEachSide(2)->links() !!}
                </div>
            </div>

        </div>
        
    </div>
</div>