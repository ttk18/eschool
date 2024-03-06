@extends('layouts.master')

@section('title')
    {{ __('attendance') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') . ' ' . __('attendance') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('create') . ' ' . __('attendance') }}
                        </h4>
                        <form action="{{ route('attendance.store') }}" class="create-form" id="formdata">
                            @csrf
                            <div class="row" id="toolbar">
                                <div class="form-group col-sm-12 col-md-4">
                                    <select required name="class_section_id" id="timetable_class_section" class="form-control select2" style="width:100%;" tabindex="-1" aria-hidden="true">
                                        <option value="">{{ __('select') . ' ' . __('Class') }}</option>
                                        @foreach ($classSections as $section)
                                            <option value="{{ $section->id }}" data-class="{{ $section->class->id }}">
                                                {{ $section->full_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-sm-12 col-md-4">
                                    {!! Form::text('date', null, ['required', 'placeholder' => __('date'), 'class' => 'datepicker-popup form-control', 'id' => 'date','data-date-end-date'=>"0d"]) !!}
                                    <span class="input-group-addon input-group-append"></span>
                                </div>
                                <div class="form-group col-sm-12 col-md-3 holiday-div">
                                    <div class="form-check">
                                        <label class="form-check-label">
                                            <input type="checkbox" class="form-check-input" name="holiday" id="holiday" value="0">
                                            Holiday
                                            <i class="input-helper"></i>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="show_student_list">
                                <table aria-describedby="mydesc" class='table student_table' id='table_list'
                                       data-toggle="table" data-url="{{ route('attendance.show',[1]) }}" data-click-to-select="true"
                                       data-side-pagination="server" data-pagination="false"
                                       data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-refresh="true"
                                       data-toolbar="#toolbar" data-show-columns="true" data-trim-on-search="false" data-mobile-responsive="true" data-sort-name="roll_number"
                                       data-sort-order="asc" data-maintain-selected="true" data-export-data-type='all'
                                       data-export-options='{ "fileName": "attendance-<?= date('d-m-y') ?>" ,"ignoreColumn": ["operate"]}'
                                       data-query-params="attendanceQueryParams" data-escape="true">
                                    <thead>
                                    <tr>
                                        <th scope="col" data-field="id" data-sortable="false" data-visible="false"> {{ __('id') }}</th>
                                        <th scope="col" data-field="no">{{ __('no.') }}</th>
                                        <th scope="col" data-field="student_id" data-sortable="false" data-visible="false" data-formatter="addStudentIdInputAttendance"> {{ __('student_id') }}</th>
                                        <th scope="col" data-field="admission_no" data-sortable="false"> {{ __('admission_no') }}</th>
                                        <th scope="col" data-field="roll_no" data-sortable="false">{{ __('roll_no') }} </th>
                                        <th scope="col" data-field="name" data-escape="false">{{ __('name') }} </th>
                                        <th scope="col" data-field="type" data-formatter="addRadioInputAttendance">{{ __('type') }} </th>
                                    </tr>
                                    </thead>
                                </table>
                            </div>
                            <input class="btn btn-theme btn_attendance mt-4" id="create-btn" type="submit" value={{ __('submit') }}>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')

    <script>
        $('#date').on('input change', function () {
            $('.student_table').bootstrapTable('refresh');
        });

        $('.btn_attendance').hide();
        $('.holiday-div').hide();

        function set_data() {
            $(document).ready(function () {
                student_class = $('#timetable_class_section').val();
                session_year = $('#date').val();

                if (student_class != '' && date != '') {
                    $('.btn_attendance').show();
                    $('.holiday-div').show();
                } else {
                    $('.btn_attendance').hide();
                    $('.holiday-div').hide();
                }
            });
        }

        $('#timetable_class_section,#date').on('change', function () {
            set_data();
        });
    </script>

    <script>
        $('input[name="holiday"]').click(function () {
            class_section_id = $('#timetable_class_section').val();
            date = $('#date').val();
            checkBox = document.getElementById('holiday');
            if (class_section_id != '' && date != '') {
                Swal.fire({
                    title: "{{ __('are_you_sure') }}",
                    text: "",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: "{{ __('Yes') }}"
                }).then((result) => {
                    if (checkBox.checked) {
                        if (result.isConfirmed == true) {
                            $("#holiday").val(3);
                            $('input[name="holiday"]').prop('checked', true);
                            $('.type').prop('required', false);
                        } else {
                            checkBox.checked = false;
                        }
                    } else {
                        if (result.isConfirmed == true) {
                            $("#holiday").val(0);
                            $('.type').prop('required', true);
                            return true;
                        } else {
                            checkBox.checked = true;
                        }

                    }
                })
            }
        });
    </script>
    <script>
        $('#timetable_class_section,#date').on('change , input', function () {
            date = $('#date').val();
            class_section_id = $('#timetable_class_section').val();
            $.ajax({
                url: "{{ url('getAttendanceData') }}",
                type: "GET",
                data: {
                    date: date,
                    class_section_id: class_section_id
                },
                success: function (response) {
                    if (response == 3) {
                        $('input[name="holiday"]').attr('checked', true);
                        $("#holiday").val(3);
                        $('.type').prop('required', false);
                    } else {
                        $('input[name="holiday"]').attr('checked', false);
                        $("#holiday").val(0);
                        $('.type').prop('required', true);
                    }
                }
            });
        });
    </script>
@endsection
