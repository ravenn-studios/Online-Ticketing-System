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
            <a class="btn btn-icon btn-primary btn-sm create-email-support" data-toggle="modal" data-target="#modalCreateEmailSupport" type="button">
                <span class="btn-inner--icon"><i class="ni ni-ruler-pencil"></i></span>
                <span class="btn-inner--text ml-0">Add Email</span>
            </a>
        </div>
    </div>
    <!-- Light table -->
    <div class="table-responsive">

        <table class="table align-items-center table-flush email-templates-listing">
            <thead class="thead-light">
            <tr>
                <th scope="col text-left" width="1100">Name</th>
                <th scope="col text-center" width="250">Date</th>
                <th scope="col text-center" class="text-center" width="250">Action</th>
            </tr>
            </thead>
            <tbody class="list">

            @php
                $footerStartPageRowCount = $startPageRowCount = ($emailTemplates->currentPage() - 1) * $emailTemplates->perPage();
            @endphp

            @forelse($emailTemplates as $emailTemplate)

                @php
                    $startPageRowCount++;
                @endphp

                <tr>
                    <td>{{ $emailTemplate->name }}</td>
                    <td>{{ $emailTemplate->created_at->format('M d, Y') }}</td>
                    <td class="text-center">
                        <a href="#" class="updateEmailTemplate" data-template-id="{{ $emailTemplate->id }}" data-toggle="modal" data-target="#modalUpdateEmailTemplate" title="Update">
                            <i class="far fa-edit"></i>
                        </a>

                        <a href="#" class="deleteEmailTemplate text-warning ml-2" data-template-id="{{ $emailTemplate->id }}" title="Delete">
                            <i class="far fa-trash-alt"></i>
                        </a>
                    </td>
                </tr>
            {{-- <tr class="message-block" data-ticket-id="{{ $emailTemplate->id }}" data-thread-id="{{ $emailTemplate->thread_id }}">
                <td class="text-right">
                    <input class="form-check-input mt--5 ticket-checkbox" type="checkbox">
                </td>
                <td style="font-weight: 600;">

                            <span class="name mb-0 text-sm">{{ $emailTemplate->subject }}</span>

                            <span class="badge badge-pill {{ \App\TicketPriority::PRIORITY_LIST[$emailTemplate->priority_id]['badge_class'] }}">{{ $emailTemplate->priority->name }}</span>


                </td>
                <td class="budget" width="100">
                    {{ substr($emailTemplate->snippet, 0, 90) }}
                </td>
                <td class="requester text-center">
                    {{ explode("<", $emailTemplate->requester)[0] }}
                </td>
                <td class="requester text-center">
                    {{ $emailTemplate->origin->name }}
                </td>
                <td class="requester text-center">
                    {{ $emailTemplate->status->name }}
                </td>
                <td class="requester text-center">
                    {{ $emailTemplate->type->name }}
                </td>

                <td class="text-center">
                    {{ date('M d',strtotime($emailTemplate->thread_started_at)) }}
                </td>
                <td class="text-right">
                    <div class="dropdown">
                        <a class="btn btn-sm btn-icon-only text-light" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-ellipsis-v"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right dropdown-menu-arrow">
                        <a class="dropdown-item updateTicket" href="#" data-toggle="modal" data-target="#modalUpdateTicket" data-ticket-id="{{ $emailTemplate->id }}">Update</a>

                        </div>
                    </div>
                </td>
            </tr> --}}

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

                    @if($emailTemplates->currentPage() !== $emailTemplates->lastPage())
                        {{ $emailTemplates->perPage() * $emailTemplates->currentPage() }}
                    @else
                     {{ $emailTemplates->total() }}
                    @endif
    
                    of {{ $emailTemplates->total() }} entries</span>
            </div>

            <div class="col-md-6">
                <div class="float-right pagination justify-content-end mb-0">
                    {!! $emailTemplates->onEachSide(2)->links() !!}
                </div>
            </div>

        </div>
        
    </div>
</div>