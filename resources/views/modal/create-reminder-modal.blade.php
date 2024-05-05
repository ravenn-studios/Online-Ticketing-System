<div class="modal fade" id="modalCreateReminder" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">

            {{-- <form role="form" id="formCreateReminder"> --}}

                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Create Reminder</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">

                    <div class="email-signature-alerts">
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <span class="alert-text"></span>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                        </div>
                        
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <span class="alert-text"></span>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                        </div>
                    </div>

                    <div class="card">

                        <div class="card-body px-lg-5 py-lg-5">

                            <div class="pl-lg-4 p-4">

                                <div class="row mb-2">

                                    <div class="col">
                                        <label class="form-control-label">Ticket Id</label>
                                        {{-- tmp value for ticket id --}}
                                        {{-- <input type="text" class="form-control reminderTicketId" value="4242" placeholder="Ticket ID" required/> --}}
                                        <input type="text" class="form-control reminderTicketId" placeholder="Ticket Id" required/>
                                    </div>
                                    
                                    <div class="col">
                                        <label class="form-control-label">*Name</label>
                                        <input type="text" class="form-control reminderName" placeholder="Reminder Name" required/>
                                    </div>

                                    <div class="col">
                                        <label class="form-control-label">Description</label>
                                        <input type="text" class="form-control reminderDescription" placeholder="Description"/>
                                    </div>

                                </div>

                                <div class="row">

                                    <div class="col">
                                        <label class="form-control-label">*Type</label>
                                        <select class="form-control selectReminderType" required>
                                            <option value="scheduled" selected>Scheduled</option>
                                            <option value="interval">Interval</option>
                                        </select>
                                    </div>

                                    <div class="col notify-at-block">
                                        <label class="form-control-label">*Notify At</label>
                                        <input type="text" class="form-control" id="notifyAt" placeholder="Notify At" readonly required>
                                    </div>

                                    <div class="col interval-block d-none">
                                        <label class="form-control-label">*Interval</label>
                                        <select class="form-control selectInterval" required>
                                            <option value="daily" selected>Day</option>
                                            <option value="hourly">Hour</option>
                                            <option value="minute">Minute</option>
                                        </select>
                                    </div>

                                    <div class="col time-block time-daily d-none">
                                        <label class="form-control-label">*Time</label>
                                        <select class="form-control selectTime" required>
                                            <option value="1">Daily</option>
                                            <option value="2">2 Days</option>
                                            <option value="3">3 Days</option>
                                            <option value="4">4 Days</option>
                                            <option value="5">5 Days</option>
                                            <option value="7">Weekly</option>
                                            <option value="14">Biweekly</option>
                                        </select>
                                    </div>

                                    <div class="col time-block time-hourly d-none">
                                        <label class="form-control-label">*Hourly</label>
                                        <select class="form-control selectTime" required>
                                            <option value="1">1 Hour</option>
                                            <option value="2">2 Hours</option>
                                            <option value="3">3 Hours</option>
                                            <option value="4">4 Hours</option>
                                            <option value="5">5 Hours</option>
                                            <option value="6">6 Hours</option>
                                        </select>
                                    </div>

                                    <div class="col time-block time-minute d-none">
                                        <label class="form-control-label">*Minute</label>
                                        <select class="form-control selectTime">
                                            <option value="1">1 Minute</option>
                                            <option value="2">2 Minutes</option>
                                            <option value="3">3 Minutes</option>
                                            <option value="4">4 Minutes</option>
                                            <option value="5">5 Minutes</option>
                                            <option value="10">10 Minutes</option>
                                            <option value="15">15 Minutes</option>
                                            <option value="30">30 Minutes</option>
                                        </select>
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary createReminder">Create</button>
                </div>

            {{-- </form> --}}
        </div>
    </div>
</div>