<div class="card">
    <div class="loadOverlay">
        <img src="{{ asset('images/ajax-bar-loader.gif') }}">
    </div>
    <!-- Card header -->
    <div class="card-header border-0 rounded">
        <div class="float-left">
            <h3 class="mb-0">Reminders</h3>
        </div>
        <div class="float-right">
            <a class="btn btn-icon btn-primary btn-sm create-reminder-modal text-white" data-toggle="modal" data-target="#modalCreateReminder" type="button">
                <span class="btn-inner--icon"><i class="ni ni-time-alarm"></i></span>
                <span class="btn-inner--text ml-0">Create Reminder</span>
            </a>
        </div>
    </div>
    <!-- Light table -->
    <div class="table-responsive">

        <table class="table align-items-center table-flush email-templates-listing">
            <thead class="thead-light">
            <tr>
                <th class="text-center" scope="col">Ticket Id</th>
                <th class="text-center" scope="col" width="700">Name</th>
                <th class="text-center" scope="col">Description</th>
                <th class="text-center" scope="col">Reminder Type</th>
                <th class="text-center" scope="col">Generated</th>
                <th class="text-center" scope="col">Active</th>
                <th class="text-center" scope="col" style="text-align: center;">Notify</th>
                <th class="text-center" scope="col" class="text-center" width="250">Action</th>
            </tr>
            </thead>
            <tbody class="list">

            @php
                $footerStartPageRowCount = $startPageRowCount = ($reminders->currentPage() - 1) * $reminders->perPage();
            @endphp

            @forelse($reminders as $reminder)

                @php
                    $startPageRowCount++;

                    //user reminders = default active, system/app reminders = depending on conditions
                    $reminderStatus = 'Active';
                    if ( $reminder->type == \App\Reminder::TYPE_SYSTEM_GENERATED )
                    {
                        if ( $reminder->status_id == \App\Reminder::STATUS_DONE || $reminder->status_id == \App\Reminder::STATUS_INACTIVE )
                        {
                            $reminderStatus = "Inactive";
                        }
                    }

                @endphp

                <tr>
                    <td class="text-center">{{ (!$reminder->ticket_id) ? '--' : $reminder->ticket_id }}</td>
                    <td class="text-center">{{ $reminder->title }}</td>
                    <td class="text-center">{{ $reminder->description }}</td>
                    <td class="text-center">{{ (!$reminder->reminder_interval_id) ? 'Scheduled' : 'Interval' }}</td>
                    <td class="text-center">{{ ($reminder->type == \App\Reminder::TYPE_USER_GENERATED) ? 'User' : 'App' }}</td>
                    <td class="text-center">{{ $reminderStatus }}</td>
                    <td class="text-center">{{ (!$reminder->reminder_interval_id) ? \Carbon\Carbon::parse($reminder->notify_at)->format('M d, Y H:i:s') : '--' }}</td>
                    <td class="text-center">
                        <a href="#" class="updateReminderModal" data-reminder-id="{{ $reminder->id }}" data-toggle="modal" data-target="#modalUpdateReminder" title="Update" @if($reminder->type == \App\Reminder::TYPE_SYSTEM_GENERATED) data-system-generated="true" @endif >
                            <i class="far fa-edit"></i>
                        </a>

                        <a href="#" class="deleteReminder text-warning ml-2 {{ ($reminder->type == \App\Reminder::TYPE_SYSTEM_GENERATED) ? 'hidden' : '' }}" data-reminder-id="{{ $reminder->id }}" title="Delete Reminder">
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

                    @if($reminders->currentPage() !== $reminders->lastPage())
                        {{ $reminders->perPage() * $reminders->currentPage() }}
                    @else
                     {{ $reminders->total() }}
                    @endif
    
                    of {{ $reminders->total() }} entries</span>
            </div>

            <div class="col-md-6">
                <div class="float-right pagination justify-content-end mb-0">
                    {!! $reminders->onEachSide(2)->links() !!}
                </div>
            </div>

        </div>
        
    </div>
</div>