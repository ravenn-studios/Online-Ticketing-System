<div class="modal fade" id="modalViewAgentPerformance" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">

        <div class="loadOverlay" style="height: 510px;">
            <img src="{{ asset('images/rocket-loader.gif') }}">
        </div>

        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Agent Report</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

                {{-- <input type="text" class="form-control-sm mr-3" id="daterange-export-modal" placeholder="Date Range" autocomplete="off"> --}}

                <div class="card">
                    {{-- <div class="card-header border-0">
                        <div class="row align-items-center">
                            <div class="col">
                                <h3 class="mb-0">Report</h3>
                            </div>
                            <div class="col text-right">
                                <a href="#!" class="btn btn-sm btn-primary">See all</a>
                            </div>
                        </div>
                    </div> --}}

                    <div class="card-body" style="min-height: 270px;">

                        <div class="row align-items-center" style="max-width: 480px;">
                            <div class="col">
                                <div class="form-group">
                                    <div class="input-group input-group-alternative">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="ni ni-calendar-grid-58"></i></span>
                                        </div>
                                        <input type="text" class="form-control-sm mr-3 daterange-export-modal" id="daterange-export-modal" placeholder="Date Range" autocomplete="off" style="border:none;">
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <div class="input-group input-group-alternative">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fa fa-search" aria-hidden="true"></i></span>
                                        </div>
                                        <input type="text" class="form-control-sm mr-3 search-export-modal" id="search-export-modal" placeholder="Search" autocomplete="off" style="border:none;">
                                    </div>
                                </div>
                            </div>
                        </div>


                        {{-- <div class="daterange-wrapper">
                            <input type="text" class="form-control-sm mr-3 daterange-export-modal" id="daterange-export-modal" placeholder="Date Range" autocomplete="off">
                        </div> --}}

                        <div class="report-wrapper"></div>

                    </div>

                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>

        </div>
    </div>
</div>