<div class="card">
    <div class="loadOverlay">
        <img src="{{ asset('images/ajax-bar-loader.gif') }}">
    </div>
    <!-- Card header -->
    <div class="card-header border-0">
        <div class="float-left">
            <h3 class="mb-0">Spam Lists</h3>
        </div>
        {{-- <div class="float-right">
            <a class="btn btn-icon btn-primary btn-sm create-email-template" data-toggle="modal" data-target="#modalAddToSpam" type="button">
                <span class="btn-inner--icon"><i class="ni ni-ruler-pencil"></i></span>
                <span class="btn-inner--text ml-0">Add to Spam</span>
            </a>
        </div> --}}
    </div>
    <!-- Light table -->
    <div class="table-responsive">

        <table class="table align-items-center table-flush email-templates-listing">
            <thead class="thead-light">
            <tr>
                <th scope="col text-left" width="1100">Keyword</th>
                <th scope="col text-center" width="250">Action By</th>
                <th scope="col text-center" width="250">Date</th>
                <th scope="col text-center" class="text-center" width="250">Action</th>
            </tr>
            </thead>
            <tbody class="list">

            @php
                $footerStartPageRowCount = $startPageRowCount = ($spamFilters->currentPage() - 1) * $spamFilters->perPage();
            @endphp

            @forelse($spamFilters as $spamFilter)

                @php
                    $startPageRowCount++;
                @endphp

                <tr>
                    <td>{{ $spamFilter->keyword }}</td>
                    <td>{{ $spamFilter->user->name }}</td>
                    <td>{{ $spamFilter->created_at->format('M d, Y H:i') }}</td> 
                    <td class="text-center">
                        {{-- <a href="#" class="updateSpam" data-template-id="{{ $spamFilter->id }}" data-toggle="modal" data-target="#modalUpdateEmailTemplate" title="Update">
                            <i class="far fa-edit"></i>
                        </a> --}}

                        <a href="#" class="remove-as-spam text-warning ml-2" data-spam-filter-id="{{ $spamFilter->id }}" title="Delete">
                            <i class="far fa-trash-alt"></i>
                        </a>
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

                    @if($spamFilters->currentPage() !== $spamFilters->lastPage())
                        {{ $spamFilters->perPage() * $spamFilters->currentPage() }}
                    @else
                     {{ $spamFilters->total() }}
                    @endif
    
                    of {{ $spamFilters->total() }} entries</span>
            </div>

            <div class="col-md-6">
                <div class="float-right pagination justify-content-end mb-0">
                    {!! $spamFilters->onEachSide(2)->links() !!}
                </div>
            </div>

        </div>
        
    </div>
</div>