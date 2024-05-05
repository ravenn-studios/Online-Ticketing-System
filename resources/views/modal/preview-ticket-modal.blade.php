<div class="modal fade" id="modalPreviewTicket" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">

        <div class="loadOverlay">
            <img src="{{ asset('images/rocket-loader.gif') }}">
        </div>

        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Ticket Preview</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

                <div>

                    <div style="min-height: 270px;">
                       
                        <div class="row">
                            <div class="col">

                                <div class="ticket-alerts">
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <span class="alert-text"></span>
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <span class="alert-text"></span>
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                </div>
                                
                            </div>
                        </div>

                        {{-- <div class="row ticket-content-block mb-5" style="display: block;"> --}}
                        <div class="row mb-5" style="display: block;">
                            <div class="col-xl-12 col-2">
                                <div class="loadOverlay">
                                    <img src="{{ asset('images/ajax-bar-loader.gif') }}">
                                </div>
                                <div class="card custom-h mb-0 preview-ticket-content-wrapper"></div>
                            </div>
                        </div>

                    </div>

                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>

        </div>
    </div>
</div>