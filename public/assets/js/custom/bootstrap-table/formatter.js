// Bootstrap Custom Column Formatters
// noinspection JSUnusedGlobalSymbols

function fileFormatter(value, row) {
    if (row.file && row.file.length) {
        let file_upload = "<br><h6>File Upload</h6>";
        let youtube_link = "<br><h6>YouTube Link</h6>";
        let video_upload = "<br><h6>Video Upload</h6>";
        let other_link = "<br><h6>Other Link</h6>";

        let file_upload_counter = 1;
        let youtube_link_counter = 1;
        let video_upload_counter = 1;
        let other_link_counter = 1;

        $.each(row.file, function (key, data) {
            //1 = File Upload , 2 = YouTube , 3 = Uploaded Video , 4 = Other
            if (data.type == 1) {
                // 1 = File Upload
                file_upload += "<a href='" + data.file_url + "' target='_blank' >" + file_upload_counter + ". " + data.file_name + "</a><br>";
                file_upload_counter++;
            } else if (data.type == 2) {
                // 2 = YouTube Link
                youtube_link += "<a href='" + data.file_url + "' target='_blank' >" + youtube_link_counter + ". " + data.file_name + "</a><br>";
                youtube_link_counter++;
            } else if (data.type == 3) {
                // 3 = Uploaded Video
                video_upload += "<a href='" + data.file_url + "' target='_blank' >" + video_upload_counter + ". " + data.file_name + "</a><br>";
                video_upload_counter++;
            } else if (data.type == 4) {
                // 4 = Other Link
                other_link += "<a href='" + data.file_url + "' target='_blank' >" + other_link_counter + ". " + data.file_name + "</a><br>";
                other_link_counter++;
            }
        })
        let html = "";
        if (file_upload_counter > 1) {
            html += file_upload;
        }

        if (youtube_link_counter > 1) {
            html += youtube_link;
        }

        if (video_upload_counter > 1) {
            html += video_upload;
        }

        if (other_link_counter > 1) {
            html += other_link;
        }

        return html;
    } else {
        return " - ";
    }
}


function packageFeatureFormatter(value, row) {
    let html = '';
    html += "<ul>";
    $.each(row.package_feature, function (value, data) {
        html += "<li>" + data.feature.name + "</li>";
    });
    html += "</ul>";
    return html;
}

function yesAndNoStatusFormatter(value) {
    if (value) {
        return "<span class='badge badge-success'>" + window.trans["Yes"] + "</span>";
    } else {
        return "<span class='badge badge-danger'>" + window.trans["No"] + "</span>";
    }
}

function descriptionFormatter(value, row) {
    let html = '';
    if (value) {
        html = '<div class="bootstrap-table-description" data-toggle="modal" data-target="#descriptionModal">' + value + '</div>';
    }
    return html;
}

function leaveStatusFormatter(value) {
    if (value == 0) {
        return "<span class='badge badge-warning'>" + window.trans["pending"] + "</span>";
    } else if (value == 1) {
        return "<span class='badge badge-success'>" + window.trans["approved"] + "</span>";
    } else {
        return "<span class='badge badge-danger'>" + window.trans["rejected"] + "</span>";
    }
}

function userTypeFormatter(value, row) {
    let html = '';
    if (row.user_status) {
        if (row.user_status.status == 0) {
            html = '<div class="d-flex"><div class="form-check mr-2"><label class="form-check-label"><input required type="radio" class="type form-check-input" id="' + row.id + '" name="user_status[' + row.id + '][type]" value="1">' + window.trans['enable'] + '<i class="input-helper"></i></label></div><div class="form-check"><label class="form-check-label"><input type="radio" class="type form-check-input" id="' + row.id + '" name="user_status[' + row.id + '][type]" checked value="0">' + window.trans['disable'] + '<i class="input-helper"></i></label></div></div>';
        } else {
            html = '<div class="d-flex"><div class="form-check mr-2"><label class="form-check-label"><input checked required type="radio" class="type form-check-input" id="' + row.id + '" name="user_status[' + row.id + '][type]" value="1">' + window.trans['enable'] + '<i class="input-helper"></i></label></div><div class="form-check"><label class="form-check-label"><input type="radio" class="type form-check-input" id="' + row.id + '" name="user_status[' + row.id + '][type]" value="0">' + window.trans['disable'] + '<i class="input-helper"></i></label></div></div>';
        }
    } else {
        if (row.deleted_at) {
            html = '<div class="d-flex"><div class="form-check mr-2"><label class="form-check-label"><input required type="radio" class="type form-check-input" id="' + row.id + '" name="user_status[' + row.id + '][type]" value="1">' + window.trans['enable'] + '<i class="input-helper"></i></label></div><div class="form-check"><label class="form-check-label"><input type="radio" class="type form-check-input" id="' + row.id + '" name="user_status[' + row.id + '][type]" checked value="0">' + window.trans['disable'] + '<i class="input-helper"></i></label></div></div>';
        } else {
            html = '<div class="d-flex"><div class="form-check mr-2"><label class="form-check-label"><input checked required type="radio" class="type form-check-input" id="' + row.id + '" name="user_status[' + row.id + '][type]" value="1">' + window.trans['enable'] + '<i class="input-helper"></i></label></div><div class="form-check"><label class="form-check-label"><input type="radio" class="type form-check-input" id="' + row.id + '" name="user_status[' + row.id + '][type]" value="0">' + window.trans['disable'] + '<i class="input-helper"></i></label></div></div>';
        }
    }


    return html;
}

function featurePermissionFormatter(value, row) {
    let html = '';
    html += '<ul>';

    $.each(row.permission, function (value, data) {
        html += '<li>' + data + '</li>';
    });
    html += '</ul>';
    return html;
}

function subscriptionStatusFormatter(value, row) {
    // 1 => Current Cycle, 2 => Success, 3 => Over Due, 4 => Failed, 5 => Pending, 6 => Next Billing Cycle
    let html = '';
    if (value == 1) {
        html = "<span class='badge badge-primary'>" + window.trans["current_cycle"] + "</span>";
    } else if (value == 2 || row.amount == 0) {
        html = "<span class='badge badge-success'>" + window.trans["paid"] + "</span>";
    } else if (value == 3) {
        html = "<span class='badge badge-danger-light'>" + window.trans["over_due"] + "</span>";
    } else if (value == 4) {
        html = "<span class='badge badge-danger'>" + window.trans["failed"] + "</span>";
    } else if (value == 5) {
        html = "<span class='badge badge-warning'>" + window.trans["pending"] + "</span>";
    } else if (value == 6) {
        html = "<span class='badge badge-info'>" + window.trans["next_billing_cycle"] + "</span>";
    } else if (value == 7) {
        html = "<span class='badge badge-danger'>" + window.trans["unpaid"] + "</span>";
    } else if (value == 0) {
        html = "<span class='badge badge-dark'>" + window.trans["bill_not_generated"] + "</span>";
    }
    return html;

}

function planDetailFormatter(value, row) {
    let html = '';
    html += row.plan;
    html += '<div class="mt-2"><small class="text-info">' + row.billing_cycle + '</small></div>';
    return html;

}

function salaryInputFormatter(value, row) {
    let html;
    if (value) {
        html = '<input type="number" min="0" required name="basic_salary[' + row.id + ']" class="form-control" value="' + value + '">';
    } else {
        html = '<input type="number" required min="0" name="basic_salary[' + row.id + ']" class="form-control" value="0">';
    }
    return html;
}

function netSalaryInputFormatter(value, row) {
    let html = '';
    if (value) {
        html = '<input type="number" min="0" required name="net_salary[' + row.id + ']" class="form-control" value="' + value + '">';
    } else {
        html = '<input type="number" required min="0" name="net_salary[' + row.id + ']" class="form-control" value="0">';
    }

    html += paid_leave = '<input type="hidden" required name="paid_leave[' + row.id + ']" class="form-control" value="' + row.paid_leaves + '">'

    return html;
}

function salaryStatusFormatter(value) {
    let html;
    if (value == 1) {
        html = '<div class="badge badge-success badge-pill">' + window.trans['paid'] + '</div>';
    } else {
        html = '<div class="badge badge-danger badge-pill">' + window.trans['unpaid'] + '</div>';
    }
    return html;
}

function assignmentFileFormatter(value, row) {
    return "<a target='_blank' href='" + row.file + "'>" + row.name + "</a>";
}


function assignmentSubmissionStatusFormatter(value, row) {
    let html;
    // 0 = Pending/In Review , 1 = Accepted , 2 = Rejected , 3 = Resubmitted
    if (row.status === 0) {
        html = "<span class='badge badge-warning'>Pending</span>";
    } else if (row.status === 1) {
        html = "<span class='badge badge-success'>Accepted</span>";
    } else if (row.status === 2) {
        html = "<span class='badge badge-danger'>Rejected</span>";
    } else if (row.status === 3) {
        html = "<span class='badge badge-warning'>Resubmitted</span>";
    }
    return html;
}

function imageFormatter(value) {
    if (value) {
        return "<a data-toggle='lightbox' href='" + value + "'><img src='" + value + "' class='img-fluid'  alt='image'  onerror='onErrorImage(event)' /></a>";
    } else {
        return '-'
    }
}

function schoolNameFormatter(value, row) {
    let html = '';
    html += '<ul>';
    $.each(row.support_school, function (value, data) {
        html += '<li>' + data.school.name + '</li>';
    });
    html += '</ul>';
    return html;
}

function schoolAdminFormatter(value, row) {
    let html = '';
    html += row.user.full_name;
    html += '<p class="mt-1 text-facebook"><small>' + row.user.email + '</small></p>';
    return html;
}

function linkFormatter(value, row) {
    if (row.link) {
        return "<a href='" + row.link + "' target='_blank'>" + row.link + "</a>";
    } else {
        return '-'
    }
}

function examTimetableFormatter(value, row) {
    let html = []
    if (row.timetable.length != null) {
        $.each(row.timetable, function (key, timetable) {
            html.push('<p>' + timetable.subject.name + '(' + timetable.subject.type + ')  - ' + timetable.total_marks + '/' + timetable.passing_marks + ' - ' + timetable.start_time + ' - ' + timetable.end_time + ' - ' + timetable.date + '</p>')
        });
    }
    return html.join('')
}

function examSubjectFormatter(value, row) {
    if (row.subject_name) {
        return row.subject_name;
    } else {
        return $('#subject_id :selected').text();
    }
}

function examStudentNameFormatter(value, row) {
    return "<input type='hidden' name='exam_marks[" + row.no + "][student_id]' class='form-control' value='" + row.id + "' />" + row.student_name
}

function obtainedMarksFormatter(value, row) {
    if (row.obtained_marks) {
        return "<input type='hidden' name='exam_marks[" + row.no + "][exam_marks_id]' class='form-control' value='" + row.exam_marks_id + "' />" +
            "<input type='number' required max='" + row.total_marks + "'  name='exam_marks[" + row.no + "][obtained_marks]' class='form-control' min='0' value='" + row.obtained_marks + "' />" + "<input type='hidden' name='exam_marks[" + row.no + "][total_marks]' class='form-control' value='" + parseInt(row.total_marks) + "' />"
    } else {
        return "<input type='number' required max='" + row.total_marks + "' name='exam_marks[" + row.no + "][obtained_marks]' class='form-control' min='0' value='" + ' ' + "' />" + "<input type='hidden' name='exam_marks[" + row.no + "][total_marks]' class='form-control' value='" + parseInt(row.total_marks) + "' />"
    }
}

function teacherReviewFormatter(value, row) {
    if (row.teacher_review) {
        return "<textarea name='exam_marks[" + row.no + "][teacher_review]' class='form-control'>" + row.teacher_review + "</textarea>"
    } else {
        return "<textarea name='exam_marks[" + row.no + "][teacher_review]' class='form-control'>" + ' ' + "</textarea>"
    }
}

function coreSubjectFormatter(value, row) {
    let core_subject_count = 1;
    let html = "<div style='line-height: 20px;'>";
    if (row.core_subjects.length) {
        $.each(row.core_subjects, function (key, row) {
            html += core_subject_count + ". " + row.name + " - " + row.type + "<br>"
            core_subject_count++;
        })
    }
    html += "</div>";
    return html;
}

function electiveSubjectFormatter(value, row) {
    let html = "<div style='line-height: 20px;'>";
    $.each(row.elective_subject_groups, function (key, group) {
        let elective_subject_count = 1;
        html += "<b>Group " + (key + 1) + "</b><br>";
        $.each(group.subjects, function (key, subject) {
            html += elective_subject_count + ". " + subject.name + " - " + subject.type + "<br>"
            elective_subject_count++;
        })
        html += "<b>Total Subjects : </b>" + group.total_subjects + "<br>"
        html += "<b>Total Selectable Subjects : </b>" + group.total_selectable_subjects + "<br><br>"
    })
    html += "</div>";
    return html;
}

function feesTypeFormatter(value, row) {
    let html = "<ol>";
    if (row.fees_class_type?.length) {
        $.each(row.fees_class_type, function (key, value) {
            html += "<li>" + value.fees_type_name + " - " + value.amount;
            if (value.optional) {
                html += "<small class='ml-1 badge badge-danger rounded-pill p-1'>" + window.trans["optional"] + "</small>";
            }
            html += "</li>";
        });
    }
    html += "</ol>";
    return html

}

function feesTransactionParentGateway(value, row) {
    if (row.payment_gateway == "Stripe") {
        return "<span class='badge badge-primary'>Stripe</span>";
    } else {
        return "-";
    }
}

function transactionPaymentStatus(value, row) {
    if (row.payment_status == 'succeed' || row.amount == 0) {
        return "<span class='badge badge-success'>" + window.trans["Success"] + "</span>";
    } else if (row.payment_status == 'pending') {
        return "<span class='badge badge-warning'>" + window.trans["pending"] + "</span>";
    } else if (row.payment_status == 'failed') {
        return "<span class='badge badge-danger'>" + window.trans["failed"] + "</span>";
    } else {
        return "<span class='badge badge-warning'>" + window.trans["pending"] + "</span>";
    }
}

function questionTypeFormatter(value, row) {
    if (row.question_type) {
        return "<span class='badge badge-secondary'>" + window.trans["equation_based"] + "</span>"
    } else {
        return "<span class='badge badge-info'>" + window.trans["optionsimple_question"] + " < /span>"
    }
}

function optionsFormatter(value, row) {
    let html = '';
    $.each(row.options, function (index, value) {
        html += "<div class='row'>";
        html += "<div class= 'col-md-1 text-center'><i class='fa fa-arrow-right small' aria-hidden='true'></i></div><div class='col-md-6'>" + value.option + "</div><br>"
        html += "</div>";
    });
    return html;
}

function answersFormatter(value, row) {
    let html = '';
    $.each(row.answers, function (index, value) {
        html += "<div class='row'>";
        html += "<span class= 'col-md-1 text-center'><i class='fa fa-arrow-right small' aria-hidden='true'></i></span><div class='col-md-6'>" + value.answer + "</div><br>"
        html += "</div>";
    });
    return html;
}

function bgColorFormatter(value, row) {
    return "<p style='background-color:" + row.bg_color + "' class='color-code-box'>" + row.bg_color + "</p>";
}

function formFieldDefaultValuesFormatter(value, row) {
    let html = '';
    if (row.default_values && row.default_values.length) {
        html += '<ul>'
        $.each(row.default_values, function (index, value) {
            html += "<i class='fa fa-arrow-right' aria-hidden='true'></i> " + value + "<br>"
        });
    } else {
        html = '<div class="text-center">-</div>';
    }
    return html;
}

function formFieldOtherValueFormatter(value, row) {
    let otherObj = JSON.parse(row.other);
    let html = '';
    if (otherObj) {
        html += '<ul>'
        otherObj.forEach(value => {
            Object.entries(value).forEach(([key, data]) => {
                html += "<i class='fa fa-arrow-right' aria-hidden='true'></i> " + key + ' - ' + data + '<br>'
            });
        });
    } else {
        html = '<div class="text-center">-</div>';
    }
    return html;
}

function addRadioInputAttendance(value, row) {
    let html = "<input type='hidden' value=" + row.id + " name='attendance_data[" + row.no + "][id]'><input type='hidden' name='attendance_data[" + row.no + "][student_id]' value=" + row.user_id + ">"
    if (row.type == 1) {
        html += '<div class="d-flex"><div class="form-check mr-2"><label class="form-check-label"><input required type="radio" class="type form-check-input" name="attendance_data[' + row.no + '][type]" value="1" checked>Present<i class="input-helper"></i></label></div><div class="form-check mr-2"><label class="form-check-label"><input type="radio" class="type form-check-input" name="attendance_data[' + row.no + '][type]" value="0">Absent<i class="input-helper"></i></label></div></div>';
    } else if (row.type == 0) {
        html += '<div class="d-flex"><div class="form-check mr-2"><label class="form-check-label"><input required type="radio" class="type form-check-input" name="attendance_data[' + row.no + '][type]" value="1">Present<i class="input-helper"></i></label></div><div class="form-check mr-2"><label class="form-check-label"><input type="radio" class="type form-check-input" name="attendance_data[' + row.no + '][type]" value="0" checked>Absent<i class="input-helper"></i></label></div></div>';
    } else {
        html += '<div class="d-flex"><div class="form-check mr-2"><label class="form-check-label"><input required type="radio" class="type form-check-input" name="attendance_data[' + row.no + '][type]" value="1" checked>Present<i class="input-helper"></i></label></div><div class="form-check"><label class="form-check-label"><input type="radio" class="type form-check-input" name="attendance_data[' + row.no + '][type]" value="0">Absent<i class="input-helper"></i></label></div></div>';
    }
    return html;
}

function addStudentIdInputAttendance(value, row) {
    return "<input type='text' class='form-control' readonly value=" + row.student_id + ">";
}

function timetableDayFormatter(value) {
    let html = "<ol>";
    value.forEach(function (data) {
        html += "<li><b>" + data.title + " : </b><small>" + data.start_time + " - " + data.end_time + "</small></li>";
    })
    html += "</ol>";
    return html;
}

function teacherTimetableDayFormatter(value) {
    let html = "<ol>";
    value.forEach(function (data) {
        html += "<li><b>" + data.class_section.name + " - " + data.title + " : </b><small>" + data.start_time + " - " + data.end_time + "</small></li>";
    })
    html += "</ol>";
    return html;
}

function classTeacherListFormatter(value, row) {
    if (row.class_teachers_list.length) {
        let html = "<ol>";
        row.class_teachers_list.forEach(function (data) {
            html += "<li>" + data + " </li>";
        })
        html += "</ol>";
        return html;
    }
}

function subjectTeacherListFormatter(value, row) {
    let html = "<ol>";
    row.subject_teachers_list.forEach(function (data) {
        html += "<li>" + data + " </li>";
    })
    html += "</ol>";
    return html;
}

function promoteStudentResultFormatter(value, row) {
    if (value) {
        return "<input type='hidden' name='promote_data[" + row.no + "][student_id]' value='" + row.user_id + "'><div class='d-flex'><div class='form-check mr-2'><label class='form-check-label'> <input required type='radio' class='result form-check-input'  name='promote_data[" + row.no + "][result]' value='1' " + value == 1 ? "selected" : '' + ">" + window.trans["pass"] + "<i class='input-helper'></i></label></div><div class='form-check-inline'><label class='form-check-label'> <input type='radio' class='result form-check-input'  name='promote_data[" + row.no + "][result]' value='0' " + value == 0 ? "selected" : '' + ">" + window.trans["fail"] + " <i class='input-helper'></i></label></div></div>";
    } else {

        return "<input type='hidden' name='promote_data[" + row.no + "][student_id]' value='" + row.user_id + "'>" +
            "<div class='d-flex'>" +
            "<div class='form-check mr-2'>" +
            "<label class='form-check-label'>" +
            "<input required type='radio' class='result form-check-input' name='promote_data[" + row.no + "][result]' value='1' checked>" +
            window.trans["pass"] +
            " <i class='input-helper'></i></label>" +
            "</div>" +
            "<div class='form-check'>" +
            "<label class='form-check-label'>" +
            "<input type='radio' class='result form-check-input' name='promote_data[" + row.no + "][result]' value='0'>" +
            window.trans["fail"] +
            " <i class='input-helper'></i></label>" +
            "</div>" +
            "</div>";
    }
}

function promoteStudentStatusFormatter(value, row) {
    if (value) {
        return "<div class='d-flex'><div class='form-check form-check-info mr-2'><label class='form-check-label'> <input required type='radio' class='status form-check-input'  name='promote_data[" + row.no + "][status]' value='1' " + value == 1 ? "selected" : '' + ">" + window.trans["continue"] + "<i class='input-helper'></i></label></div><div class='form-check form-check-info'><label class='form-check-label'> <input type='radio' class='status form-check-input'  name='promote_data[" + row.no + "][status]' value='0' " + value == 0 ? "selected" : '' + ">" + window.trans["leave"] + " <i class='input-helper'></i></label></div></div>";
    } else {
        return "<div class='d-flex'><div class='form-check form-check-info mr-2'><label class='form-check-label'> <input required type='radio' class='status form-check-input'  name='promote_data[" + row.no + "][status]' value='1' checked>" + window.trans["continue"] + "<i class='input-helper'></i></label></div><div class='form-check form-check-info'><label class='form-check-label'> <input type='radio' class='status form-check-input'  name='promote_data[" + row.no + "][status]' value='0'>" + window.trans["leave"] + " <i class='input-helper'></i></label></div></div>";
    }
}


// function promoteStudentStudentIDFormatter(value, row) {
//     return "<input type='text' name='promote_data[" + row.no + "][student_id]' class='form-control' value='" + row.user_id + "' readonly>";
// }

function feesPaidStatusFormatter(value, row) {
    if (row.fees_status == 1) {
        return "<span class='badge badge-success'>" + window.trans["Success"] + "</span>"
    } else if (row.fees_status == 0) {
        return "<span class='badge badge-info'>" + window.trans["Partial Paid"] + "</span>"
    } else {
        return "<span class='badge badge-warning'>" + window.trans["Pending"] + "</span>";
    }
}

function classSubjectsDetailFormatter(value, row) {
    if (row.include_semesters) {
        let html = `<table class="table table-borderless">`
        $.each(row.semesters, function (index, semester) {

            let CoreSubjectsList = "";
            // console.log(row.semester_wise_core_subjects[semester.id]);
            if (typeof row.semester_wise_core_subjects[semester.id] !== "undefined") {
                $.each(row.semester_wise_core_subjects[semester.id], function (index, subject) {
                    CoreSubjectsList += (index + 1) + '. ' + subject.name_with_type + '<br>';
                });
            }

            let ElectiveSubjectsList = "";
            if (typeof row.semester_wise_elective_subject_groups[semester.id] !== "undefined") {
                $.each(row.semester_wise_elective_subject_groups[semester.id], function (index, group) {
                    let subjectsList = ""
                    $.each(group.subjects, function (index, subject) {
                        subjectsList += (index + 1) + '. ' + subject.name + ' - ' + subject.type + '<br>'
                    });
                    ElectiveSubjectsList += '<b>' + window.trans["group"] + '</b> - ' + (index + 1) + '<br>' + subjectsList + '<b>' + window.trans["total_subjects"] + '</b> : ' + group.total_subjects + '<br> <b>' + window.trans["total_selectable_subjects"] + '</b> : ' + group.total_selectable_subjects + '<br><br>';
                });
            }

            html += `<thead>
                        <tr>
                            <th scope="col"></th>
                            <th scope="col" class="text-right pr-5"><h3><u>` + semester.name + `</u></h3></th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <thead>
                        <tr>
                            <th scope="col"></th>
                            <th scope="col">` + window.trans["Core Subjects"] + `</th>
                            <th scope="col">` + window.trans["elective_subject"] + `</th>
                        </tr>
                    </thead>
                    <tbody border="2">
                        <tr>
                            <th scope="row">-></th>
                            <td>` + CoreSubjectsList + `</td>
                            <td>` + ElectiveSubjectsList + `</td>
                        </tr>
                    </tbody>
                    `
        });
        html += '</table>';
        return html;
    }
}

function classSubjectsDetailFilter(value, row) {
    return row.include_semesters == 1
}

function subjectTeachersDetailFilter(value, row) {
    return row.class.include_semesters == 1
}


function SubjectTeachersDetailFormatter(value, row) {
    if (row.class.include_semesters) {
        let html = `<table class="table table-borderless">`
        $.each(row.subject_teachers_with_semester, function (index, semester) {

            // Make Subject Teachers View
            let subject_teachers_data = "";
            $.each(semester.subject_teachers, function (index, subjectData) {
                subject_teachers_data += '<tr><th scope="row">-></th><td>' + subjectData.subject_name + '</td><td>' + subjectData.teacher_name + '</td></tr>'
            });

            // Table View
            html += `<thead>
                        <tr>
                            <th scope="col"></th>
                            <th scope="col" class="text-center"><h3><u>` + semester.semester_name + `</u></h3></th>
                            <th></th>
                        </tr>
                    </thead>
                    <thead>
                        <tr>
                            <th scope="col"></th>
                            <th scope="col">` + window.trans["subject_name"] + `</th>
                            <th scope="col">` + window.trans["subject_teachers"] + `</th>
                        </tr>
                    </thead>
                    <tbody border="2">` + subject_teachers_data + `</tbody>`
        });
        html += '</table>';
        return html;
    }
}

function attendanceTypeFormatter(value) {
    if (value == 0) {
        return '<label class="badge badge-danger">' + window.trans["absent"] + '</label>';
    } else if (value == 1) {
        return '<label class="badge badge-info">' + window.trans["present"] + '</label>';
    } else {
        return '<label class="badge badge-success">' + window.trans["holiday"] + '</label>';
    }
}

function shiftStatusFormatter(value, row) {
    if (row.status == 1) {
        return "<span class='badge badge-success'>" + window.trans["Active"] + "</span>";
    } else {
        return "<span class='badge badge-danger'>" + window.trans["Inactive"] + "</span>";
    }
}

function amountFormatter(value, row) {
    return formatMoney(parseInt(value));
}

function totalFormatter() {
    return window.trans['total'];
}

function totalAmountFormatter(data) {
    let field = this.field
    let amount = 0;
    data.map(function (row) {
        amount += parseInt(row[field]);
    })
    return formatMoney(amount);
}

function feesInstallmentFormatter(value, row) {
    let html;
    if (row.installments) {
        html = "<ol>";
        row.installments.forEach(function (data) {
            html += "<li>" + data.name + " (" + data.due_date + ")</li>";
        })
        html += "</ol>";
    }
    return html;
}
