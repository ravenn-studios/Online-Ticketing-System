<div class="modal fade" id="modalUpdateUserInfo" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">

            <form role="form" id="formUpdateUserInfo">

                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Update Custom Page</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">

                    <div class="custom-page-alerts">
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

                            <h6 class="heading-small text-muted">Page Details</h6>

                            <div class="pl-lg-4 pt-4">

                                <div class="row">

                                    <div class="col-lg-6">

                                        <div class="form-group">
                                            <label class="form-control-label">Name</label>
                                            <input type="text" class="form-control form-control-sm pageName" placeholder="Page Name"/>
                                        </div>

                                    </div>

                                    <div class="col-lg-6">

                                        <div class="form-group">
                                            <label class="form-control-label">Slug</label>
                                            <input type="text" class="form-control form-control-sm pageSlug" placeholder="URL Slug"/>
                                        </div>

                                    </div>

                                </div>

                            </div>


                            <h6 class="heading-small text-muted mt-4">Page Conditions</h6>

                            <div class="pl-lg-4 pt-4">

                                <div class="row">

                                    <div class="col-lg-6 all-page-conditions-block">

                                        <h6 class="heading-small">Tickets must meet all of these conditions</h6>

                                        {{-- <div class="row all condition-block1" data-row="1" data-operator="AND">

                                            <div class="col-lg-6">

                                                <div class="form-group">
                                                    <label class="form-control-label">Column</label>
                                                    <select class="form-control form-control-sm selectColumn selectColumn1" data-target-class="all-page-conditions-block" data-row="1">
                                                        <option value="" hidden>Select Option</option>
                                                    </select>
                                                </div>
        
                                            </div>
        
                                            <div class="col-lg-6">
        
                                                <div class="form-group">
                                                    <label class="form-control-label">Value</label>
                                                    <select class="form-control form-control-sm selectValue selectValue1" data-target-class="all-page-conditions-block" data-row="1">
                                                        <option value="" hidden>-</option>
                                                    </select>
                                                </div>
        
                                            </div>

                                        </div> --}}
                                        <div class="row"></div>

                                        <button type="button" class="btn btn-outline-primary btn-sm float-right add-row-condition all" data-target-class="all-page-conditions-block" data-is-create="false" data-action="all">
                                            <i class="fas fa-plus"></i> ADD CONDITION</button>

                                    </div>

                                    <div class="col-lg-6 any-page-conditions-block">

                                        <h6 class="heading-small">Tickets must meet any of these conditions</h6>

                                        {{-- <div class="row any condition-block1" data-row="1" data-operator="OR">

                                            <div class="col-lg-6">

                                                <div class="form-group">
                                                    <label class="form-control-label">Column</label>
                                                    <select class="form-control form-control-sm selectColumn selectColumn1" data-target-class="any-page-conditions-block" data-row="1">
                                                        <option value="" hidden>Select Option</option>
                                                    </select>
                                                </div>
        
                                            </div>
        
                                            <div class="col-lg-6">
        
                                                <div class="form-group">
                                                    <label class="form-control-label">Value</label>
                                                    <select class="form-control form-control-sm selectValue selectValue1" data-target-class="any-page-conditions-block" data-row="1">
                                                        <option value="" hidden>-</option>
                                                    </select>
                                                </div>
        
                                            </div>

                                        </div> --}}
                                        <div class="row"></div>

                                        <button type="button" class="btn btn-outline-primary btn-sm float-right add-row-condition any" data-target-class="any-page-conditions-block" data-is-create="false" data-action="any">
                                            <i class="fas fa-plus"></i> ADD CONDITION</button>

                                    </div>

                                </div>

                            </div>

                            {{-- <div class="pl-lg-4">

                                <div class="row">

                                    <div class="col-lg-12">

                                        <button type="button" class="btn btn-outline-primary btn-sm float-right add-row-condition" data-operator="AND">
                                            <i class="fas fa-plus"></i> ADD CONDITION</button>

                                    </div>

                                </div>

                            </div> --}}

                        </div>

                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary btn-update-page-conditions">Update</button>
                </div>

            </form>
        </div>
    </div>
</div>