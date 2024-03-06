@extends('layouts.master')

@section('title')
    {{ __('manage') . ' ' . __('online'). ' '.__('exam') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') . ' ' . __('online'). ' '.__('exam') }}
            </h3>
        </div>
        <div class="row">
            @can('online-exam-create')
                <div class="col-md-12 grid-margin stretch-card search-container">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title mb-4">
                                {{ __('create') . ' ' . __('online'). ' '.__('exam') }}
                            </h4>
                            <form class="pt-3 mt-6" id="create-form" method="POST" action="{{ route('online-exam.store') }}">
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label>{{ __('Class Section') }} <span class="text-danger">*</span></label>
                                        <select required name="class_section_id" id="class-section-id" class="form-control select2 online-exam-class-section-id" style="width:100%;" tabindex="-1" aria-hidden="true">
                                            <option value="">--- {{ __('select') . ' ' . __('Class Section') }} ---</option>
                                            @foreach ($classSections as $data)
                                                <option value="{{ $data->id }}" data-class-id="{{ $data->class_id }}">
                                                    {{ $data->full_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>{{ __('subject') }} <span class="text-danger">*</span></label>
                                        @if (Auth::user()->hasRole('School Admin'))
                                            <select required name="class_subject_id" id="class-subject-id" class="form-control">
                                                <option value="">-- {{ __('Select Subject') }} --</option>
                                                <option value="data-not-found">-- {{ __('no_data_found') }} --</option>
                                                @foreach ($classSubjects as $item)
                                                    <option value="{{ $item->id }}" data-class-id="{{ $item->class_id }}">{{ $item->subject_with_name}}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            <select required name="class_subject_id" id="subject-id" class="form-control">
                                                <option value="">-- {{ __('Select Subject') }} --</option>
                                                <option value="data-not-found">-- {{ __('no_data_found') }} --</option>
                                                @foreach ($subjectTeachers as $item)
                                                    <option value="{{ $item->class_subject_id }}" data-class-section="{{ $item->class_section_id }}">{{ $item->subject_with_name}}</option>
                                                @endforeach
                                            </select>    
                                        @endif
                                        
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-4 col-lg-4 col-xl-4">
                                        <label>{{ __('title') }} <span class="text-danger">*</span></label>
                                        {!! Form::text('title', "", ['required','id' => "title","placeholder" => trans('title'),"class" => "form-control" ]) !!}
                                    </div>
                                    <div class="form-group col-md-4 col-lg-4 col-xl-2">
                                        <label>{{ __('exam') }} {{__('key')}} <span class="text-danger">*</span></label>
                                        {!! Form::number('exam_key', "", ['required','id' => "key","placeholder" => trans('exam_key'),"class" => "form-control","min" => 1]) !!}
                                    </div>
                                    <div class="form-group col-md-4 col-lg-4 col-xl-2">
                                        <label>{{ __('duration') }} <span class="text-danger">*</span> <span class="text-info small">( {{__('in_minutes')}} )</span></label>
                                        {!! Form::number('duration', "", ['required','id' => "duration","placeholder" => trans('duration'),"class" => "form-control","min" => 1]) !!}
                                    </div>
                                    <div class="form-group col-md-4 col-lg-4 col-xl-2">
                                        <label>{{ __('start_date')}} <span class="text-danger">*</span></label>
                                        {!! Form::datetimeLocal('start_date', "", ['required','id' => "start-date timepicker-example","placeholder" => trans('start_date'),"class" => "form-control"]) !!}
                                    </div>
                                    <div class="form-group col-md-4 col-lg-4 col-xl-2">
                                        <label>{{ __('end_date') }} <span class="text-danger">*</span></label>
                                        {!! Form::datetimeLocal('end_date', "", ['required','id' => "end-date","placeholder" => trans('end_date'),"class" => "form-control"]) !!}
                                    </div>
                                </div>

                                <input class="btn btn-theme" id="add-online-exam-btn" type="submit" value={{ __('submit') }}>
                            </form>
                        </div>
                    </div>
                </div>
            @endcan

            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('list') . ' ' . __('exams') }}
                        </h4>
                        <div class="d-block">
                            <div class="">
                                <div class="col-12 text-right d-flex justify-content-end text-right align-items-end">
                                    <b><a href="#" class="table-list-type active mr-2">All</a></b> | <a href="#" class="ml-2 table-list-type">Trashed</a>
                                </div>
                            </div>
                        </div>
                        <div class="row" id="toolbar">
                            <div class="form-group col-12 col-sm-12 col-md-3 col-lg-6">
                                <label for="filter-class-section-id" class="filter-menu">{{__("class_section")}}</label>
                                <select name="class_section_id" id="filter-class-section-id" class="form-control" style="width:100%;" tabindex="-1" aria-hidden="true">
                                    <option value="">{{ __('all') }}</option>
                                    @foreach ($classSections as $data)
                                        <option value="{{ $data->id }}" data-class-id="{{ $data->class_id }}">
                                            {{ $data->full_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-12 col-sm-12 col-md-3 col-lg-6">
                                <label for="filter-subject-id" class="filter-menu">{{__("subject")}}</label>
                                @if (Auth::user()->hasRole('School Admin'))
                                    <select name="class_subject_id" id="filter-class-subject-id" class="form-control select2" style="width:100%;" tabindex="-1" aria-hidden="true">
                                        <option value="">-- {{ __('Select Subject') }} --</option>
                                        {{-- <option value="data-not-found">-- {{ __('no_data_found') }} --</option> --}}
                                        @foreach ($classSubjects as $item)
                                            <option value="{{ $item->id }}" data-class-id="{{ $item->class_id }}">{{ $item->subject_with_name}}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <select name="class_subject_id" id="filter-subject-id" class="form-control select2" style="width:100%;" tabindex="-1" aria-hidden="true">
                                        <option value="">-- {{ __('Select Subject') }} --</option>
                                        {{-- <option value="data-not-found">-- {{ __('no_data_found') }} --</option> --}}
                                        @foreach ($subjectTeachers as $item)
                                            <option value="{{ $item->class_subject_id }}" data-class-section="{{ $item->class_section_id }}">{{ $item->subject_with_name}}</option>
                                        @endforeach
                                    </select>
                                @endif
                                
                            </div>
                        </div>
                        <table aria-describedby="mydesc" class='table' id='table_list'
                               data-toggle="table" data-url="{{ route('online-exam.show', 1) }}" data-click-to-select="true"
                               data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                               data-search="true" data-toolbar="#toolbar" data-show-columns="true"
                               data-show-refresh="true" data-fixed-columns="true" data-fixed-right-number="1"
                               data-trim-on-search="false" data-mobile-responsive="true" data-sort-name="id"
                               data-sort-order="desc" data-maintain-selected="true" data-export-data-type='all'
                               data-export-options='{ "fileName": "{{__('online').' '.__('exam')}}-<?= date(' d-m-y') ?>" ,"ignoreColumn":["operate"]}'
                               data-show-export="true" data-query-params="onlineExamQueryParams" data-escape="true" data-escape-title="false">
                            <thead>
                            <tr>
                                <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{ __('id') }}</th>
                                <th scope="col" data-field="no">{{ __('no.') }}</th>
                                <th scope="col" data-field="class_section_name">{{ __('class_section') }}</th>
                                <th scope="col" data-field="subject_name">{{ __('subject') }}</th>
                                <th scope="col" data-field="title">{{ __('title') }}</th>
                                <th scope="col" data-field="exam_key" data-align="center">{{ __('exam_key')}}</th>
                                <th scope="col" data-field="duration" data-align="center">{{ __('duration')}} <span class="text-info small">( {{__('in_minutes')}} )</span></th>
                                <th scope="col" data-field="start_date" data-sortable="true">{{ __('start_date') }}</th>
                                <th scope="col" data-field="end_date" data-sortable="true">{{ __('end_date') }}</th>
                                <th scope="col" data-field="total_questions" data-align="center">{{ __('total').' '.__('questions') }}</th>
                                <th scope="col" data-field="created_at" data-sortable="true" data-visible="false">{{ __('created_at') }}</th>
                                <th scope="col" data-field="updated_at" data-sortable="true" data-visible="false">{{ __('updated_at') }}</th>
                                <th scope="col" data-field="operate" data-events="onlineExamEvents" data-escape="false">{{ __('action') }}</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- model --}}
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">{{__('edit')}} {{__('online')}} {{__('exam')}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fa fa-close"></i></span>
                    </button>
                </div>
                <form id="edit-form" class="pt-3 edit-form" action="{{ url('online-exam') }}">
                    <input type="hidden" name="edit_id" id="edit_id">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>{{ __('title') }} <span class="text-danger">*</span></label>
                            <input type="text" id="edit-online-exam-title" required name="edit_title" placeholder="{{ __('title') }}" class="form-control"/>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label>{{ __('exam') }} {{__('key')}} <span class="text-danger">*</span></label>
                                <input type="number" id="edit-online-exam-key" required name="edit_exam_key" placeholder="{{ __('exam_key') }}" class="form-control"/>
                            </div>
                            <div class="form-group col-md-6">
                                <label>{{ __('duration') }} <span class="text-danger">*</span></label><span class="text-info small">( {{__('in_minutes')}} )</span>
                                <input type="number" id="edit-online-exam-duration" required name="edit_duration" placeholder="{{ __('duration') }}" class="form-control"/>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label>{{ __('start_date')}} <span class="text-danger">*</span></label>
                                <input type="datetime-local" id="edit-online-exam-start-date" required name="edit_start_date" placeholder="{{__('start_date')}}" class='form-control'>
                            </div>
                            <div class="form-group col-md-6">
                                <label>{{ __('end_date') }} <span class="text-danger">*</span></label>
                                <input type="datetime-local" id="edit-online-exam-end-date" required name="edit_end_date" placeholder="{{ __('end_date')}}" class='form-control'>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('close') }}</button>
                        <input class="btn btn-theme" type="submit" value={{ __('submit') }} />
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
