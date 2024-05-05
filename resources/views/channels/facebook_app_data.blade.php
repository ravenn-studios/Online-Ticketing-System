<div class="card">
    <div class="loadOverlay">
        <img src="{{ asset('images/ajax-bar-loader.gif') }}">
    </div>
    <!-- Card header -->
    <div class="card-header border-0">
        <div class="float-left">
            <h3 class="mb-0">App</h3>
        </div>
        {{-- <div class="float-right">
            <a class="btn btn-icon btn-primary btn-sm text-white create-email-support" data-toggle="modal" data-target="#modalCreateEmailSupport" type="button">
                <span class="btn-inner--icon"><i class="far fa-envelope"></i></span>
                <span class="btn-inner--text ml-0">Add Email</span>
            </a>
        </div> --}}
    </div>

    <div class="card-body">

        <div class="form-group">
            <label class="form-control-label" for="input-address">App Name</label>
            <div class="input-group input-group-merge input-group-alternative">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="far fa-address-card"></i></span>
                </div>
                <input type="text" class="form-control name" placeholder="Name" name="name" value="{{ $facebook->name }}">
            </div>
        </div>

        <div class="form-group">
            <label class="form-control-label" for="input-address">App ID</label>
            <div class="input-group input-group-merge input-group-alternative">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-id-card-alt"></i></span>
                </div>
                <input type="text" class="form-control name" placeholder="App ID" name="name" value="{{ $facebook->app_id }}">
            </div>
        </div>

        <div class="form-group">
            <label class="form-control-label" for="input-address">App Secret</label>
            <div class="input-group input-group-merge input-group-alternative">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-key"></i></i></span>
                </div>
                <input type="password" class="form-control name" placeholder="App Secret" name="name" value="{{ $facebook->app_secret }}">
            </div>
        </div>

        <div class="form-group float-right">
            {{-- <button type="submit" class="btn btn-primary btn-sm btn-create-email-support">Update</button> --}}
        </div>

    </div>

</div>