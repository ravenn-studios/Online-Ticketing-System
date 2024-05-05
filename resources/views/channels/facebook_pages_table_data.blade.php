<div class="card">
    <div class="loadOverlay">
        <img src="{{ asset('images/ajax-bar-loader.gif') }}">
    </div>
    <!-- Card header -->
    <div class="card-header border-0">
        <div class="float-left">
            <h3 class="mb-0">Pages</h3>
        </div>

        <div class="float-right">
            <a href="{{ App\Facebook::getLoginUrl() }}" class="btn btn-facebook btn-icon text-white">
                <span class="btn-inner--icon"><i class="fab fa-facebook"></i></span>
                <span class="btn-inner--text">Continue With Facebook</span>
            </a>
        </div>
        {{-- <div class="float-right">
            <a class="btn btn-icon btn-primary btn-sm text-white create-email-support" data-toggle="modal" data-target="#modalCreateEmailSupport" type="button">
                <span class="btn-inner--icon"><i class="far fa-envelope"></i></span>
                <span class="btn-inner--text ml-0">Add Email</span>
            </a>
        </div> --}}
    </div>
    <!-- Light table -->
    <div class="table-responsive">

        <table class="table align-items-center table-flush email-support-listing">
            <thead class="thead-light">
            <tr>
                <th scope="col text-left" width="250">Name</th>
                {{-- <th scope="col text-left" width="250">Profile</th> --}}
                <th scope="col text-center" width="120">Page ID</th>
                <th scope="col text-center" width="250">Date</th>
                {{-- <th scope="col text-center" class="text-center" width="250">Action</th> --}}
            </tr>
            </thead>
            <tbody class="list">

            @php
                $footerStartPageRowCount = $startPageRowCount = ($facebookPages->currentPage() - 1) * $facebookPages->perPage();
            @endphp

            @forelse($facebookPages as $facebookPage)

                @php
                    $startPageRowCount++;
                @endphp

                <tr>
                    <td>{!! $facebookPage->displayPhoto() !!} {{ $facebookPage->name }}</td>
                    {{-- <td><img src="{{ $facebookPage->image }}"/></td> --}}
                    <td>{{ $facebookPage->page_id }}</td>
                    {{-- <td>
                        @if ( $facebookPage->status == App\EmailSupportAddress::STATUS_INACTIVE )
                            Inactive
                        @else
                            Active
                        @endif
                    </td> --}}
                    <td>{{ $facebookPage->created_at->format('M d, Y') }}</td>
                    {{-- <td class="text-center" data-facebook-page-id="{{ $facebookPage->id }}">
                        <a href="#" class="deleteEmailSupport text-warning ml-2" data-email-support-id="{{ $facebookPage->id }}" data-toggle="modal" data-target="#modalConfirmDeleteEmailSupport" title="Delete">
                            <i class="far fa-trash-alt"></i>
                        </a>
                    </td> --}}
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

                    @if($facebookPages->currentPage() !== $facebookPages->lastPage())
                        {{ $facebookPages->perPage() * $facebookPages->currentPage() }}
                    @else
                     {{ $facebookPages->total() }}
                    @endif
    
                    of {{ $facebookPages->total() }} entries</span>
            </div>

            <div class="col-md-6">
                <div class="float-right pagination justify-content-end mb-0">
                    {!! $facebookPages->onEachSide(2)->links() !!}
                </div>
            </div>

        </div>
        
    </div>
</div>