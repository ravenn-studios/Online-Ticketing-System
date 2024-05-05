<div class="modal fade" id="modalAssignTicket" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">

            <form role="form" id="formAssignTicket">

                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Assign Ticket</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">

                    <div class="ticket-assign-alerts">
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
                
                    <div class="card-body px-lg-5 py-lg-5 filterInputsBlock">

                        <div class="form-group mb-3">
                            <label class="form-control-label">Assign To</label>
                            <div class="input-group input-group-merge input-group-alternative">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-bars"></i></span>
                                </div>
                                <select class="form-control selectAssignTicketToUser">
                                    <option value="" hidden>User</option>

                                    @foreach($agents as $agent)

                                        <option value="{{ $agent->id }}">{{ $agent->name }}</option>

                                    @endforeach

                                </select>
                            </div>
                        </div>

                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Assign</button>
                </div>

            </form>
        </div>
    </div>
</div>