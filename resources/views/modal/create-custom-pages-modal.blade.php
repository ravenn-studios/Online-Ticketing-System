<div class="modal fade" id="modalCreateCustomPage" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">

            <form role="form" id="formCreateCustomPage">

                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Create Custom Page</h5>
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

                            <div class="pl-lg-4 pt-4">

                                <div class="form-group">
                                    <label class="form-control-label">Name</label>
                                    <input type="text" class="form-control signatureName" id="signatureName" placeholder="Email Signature Name"/>
                                </div>

                                <div class="form-group">
                                    <label class="form-control-label">Status</label>
                                    <select class="form-control selectSignatureStatus" id="selectSignatureStatus">
                                        <option value="0">Inactive</option>
                                        <option value="1">Active</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-control-label">Email Signature</label>
                                    <textarea class="form-control" id="createSignatureContentEditor" rows="6"></textarea>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>

            </form>
        </div>
    </div>
</div>