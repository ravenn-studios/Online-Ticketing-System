<div class="card">
    <div class="loadOverlay">
        <img src="{{ asset('images/ajax-bar-loader.gif') }}">
    </div>
    <!-- Card header -->
    <div class="card-header border-0 rounded">
        <div class="float-left">
            <h3 class="mb-0">Email Signatures</h3>
        </div>
        <div class="float-right">
            <a class="btn btn-icon btn-primary btn-sm create-signature text-white" data-toggle="modal" data-target="#modalCreateSignature" type="button">
                <span class="btn-inner--icon"><i class="fas fa-pencil-alt"></i></span>
                <span class="btn-inner--text ml-0">Create Signature</span>
            </a>
        </div>
    </div>
    <!-- Light table -->
    <div class="table-responsive">

        <table class="table align-items-center table-flush email-templates-listing">
            <thead class="thead-light">
            <tr>
                <th scope="col text-left" width="1100">Name</th>
                <th scope="col text-left" width="200">Status</th>
                <th scope="col text-center" width="250">Date</th>
                <th scope="col text-center" class="text-center" width="250">Action</th>
            </tr>
            </thead>
            <tbody class="list">

            @php
                $footerStartPageRowCount = $startPageRowCount = ($signatures->currentPage() - 1) * $signatures->perPage();
            @endphp

            @forelse($signatures as $signature)

                @php
                    $startPageRowCount++;
                @endphp

                <tr>
                    <td>{{ $signature->name }}</td>

                    @if ( $signature->active == App\Signature::ACTIVE )

                    <td>Active</td>

                    @else

                    <td>Inactive</td>

                    @endif

                    <td>{{ $signature->created_at->format('M d, Y') }}</td>
                    <td class="text-center">
                        <a href="#" class="updateSignature" data-signature-id="{{ $signature->id }}" data-toggle="modal" data-target="#modalUpdateSignature" title="Update">
                            <i class="far fa-edit"></i>
                        </a>

                        <a href="#" class="deleteSignature text-warning ml-2" data-signature-id="{{ $signature->id }}" data-toggle="modal" data-target="#modalConfirmDeleteSignature" title="Delete">
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

                    @if($signatures->currentPage() !== $signatures->lastPage())
                        {{ $signatures->perPage() * $signatures->currentPage() }}
                    @else
                     {{ $signatures->total() }}
                    @endif
    
                    of {{ $signatures->total() }} entries</span>
            </div>

            <div class="col-md-6">
                <div class="float-right pagination justify-content-end mb-0">
                    {!! $signatures->onEachSide(2)->links() !!}
                </div>
            </div>

        </div>
        
    </div>
</div>