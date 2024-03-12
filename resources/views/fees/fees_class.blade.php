@extends('layouts.master')

@section('title')
    {{__('fees')}} {{__('classes')}}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') }} {{__('fees')}} {{__('classes')}}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-end">
                            <a class="btn btn-sm btn-theme" href="{{ route('fees.index') }}">{{ __('back') }}</a>
                        </div>
                        <label>{{ __('fee') }} <span class="text-danger">*</span></label>
                        {!! Form::text('', $fees->name, ['class' => 'form-control mb-4', 'readonly' => true]) !!}
                        <div id="toolbar">
                            {!! Form::hidden('', $fees->id, ['id' => 'fees-id']) !!}
                            <select name="filter-medium-id" id="filter-medium-id" class="form-control">
                                <option value="">{{ __('all') }}</option>
                                @foreach ($mediums as $medium)
                                    <option value="{{ $medium->id }}">
                                        {{ $medium->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <table aria-describedby="mydesc" class='table table-striped' id='table_list' data-toggle="table" data-url="{{ route('fees.class-list') }}" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true" data-fixed-columns="true" data-fixed-number="2" data-fixed-right-number="1" data-trim-on-search="false" data-mobile-responsive="true" data-sort-name="id" data-sort-order="desc" data-maintain-selected="true" data-export-types='["txt","excel"]' data-query-params="AssignclassQueryParams" data-export-options='{ "fileName": "class-list-<?= date(' d-m-y') ?>" ,"ignoreColumn": ["operate"]}' data-show-export="true">
                            <thead>
                            <tr>
                                <th scope="col" data-field="class_id" data-sortable="true" data-visible="false">{{ __('id') }}</th>
                                <th scope="col" data-field="no">{{ __('no.') }}</th>
                                <th scope="col" data-field="class_name">{{ __('Class') }}</th>
                                <th scope="col" data-field="fees_type" data-align="left" data-formatter="feesTypeFormatter">{{ __('Fees') }} {{__('type')}}</th>
                                <th scope="col" data-field="base_amount" data-align="center" data-formatter="compulsoryFeesClass">{{ __('Compulsory Amount')}}</th>
                                <th scope="col" data-field="total_amount" data-align="center" data-formatter="totalFeesClass">{{ __('Total Amount')}}</th>
                                <th scope="col" data-field="created_at" data-sortable="true" data-visible="false">{{ __('created_at') }}</th>
                                <th scope="col" data-field="updated_at" data-sortable="true" data-visible="false">{{ __('updated_at') }}</th>
                                <th scope="col" data-field="operate" data-events="feesClassEvents"> {{ __('action') }}</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Modal -->
            <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">
                                {{ __('assign_class_fees')}}
                            </h5>
                            <button type="button" class="close" id="closeFeesClassModal" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>


                        <form class="pt-3" id="create-form" action="{{ route('fees.class-update') }}" data-success-function="formSuccessFunction" novalidate="novalidate">
                            <div class="modal-body">
                                <div class="form-group">
                                    {!! Form::hidden('fees_id', $fees->id) !!}
                                    <label>{{ __('class') }} <span class="text-danger">*</span></label>
                                    <select id="edit_class_id" class="form-control">
                                        @foreach ($classes as $class)
                                            <option value="{{ $class->id}}" data-medium="{{$class->medium_id}}"> {{ $class->full_name }}</option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="class_id" id="class_id" value=""/>
                                </div>

                                <h4 class="mb-3">
                                    {{ __('Fees Type')}}
                                </h4>

                                <div class="class-fees-data">
                                    <div data-repeater-list="fees_data" class="row">
                                        <div class="row col-12 mb-3" data-repeater-item>
                                            {!! Form::hidden('id', null, ['id' => 'fees-class-id']) !!}
                                            <div class="form-group col-md-12 col-lg-4">
                                                <select name="fees_type_id" class="form-control fees_type" required>
                                                    <option value="">{{ __('Select Fees Type')}}</option>
                                                    @foreach ($feesTypeData as $feesType)
                                                        <option value="{{ $feesType->id }}">
                                                            {{ $feesType->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="form-group col-md-12 col-lg-3">
                                                {!! Form::text('amount', null, ['class' => 'form-control amount','placeholder' => __('enter').' '.__('fees').' '.__('amount'),'id' => 'amount', 'required' => true, 'min' => 0, "data-convert" => "number"]) !!}
                                            </div>
                                            <div class="form-group col-md-12 col-lg-3">
                                                <label>{{ __('choiceable') }} <span class="text-danger">*</span></label>
                                                <div class="d-flex">
                                                    <div class="form-check-inline">
                                                        <label class="form-check-label">
                                                            {!! Form::radio('choiceable', 1, '', ['class' => 'form-check-input choiceable_yes', 'required' => true]) !!}
                                                            {{ __('Yes') }}
                                                        </label>
                                                    </div>
                                                    <div class="form-check-inline">
                                                        <label class="form-check-label">
                                                            {!! Form::radio('choiceable', 0, '', ['class' => 'form-check-input choiceable_no', 'required' => true]) !!}
                                                            {{ __('No') }}
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-12 col-lg-1">
                                                <button type="button" class="btn btn-inverse-danger mt-2 btn-icon remove-fees-data" data-repeater-delete>
                                                    <i class="fa fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 pl-0 mb-4 mt-4">
                                        <button class="btn btn-dark btn-sm" type="button" data-repeater-create>
                                            <i class="fa fa-plus-circle fa-3x mr-2" aria-hidden="true"></i>
                                            {{__('Add New Data')}}
                                        </button>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('close') }}</button>
                                    <input class="btn btn-theme" type="submit" value={{ __('save') }} />
                                </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        function formSuccessFunction(response) {
            if (!response.error) {
                $('#editModal').modal('hide');
                feeClassIdCounter = 0;
            }
        }
    </script>
@endsection
