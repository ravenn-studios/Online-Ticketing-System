<div class="card">
    <div class="loadOverlay">
        <img src="{{ asset('images/ajax-bar-loader.gif') }}">
    </div>
    <!-- Card header -->
    <div class="card-header border-0">
        <div class="float-left">
            <h3 class="mb-0">Emails</h3>
        </div>
        <div class="float-right">
            <a class="btn btn-icon btn-primary btn-sm text-white create-email-support" data-toggle="modal" data-target="#modalCreateEmailSupport" type="button">
                <span class="btn-inner--icon"><i class="far fa-envelope"></i></span>
                <span class="btn-inner--text ml-0">Add Email</span>
            </a>

            {{-- Recent Addition --}}
            {{-- <div id="my-signin2"></div>
            <a href="#" onclick="signOut();">Sign out</a> --}}
        </div>
    </div>
    <!-- Light table -->
    <div class="table-responsive">

        <table class="table align-items-center table-flush email-support-listing">
            <thead class="thead-light">
            <tr>
                <th scope="col text-left" width="250">Name</th>
                <th scope="col text-left" width="250">Email</th>
                <th scope="col text-center" width="120">Status</th>
                <th scope="col text-center" width="250">Date</th>
                <th scope="col text-center" class="text-center" width="250">Action</th>
            </tr>
            </thead>
            <tbody class="list">

            @php
                $footerStartPageRowCount = $startPageRowCount = ($emailSupportAddresses->currentPage() - 1) * $emailSupportAddresses->perPage();
            @endphp

            @forelse($emailSupportAddresses as $emailSupportAddress)

                @php
                    $startPageRowCount++;
                @endphp

                <tr>
                    <td>{{ $emailSupportAddress->name }}</td>
                    <td>{{ $emailSupportAddress->email }}</td>
                    <td>
                        @if ( $emailSupportAddress->status == App\EmailSupportAddress::STATUS_INACTIVE )
                            Inactive
                        @else
                            Active
                        @endif
                    </td>
                    <td>{{ $emailSupportAddress->created_at->format('M d, Y') }}</td>
                    <td class="text-center" data-email-support-id="{{ $emailSupportAddress->id }}">

                        @php

                        $authGmail = new App\AuthGmail;

                        $authGmail->set_connection($emailSupportAddress->id);
                        // $authGmail->set_connection();
                            
                        @endphp

                        {{-- @if ( file_exists('credentials1.json') )
                            @@exists@@
                        @endif --}}

                        @if ( !empty($authGmail->go()) )

                            {!! $authGmail->go() !!}

                        @endif

                        {{-- <a href="#" class="updateEmailSupport" data-email-support-id="{{ $emailSupportAddress->id }}" data-toggle="modal" data-target="#modalUpdateEmailSupport" title="Update">
                            <i class="far fa-edit"></i>
                        </a> --}}

                        <a href="#" class="deleteEmailSupport text-warning ml-2" data-email-support-id="{{ $emailSupportAddress->id }}" data-toggle="modal" data-target="#modalConfirmDeleteEmailSupport" title="Delete">
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

                    @if($emailSupportAddresses->currentPage() !== $emailSupportAddresses->lastPage())
                        {{ $emailSupportAddresses->perPage() * $emailSupportAddresses->currentPage() }}
                    @else
                     {{ $emailSupportAddresses->total() }}
                    @endif
    
                    of {{ $emailSupportAddresses->total() }} entries</span>
            </div>

            <div class="col-md-6">
                <div class="float-right pagination justify-content-end mb-0">
                    {!! $emailSupportAddresses->onEachSide(2)->links() !!}
                </div>
            </div>

        </div>
        
    </div>
</div>