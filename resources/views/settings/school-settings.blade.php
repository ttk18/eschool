@extends('layouts.master')

@section('title')
    {{ __('general_settings') }}
@endsection


@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('general_settings') }}
            </h3>
        </div>
        <div class="row grid-margin">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <form class="create-form-without-reset" action="{{ route('school-settings.store') }}" method="POST" novalidate="novalidate" enctype="multipart/form-data">
                            @csrf
                            <div class="border border-secondary rounded-lg mb-2">
                                <div class="row my-4 mx-1">
                                    <div class="form-group col-md-6 col-sm-12">
                                        <label for="school_name">{{ __('school_name') }} <span class="text-danger">*</span></label>
                                        <input name="school_name" id="school_name" value="{{ $settings['school_name'] ?? '' }}" type="text" required placeholder="{{ __('school_name') }}" class="form-control"/>
                                    </div>
                                    <div class="form-group col-md-6 col-sm-12">
                                        <label for="school_email">{{ __('school_email') }} <span class="text-danger">*</span></label>
                                        <input name="school_email" id="school_email" value="{{ $settings['school_email'] ?? '' }}" type="email" required placeholder="{{ __('school_email') }}" class="form-control"/>
                                    </div>
                                    <div class="form-group col-md-6 col-sm-12">
                                        <label for="school_phone">{{ __('school_phone') }} <span class="text-danger">*</span></label>
                                        <input name="school_phone" id="school_phone" value="{{ $settings['school_phone'] ?? '' }}" type="text" required placeholder="{{ __('school_phone') }}" class="form-control"/>
                                    </div>
                                    <div class="form-group col-md-6 col-sm-12">
                                        <label for="school_tagline">{{ __('school_tagline') }} <span class="text-danger">*</span></label>
                                        <textarea name="school_tagline" id="school_tagline" required placeholder="{{ __('school_tagline') }}" class="form-control">{{ $settings['school_tagline'] ?? '' }}</textarea>
                                    </div>
                                    <div class="form-group col-md-12 col-sm-12">
                                        <label for="school_address">{{ __('school_address') }} <span class="text-danger">*</span></label>
                                        <textarea name="school_address" id="school_address" required placeholder="{{ __('school_address') }}" class="form-control">{{ $settings['school_address'] ?? '' }}</textarea>
                                    </div>
                                    <div class="form-group col-md-6 col-lg-6 col-xl-4 col-sm-12">
                                        <label for="favicon">{{ __('favicon') }} <span class="text-danger">*</span></label>
                                        <input type="file" name="favicon" class="file-upload-default"/>
                                        <div class="input-group col-xs-12">
                                            <input type="text" id="favicon" class="form-control file-upload-info" disabled="" placeholder="{{ __('favicon') }}"/>
                                            <span class="input-group-append">
                                            <button class="file-upload-browse btn btn-theme" type="button">{{ __('upload') }}</button>
                                        </span>
                                            <div class="col-md-12 mt-2">
                                                <img height="50px" src='{{ $settings['favicon'] ??  '' }}' alt="">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6 col-lg-6 col-xl-4 col-sm-12">
                                        <label for="horizontal_logo">{{ __('horizontal_logo') }} <span class="text-danger">*</span></label>
                                        <input type="file" name="horizontal_logo" class="file-upload-default"/>
                                        <div class="input-group col-xs-12">
                                            <input type="text" id="horizontal_logo" class="form-control file-upload-info" disabled="" placeholder="{{ __('horizontal_logo') }}"/>
                                            <span class="input-group-append">
                                            <button class="file-upload-browse btn btn-theme" type="button">{{ __('upload') }}</button>
                                        </span>
                                            <div class="col-md-12 mt-2">
                                                <img height="50px" src='{{ $settings['horizontal_logo'] ?? '' }}' alt="">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6 col-lg-6 col-xl-4 col-sm-12">
                                        <label for="vertical_logo">{{ __('vertical_logo') }} <span class="text-danger">*</span></label>
                                        <input type="file" name="vertical_logo" class="file-upload-default"/>
                                        <div class="input-group col-xs-12">
                                            <input type="text" class="form-control file-upload-info" id="vertical_logo" disabled="" placeholder="{{ __('vertical_logo') }}"/>
                                            <span class="input-group-append">
                                        <button class="file-upload-browse btn btn-theme" type="button">{{ __('upload') }}</button>
                                        </span>
                                            <div class="col-md-12 mt-2">
                                                <img height="50px" src='{{ $settings['vertical_logo'] ?? '' }}' alt="">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="border border-secondary rounded-lg my-4 mx-1">
                                <div class="col-md-12 mt-3"><h4>{{__("Roll Number Settings")}}</h4></div>
                                <div class="col-12 mb-3">
                                    <hr class="mt-0">
                                </div>
                                <div class="form-group col-md-12 col-sm-12">
                                    <label for="roll-number-order">{{__("Roll Number Sorting")}}</label>
                                    <input type="hidden" id="roll-number-sort-column" name="roll_number_sort_column" value="{{ $settings['roll_number_sort_column'] ?? "" }}">
                                    <input type="hidden" id="roll-number-sort-order" name="roll_number_sort_order" value="{{ $settings['roll_number_sort_order'] ?? "" }}">
                                    <select name="" id="roll-number-order" class="form-control" required>
                                        <option value="" hidden="">-- {{__('Select')}} --</option>
                                        <option value="first_name,asc">{{__("First Name - Ascending")}}</option>
                                        <option value="first_name,desc">{{__("First Name - Descending")}}</option>
                                        <option value="last_name,asc">{{__("Last Name - Ascending")}}</option>
                                        <option value="last_name,desc">{{__("Last Name - Descending")}}</option>
                                    </select>

                                    <div class="form-check">
                                        <label class="form-check-label"> <input type="checkbox" class="form-check-input" name="change_roll_number" id="change-roll-ckh-settings" value="1"> {{ __('Change Roll Number for All Classes') }} <i class="input-helper"></i></label>
                                    </div>
                                </div>

                            </div>


                            <input class="btn btn-theme" type="submit" value="Submit">
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
