<div class="modal fade" id="modalNotificationDetailsPopup" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Ticket Request: &nbsp;<span class="ticket-subject"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            
                <div class="card-body">

                    <h4 class="confirm-header display-none">Confirm assigning of ticket to <mark class="assignTicketToName"></mark>?</h4>
                    <h4 class="approved-header display-none">Ticket Request has been <mark>approved</mark> by <span class="action-by"></span></h4>
                    <h4 class="declined-header display-none">Ticket Request has been <mark>declined</mark> by <span class="action-by"></span></h4>

                    <div class="display-none action-btn-wrapper ml-2">
                        <button type="button" class="btn btn-primary btn-sm approve-ticket-request" data-action="approve" data-ticket-request-id="">Approve</button>
                        <button type="button" class="btn btn-warning btn-sm decline-ticket-request" data-action="decline" data-ticket-request-id="">Decline</button>
                    </div>

                    <div class="ticket-messages-popup message-history mt-2"></div>

                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                {{-- <button type="submit" class="btn btn-warning btn-sm decline-ticket-request">Decline</button>
                <button type="submit" class="btn btn-primary btn-sm approve-ticket-request">Approve</button> --}}
            </div>

        </div>
    </div>
</div>