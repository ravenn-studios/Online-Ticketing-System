<div class="modal fade" id="modalChangePassword" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered" role="document">
				<div class="modal-content">

						{{-- <form action="{{ route('change.password') }}" id="formUpdateUserInfo" enctype="multipart/form-data" method="POST"> --}}
						<form action="{{ route('change.password') }}" id="formChangePassword" enctype="multipart/form-data" method="POST">
								{{ csrf_field() }}
								<div class="modal-header">
										<h5 class="modal-title" id="exampleModalLabel">Update Password</h5>
										<button type="button" class="close" data-dismiss="modal" aria-label="Close">
												<span aria-hidden="true">&times;</span>
										</button>
								</div>

								<div class="modal-body">
										<div class="update-user-info-alerts">
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

										<div class="card">

												<div class="card-body">

														<div class="pl-lg-4 pt-4">

																<div class="row">
																		<div class="col-md-12">
																				<div class="form-group">
																						<label class="form-control-label" for="input-address">Current Password</label>
																						<div class="input-group input-group-merge input-group-alternative">
																								<div class="input-group-prepend">
																										<span class="input-group-text"><i class="fas fa-lock"></i></span>
																								</div>
																								<input type="password" class="form-control current_password" placeholder="Enter Current Password" name="current_password" value=""/>
																						</div>
																				</div>
																		</div>
																</div>

																<div class="row">
																		<div class="col-md-12">
																				<div class="form-group">
																						<label class="form-control-label" for="input-address">New Password</label>
																						<div class="input-group input-group-merge input-group-alternative">
																								<div class="input-group-prepend">
																										<span class="input-group-text"><i class="fas fa-lock"></i></span>
																								</div>
																								<input type="password" class="form-control new_password" placeholder="Enter New Password" name="new_password" value=""/>
																						</div>
																				</div>
																		</div>
																</div>

																<div class="row">
																		<div class="col-md-12">
																				<div class="form-group">
																						<label class="form-control-label" for="input-address">Confirm Password</label>
																						<div class="input-group input-group-merge input-group-alternative">
																								<div class="input-group-prepend">
																										<span class="input-group-text"><i class="fas fa-lock"></i></span>
																								</div>
																								<input type="password" class="form-control new_confirm_password" placeholder="Confirm Password" name="new_confirm_password" value=""/>
																						</div>
																				</div>
																		</div>
																</div>

														</div>

														
												</div>
												
										</div>
								</div>
								
								<div class="modal-footer">
										<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
										<button type="submit" class="btn btn-primary {{-- btn-create-email-support --}}">Update</button>
								</div>
						</form>
				</div>
		</div>
</div>