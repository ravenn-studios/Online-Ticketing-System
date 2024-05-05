<div class="card">
    <div class="loadOverlay">
        <img src="{{ asset('images/ajax-bar-loader.gif') }}">
    </div>
    <!-- Card header -->
    <div class="card-header border-0 rounded">
        <div class="float-left">
            <h3 class="mb-0">Custom Pages</h3>
        </div>
        <div class="float-right">
            <a class="btn btn-icon btn-primary btn-sm create-custom-page text-white" data-toggle="modal" data-target="#modalCreateCustomPage" type="button">
                <span class="btn-inner--icon"><i class="far fa-file"></i></span>
                <span class="btn-inner--text ml-0">Create Custom Page</span>
            </a>
        </div>
    </div>
    <!-- Light table -->
    <div class="table-responsive">

        <table class="table align-items-center table-flush email-templates-listing">
            <thead class="thead-light">
            <tr>
                <th scope="col text-left" width="1100">Name</th>
                <th scope="col text-left" width="200">Slug</th>
                <th scope="col text-center" width="250">Date</th>
                <th scope="col text-center" class="text-center" width="250">Action</th>
            </tr>
            </thead>
            <tbody class="list">

            @php
                $footerStartPageRowCount = $startPageRowCount = ($customPages->currentPage() - 1) * $customPages->perPage();
            @endphp

            @forelse($customPages as $customPage)

                @php
                    $startPageRowCount++;
                @endphp

                <tr>
                    <td>{{ $customPage->name }}</td>

                    <td>{{ $customPage->slug }}</td>

                    <td>{{ $customPage->created_at->format('M d, Y') }}</td>

                    <td class="text-center">
                        <a href="#" class="updateCustomPage" data-custom-page-id="{{ $customPage->id }}" data-toggle="modal" data-target="#modalUpdateCustomPage" title="Update">
                            <i class="far fa-edit"></i>
                        </a>

                        <a href="#" class="deleteCustomPage text-warning ml-2" data-custom-page-id="{{ $customPage->id }}" data-toggle="modal" data-target="#modalConfirmDeleteCustomPage" title="Delete">
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

                    @if($customPages->currentPage() !== $customPages->lastPage())
                        {{ $customPages->perPage() * $customPages->currentPage() }}
                    @else
                     {{ $customPages->total() }}
                    @endif
    
                    of {{ $customPages->total() }} entries</span>
            </div>

            <div class="col-md-6">
                <div class="float-right pagination justify-content-end mb-0">
                    {!! $customPages->onEachSide(2)->links() !!}
                </div>
            </div>

        </div>
        
    </div>
</div>