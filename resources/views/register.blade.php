<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">{{ __('registration') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fa fa-close"></i></span>
                </button>
            </div>
            <form class="" action="{{ url('schools/registration') }}" method="post" data-success-function="formSuccessFunction">
                @csrf
                <div class="modal-body">
                    <div class="bg-light p-4 mt-4">
                        <h4 class="card-title mb-4">
                            {{ __('create') . ' ' . __('schools') }}
                        </h4>
                        <div class="row">
                            <div class="form-group col-sm-12 col-md-4">
                                <label for="school_name">{{ __('name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="school_name" id="school_name"
                                    placeholder="{{ __('schools') }}" class="form-control" required>
                            </div>
                            <div class="form-group col-sm-12 col-md-4">
                                <label for="school_support_email">{{ __('support') . ' ' . __('email') }} <span
                                        class="text-danger">*</span></label>
                                <input type="email" name="school_support_email" id="school_support_email"
                                    placeholder="{{ __('support') . ' ' . __('email') }}" class="form-control" required>
                            </div>
                            <div class="form-group col-sm-12 col-md-4">
                                <label for="school_support_phone">{{ __('support') . ' ' . __('phone') }} <span
                                        class="text-danger">*</span></label>
                                <input type="number" name="school_support_phone" id="school_support_phone"
                                    placeholder="{{ __('support') . ' ' . __('phone') }}" min="0"
                                    class="form-control remove-number-increment" required>
                            </div>
                            <div class="form-group col-sm-12 col-md-6">
                                <label for="school_tagline">{{ __('tagline') }} <span
                                        class="text-danger">*</span></label>
                                <textarea name="school_tagline" id="school_tagline" cols="30" rows="3" class="form-control"
                                    placeholder="{{ __('tagline') }}" required></textarea>
                            </div>
                            <div class="form-group col-sm-12 col-md-6">
                                <label for="school_address">{{ __('address') }} <span
                                        class="text-danger">*</span></label>
                                <textarea name="school_address" id="school_address" cols="30" rows="3" class="form-control"
                                    placeholder="{{ __('address') }}" required></textarea>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <div class="bg-light p-4 mt-4">
                        <h4 class="card-title mb-4">
                            {{ __('add') . ' ' . __('admin') }}
                        </h4>
                        <div class="row">
                            <div class="form-group col-sm-12 col-md-3">
                                <label for="admin_first_name">{{ __('first_name') }} <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="admin_first_name" id="admin_first_name"
                                    placeholder="{{ __('first_name') }}" class="form-control" required>
                            </div>
                            <div class="form-group col-sm-12 col-md-3">
                                <label for="admin_last_name">{{ __('last_name') }} <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="admin_last_name" id="admin_last_name"
                                    placeholder="{{ __('last_name') }}" class="form-control" required>
                            </div>
                            <div class="form-group col-sm-12 col-md-3">
                                <label for="admin_email">{{ __('email') }} <span
                                        class="text-danger">*</span></label>
                                <input type="email" name="admin_email" id="admin_email"
                                    placeholder="{{ __('email') }}" class="form-control" required>
                            </div>
                            <div class="form-group col-sm-12 col-md-3">
                                <label for="admin_contact">{{ __('contact') }} <span
                                        class="text-danger">*</span></label>
                                <input type="number" name="admin_contact" id="admin_contact" min="0"
                                    placeholder="{{ __('contact') }}" class="form-control remove-number-increment" required>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="col-sm-12 col-md-12 text-right">
                        <input type="submit" class="btn btn-home-theme" value="{{ __('submit') }}">
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
