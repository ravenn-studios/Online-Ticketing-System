{{-- <div class="alert alert-primary" role="alert">
    <strong>Primary!</strong> This is a primary alert—check it out!
</div> --}}

<div class="row mb-2 update">

    {{-- @if($reminder->type == \App\Reminder::TYPE_SYSTEM_GENERATED) readonly @endif --}}

    <div class="col">
        <label class="form-control-label">Ticket Id</label>
        {{-- tmp value for ticket id --}}
        <input type="text" class="form-control updateReminderTicketId" value="{{ $reminder->ticket_id }}" placeholder="Ticket Id" @if($reminder->type == \App\Reminder::TYPE_SYSTEM_GENERATED) readonly @endif required/>
    </div>
                                    
    <div class="col">
        <label class="form-control-label">*Name</label>
        <input type="text" class="form-control updateReminderName" placeholder="Reminder Name" value="{{ $reminder->title }}" @if($reminder->type == \App\Reminder::TYPE_SYSTEM_GENERATED) readonly @endif required/>
        <input type="hidden" class="reminderId" value="{{ $reminder->id }}"/>
    </div>

    <div class="col">
        <label class="form-control-label">Description</label>
        <input type="text" class="form-control updateReminderDescription" placeholder="Description" value="{{ $reminder->description }}" @if($reminder->type == \App\Reminder::TYPE_SYSTEM_GENERATED) readonly @endif/>
    </div>

</div>

<div class="row mb-2 update">

    <div class="col">
        <label class="form-control-label">*Type</label>
        <select class="form-control selectReminderType" required>
            <option value="scheduled" {{ (empty($interval)) ? 'selected' : '' }}>Scheduled</option>
            <option value="interval" {{ (!empty($interval)) ? 'selected' : '' }}>Interval</option>
        </select>
    </div>

    <div class="col notify-at-block {{ (empty($interval)) ? '' : 'd-none' }}">
        <label class="form-control-label">*Notify At</label>
        <input type="text" class="form-control updateNotifyAt" id="updateNotifyAt" placeholder="Notify At" value="{{ ($reminder->reminder_interval_id) ? \Carbon\Carbon::now()->format('Y-m-d H:i:s') : $reminder->notify_at }}" readonly required>
    </div>

    <div class="col interval-block {{ (!empty($interval)) ? '' : 'd-none' }}">
        <label class="form-control-label">*Interval</label>
        <select class="form-control selectInterval" required>
            <option value="daily" {{ (!empty($interval) && $interval->day) ? 'selected' : '' }}>Day</option>
            <option value="hourly" {{ (!empty($interval) && $interval->hour) ? 'selected' : '' }}>Hour</option>
            <option value="minute" {{ (!empty($interval) && $interval->minute) ? 'selected' : '' }}>Minute</option>
        </select>
    </div>

    <div class="col time-block time-daily {{ (!empty($interval) && $interval->day) ? '' : 'd-none' }}">
        <label class="form-control-label">*Time</label>
        <select class="form-control updateSelectDay" required>
            <option value="1" {{ (!empty($interval) && $interval->day == 1) ? 'selected' : '' }}>Daily</option>
            <option value="2" {{ (!empty($interval) && $interval->day == 2) ? 'selected' : '' }}>2 Days</option>
            <option value="3" {{ (!empty($interval) && $interval->day == 3) ? 'selected' : '' }}>3 Days</option>
            <option value="4" {{ (!empty($interval) && $interval->day == 4) ? 'selected' : '' }}>4 Days</option>
            <option value="5" {{ (!empty($interval) && $interval->day == 5) ? 'selected' : '' }}>5 Days</option>
            <option value="7" {{ (!empty($interval) && $interval->day == 7) ? 'selected' : '' }}>Weekly</option>
            <option value="14" {{ (!empty($interval) && $interval->day == 14) ? 'selected' : '' }}>Biweekly</option>
        </select>
    </div>

    <div class="col time-block time-hourly {{ (!empty($interval) && $interval->hour) ? '' : 'd-none' }}">
        <label class="form-control-label">*Hourly</label>
        <select class="form-control updateSelectHour" required>
            <option value="1" {{ (!empty($interval) && $interval->hour == 1) ? 'selected' : '' }}>1 Hour</option>
            <option value="2" {{ (!empty($interval) && $interval->hour == 2) ? 'selected' : '' }}>2 Hours</option>
            <option value="3" {{ (!empty($interval) && $interval->hour == 3) ? 'selected' : '' }}>3 Hours</option>
            <option value="4" {{ (!empty($interval) && $interval->hour == 4) ? 'selected' : '' }}>4 Hours</option>
            <option value="5" {{ (!empty($interval) && $interval->hour == 5) ? 'selected' : '' }}>5 Hours</option>
            <option value="6" {{ (!empty($interval) && $interval->hour == 6) ? 'selected' : '' }}>6 Hours</option>
        </select>
    </div>

    <div class="col time-block time-minute {{ (!empty($interval) && $interval->minute) ? '' : 'd-none' }}">
        <label class="form-control-label">*Minute</label>
        <select class="form-control updateSelectMinute">
            <option value="1" {{ (!empty($interval) && $interval->minute == 1) ? 'selected' : '' }}>1 Minute</option>
            <option value="2" {{ (!empty($interval) && $interval->minute == 2) ? 'selected' : '' }}>2 Minutes</option>
            <option value="3" {{ (!empty($interval) && $interval->minute == 3) ? 'selected' : '' }}>3 Minutes</option>
            <option value="4" {{ (!empty($interval) && $interval->minute == 4) ? 'selected' : '' }}>4 Minutes</option>
            <option value="5" {{ (!empty($interval) && $interval->minute == 5) ? 'selected' : '' }}>5 Minutes</option>
            <option value="10" {{ (!empty($interval) && $interval->minute == 10) ? 'selected' : '' }}>10 Minutes</option>
            <option value="15" {{ (!empty($interval) && $interval->minute == 15) ? 'selected' : '' }}>15 Minutes</option>
            <option value="30" {{ (!empty($interval) && $interval->minute == 30) ? 'selected' : '' }}>30 Minutes</option>
        </select>
    </div>

</div>

@if( $reminder->type == \App\Reminder::TYPE_SYSTEM_GENERATED )

<div class="row">
    
    <div class="col-lg-4">
        <label class="form-control-label">*Status</label>
        <select class="form-control updateReminderStatus">
            <option value="2" {{ ($reminder->status_id == \App\Reminder::STATUS_INACTIVE) ? 'selected' : '' }}>Inactive</option>
            <option value="0" {{ ($reminder->status_id == \App\Reminder::STATUS_PENDING) ? 'selected' : '' }}>Active</option>
        </select>
    </div>

</div>

@endif