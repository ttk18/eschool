@extends('layouts.master')

@section('title')
    {{ __('Edit Fees')}}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('Edit Fees')}}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card search-container">
                <div class="card">
                    <div class="card-body">
                        @if($fees->fees_paid_count > 0)
                            <div class="col-12 alert alert-danger">{{__("Certain Fees modification are prohibited because some Parents have already Paid the Fees. ")}}</div>
                        @endif
                        <form class="common-validation" action="{{ route('fees.update',$fees->id) }}" method="POST" novalidate="novalidate">
                            @csrf
                            <input type="hidden" name="_method" value="PUT"/>
                            <div class="border border-secondary rounded-lg mb-2 p-2 mb-3">
                                <div class="col-12 mt-1">
                                    <h4 class="card-title">
                                        {{ __('Fees')}} : {{$fees->class->full_name}}
                                    </h4>
                                    <hr>
                                </div>
                                <div class="row col-12">

                                    <div class="form-group col-sm-12 col-md-6 col-lg-4">
                                        <label>{{ __('Name') }} <span class="text-danger">*</span></label>
                                        {!! Form::text('name', $fees->name, ['placeholder' => __('Name'), 'class' => 'form-control','required']) !!}
                                    </div>
                                    <div class="form-group col-sm-12 col-md-6 col-lg-4">
                                        <label>{{ __('due_date')}} <span class="text-danger">*</span></label>
                                        {{ Form::text('due_date', $fees->due_date, ['class' => 'datepicker-popup form-control', 'placeholder' => __('due_date'), 'required','autocomplete'=>'off']) }}
                                    </div>
                                    <div class="form-group col-sm-12 col-md-6 col-lg-4">
                                        <label>{{ __('due_charges')}} <span class="text-danger">*</span> <span class="text-info small">( {{__('in_percentage')}} )</span></label>
                                        {{ Form::number('due_charges', $fees->due_charges, ['class' => 'form-control', 'placeholder' => __('due_charges'), 'required', 'min' => 0]) }}
                                    </div>
                                </div>
                            </div>
                            <div class="border border-secondary rounded-lg mb-2 p-2 mb-3">
                                <div class="col-12 mt-1">
                                    <h4 class="card-title">
                                        {{ __('Fees Type') }}
                                    </h4>
                                    <hr>
                                </div>
                                <div class="fees-class-types">
                                    <div data-repeater-list="fees_type" class="row col-12">
                                        <div class="row col-12 mb-3" data-repeater-item>
                                            <input type="hidden" name="id" class="fees_class_type_id"/>
                                            <div class="form-group col-md-12 col-lg-4">
                                                <select name="fees_type_id" id="fees_type_id" class="form-control fees_type" aria-label="Fees Type" required>
                                                    <option value="" hidden="">{{ __('Select Fees Type')}}</option>
                                                    @foreach ($feesTypeData as $feesType)
                                                        <option value="{{ $feesType->id }}">{{ $feesType->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="form-group col-md-12 col-lg-3">
                                                {!! Form::text('amount', null, ['class' => 'form-control amount','placeholder' => __('enter').' '.__('fees').' '.__('amount'),'id' => 'amount', 'required' => true, 'min' => 0, "data-convert" => "number"]) !!}
                                            </div>
                                            <div class="form-group col-md-12 col-lg-2">
                                                <label>{{ __('optional') }} <span class="text-danger">*</span></label>
                                                <div>
                                                    <div class="form-check-inline">
                                                        <label class="form-check-label">
                                                            {!! Form::radio('optional', 1, '', ['class' => 'form-check-input optional_yes', 'required' => true]) !!}
                                                            {{ __('Yes') }}
                                                        </label>
                                                    </div>
                                                    <div class="form-check-inline">
                                                        <label class="form-check-label">
                                                            {!! Form::radio('optional', 0, '', ['class' => 'form-check-input optional_no', 'required' => true]) !!}
                                                            {{ __('No') }}
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-12 col-lg-1">
                                                <button type="button" class="btn btn-inverse-danger mt-2 btn-icon remove-fees-type" data-repeater-delete>
                                                    <i class="fa fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="col-md-4 pl-0 mb-4">
                                            <button class="btn btn-dark btn-sm add-fees-type" type="button" data-repeater-create>
                                                <i class="fa fa-plus-circle fa-3x mr-2" aria-hidden="true"></i>
                                                {{__('Add New Data')}}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="border border-secondary rounded-lg mb-2 p-2 mb-3">
                                <div class="col-12 mt-1">
                                    <h4 class="card-title">
                                        {{ __('Fees Installment')}}
                                    </h4>
                                    <hr>
                                </div>
                                <div class="mb-4">
                                    <div class="form-inline col-md-4">
                                        <label>{{__('include_fees_installment')}}</label> <span class="ml-1 text-danger">*</span>
                                        <div class="ml-4 d-flex">
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    <input type="radio" name="include_fee_installments" class="fees-installment-toggle" value="1" {{$fees->include_fee_installments==1 ? 'checked':''}}>
                                                    {{ __('Enable') }}
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    <input type="radio" name="include_fee_installments" id="disable-installment" class="fees-installment-toggle" value="0" {{$fees->include_fee_installments==0 ? 'checked':''}}>
                                                    {{ __('Disable') }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="fees-installment-repeater" style="{{$fees->include_fee_installments==0 ? 'display: none':''}}">

                                    <div data-repeater-list="fees_installments">
                                        <div data-repeater-item class="col-12 row">
                                            <input type="hidden" name="id" class="installment_id"/>
                                            <div class="form-group col-lg-12 col-xl-4">
                                                <label>{{ __('installment_name') }} <span class="text-danger">*</span></label>
                                                {{ Form::text('name', null, ['class' => 'form-control installment-name', 'placeholder' => __('installment') . ' ' . __('name'), 'required']) }}
                                            </div>
                                            <div class="form-group col-lg-12 col-xl-4">
                                                <label>{{ __('due_date') }} <span class="text-danger">*</span></label>
                                                {{ Form::text('due_date', null, ['class' => 'datepicker-popup form-control installment-due-date', 'placeholder' => __('due_date'),'autocomplete'=>'off' ,'required']) }}
                                            </div>
                                            <div class="form-group col-lg-12 col-xl-3">
                                                <label>{{ __('due_charges') }} <span class="text-danger">*</span><span class="text-info small">( {{__('in_percentage')}} )</span></label>
                                                {!! Form::text("due_charges",null, ["class" => "installment-due-charges form-control" , "placeholder" => trans('due_charges') , "required" => true , "data-convert" => "number", "min"=>0]) !!}
                                            </div>
                                            <div class="form-group col-lg-12 col-xl-1 mt-4">
                                                <button type="button" class="btn btn-inverse-danger btn-icon remove-installment-fee" data-repeater-delete>
                                                    <i class="fa fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="col-md-4 pl-0 mb-4 mt-4">
                                            <button class="btn btn-dark btn-sm add-installment" type="button" data-repeater-create>
                                                <i class="fa fa-plus-circle fa-3x mr-2" aria-hidden="true"></i>
                                                {{__('Add New Data')}}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <input class="btn btn-theme" type="submit" value={{ __('submit') }}>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('script')
    <script>
        $(document).ready(function () {
            feesClassTypeRepeater.setList([
                    @foreach($fees->fees_class_type as $type)
                {
                    id: "{{$type->id}}",
                    fees_type_id: "{{$type->fees_type_id}}",
                    amount: "{{$type->amount}}",
                    optional: "{{$type->optional}}"
                },
                @endforeach
            ]);

            feesInstallmentRepeater.setList([
                    @foreach($fees->installments as $installment)
                {
                    id: "{{$installment->id}}",
                    name: "{{$installment->name}}",
                    due_date: "{{$installment->due_date}}",
                    due_charges: "{{$installment->due_charges}}"
                },
                @endforeach
            ]);

            @if(count($fees->installments) > 0)
            $('#disable-installment').attr('disabled', true);
            @endif

            @if($fees->fees_paid_count > 0)
            {{--Make readonly to certain fields as fees have already paid --}}
            $('.optional_yes,.optional_no,.fees-installment-toggle').attr('readonly', true).bind('click', function () {
                return false;
            });

            $('.installment-name,.installment-due-date,.installment-due-charges,.fees_type,.amount').attr('readonly', true);
            // $('.fees_type').attr('disabled', true);
            $('.fees_type option:not(:selected)').attr('disabled', true);


            $('.remove-fees-type,.remove-installment-fee').bind('click', function () {
                return false;
            });
            $('.add-fees-type,.add-installment').prop('disabled', true);
            @endif
        })
    </script>
@endsection
