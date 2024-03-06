<?php

use App\Http\Controllers\AddonController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassSchoolController;
use App\Http\Controllers\ClassSectionController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Exam\ExamController;
use App\Http\Controllers\Exam\ExamTimetableController;
use App\Http\Controllers\Exam\GradeController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\FeesController;
use App\Http\Controllers\FeesTypeController;
use App\Http\Controllers\FormFieldsController;
use App\Http\Controllers\GuardianController;
use App\Http\Controllers\GuidanceController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\InstallerController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\LeaveMasterController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\LessonTopicController;
use App\Http\Controllers\MediumController;
use App\Http\Controllers\OnlineExamController;
use App\Http\Controllers\OnlineExamQuestionController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PromoteStudentController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SchoolSettingsController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\SessionYearController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\SliderController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StreamController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SubscriptionWebhookController;
use App\Http\Controllers\SystemSettingsController;
use App\Http\Controllers\SystemUpdateController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebhookController;
use App\Models\User;
use App\Services\CachingService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Auth::routes();
Route::get('/', [AuthController::class, 'login']);
Route::get('/', [Controller::class, 'index']);
Route::post('schools/registration', [SchoolController::class, 'registration']);
Route::post('contact', [Controller::class, 'contact']);
Route::get('subscription/cron-job', [Controller::class, 'cron_job']);
Route::get('set-language/{lang}', [LanguageController::class, 'set_language']);

Route::group(['prefix' => 'install'], static function () {
    Route::get('purchase-code', [InstallerController::class, 'purchaseCodeIndex'])->name('install.purchase-code.index');
    Route::post('purchase-code', [InstallerController::class, 'checkPurchaseCode'])->name('install.purchase-code.post');
});
Route::group(['middleware' => ['Role', 'auth', 'checkSchoolStatus', 'status']], static function () {
    Route::group(['middleware' => 'language'], static function () {
        /*** Dashboard ***/
        Route::get('dashboard', [DashboardController::class, 'index']);
        Route::get('home', [DashboardController::class, 'index'])->name('home');

        /*** Auth ***/
        Route::group(['prefix' => 'auth'], static function () {
            Route::get('logout', [AuthController::class, 'logout'])->name('auth.logout');
            Route::get('check-password', [AuthController::class, 'checkPassword'])->name('auth.check-password');
            Route::get('change-password', [AuthController::class, 'changePasswordIndex'])->name('auth.change-password.index');
            Route::post('change-password', [AuthController::class, 'changePasswordStore'])->name('auth.change-password.update');
            Route::get('profile', [AuthController::class, 'profileEdit'])->name('auth.profile.edit');
            Route::put('profile', [AuthController::class, 'profileUpdate'])->name('auth.profile.update');
        });

        /*** Role & Staff management ***/
        Route::get('staff/support', [StaffController::class, 'support']);
        Route::get("/roles-list", [RoleController::class, 'list'])->name('roles.list');
        Route::resource('roles', RoleController::class);
        Route::resource('staff', StaffController::class);
        Route::put("staff/{id}/change-status", [StaffController::class, 'restore'])->name('staff.restore');
        Route::delete("staff/{id}/deleted", [StaffController::class, 'trash'])->name('staff.trash');
        Route::post("staff/change-status-bulk", [StaffController::class, 'changeStatusBulk'])->name('staff.change-status-bulk');


        /*** Medium ***/
        Route::group(['prefix' => 'mediums'], static function () {
            Route::put("/{id}/restore", [MediumController::class, 'restore'])->name('mediums.restore');
            Route::delete("/{id}/deleted", [MediumController::class, 'trash'])->name('mediums.trash');
        });
        Route::resource('mediums', MediumController::class);


        /*** Section ***/
        Route::group(['prefix' => 'section'], static function () {
            Route::put("/{id}/restore", [SectionController::class, 'restore'])->name('section.restore');
            Route::delete("/{id}/deleted", [SectionController::class, 'trash'])->name('section.trash');
        });
        Route::resource('section', SectionController::class);


        /*** Subject ***/
        Route::group(['prefix' => 'subject'], static function () {
            Route::put("/{id}/restore", [SubjectController::class, 'restore'])->name('subject.restore');
            Route::delete("/{id}/deleted", [SubjectController::class, 'trash'])->name('subject.trash');
        });
        Route::resource('subject', SubjectController::class);

        /*** Class ***/
        Route::group(['prefix' => 'class'], static function () {
            Route::put("/{id}/restore", [ClassSchoolController::class, 'restore'])->name('class.restore');
            Route::delete("/{id}/deleted", [ClassSchoolController::class, 'trash'])->name('class.trash');
            Route::get('/subject', [ClassSchoolController::class, 'classSubjectIndex'])->name('class.subject.index');
            Route::get('/subject/{id}/edit', [ClassSchoolController::class, 'classSubjectEdit'])->name('class.subject.edit');
            Route::put('/subject/{id}/edit', [ClassSchoolController::class, 'classSubjectUpdate'])->name('class.subject.update');
            Route::get('/subject/list', [ClassSchoolController::class, 'classSubjectList'])->name('class.subject.list');
            Route::delete('/subject/{class_subject_id}', [ClassSchoolController::class, 'deleteClassSubject'])->name('class.subject.destroy');
            Route::delete('/subject-group/{group_id}', [ClassSchoolController::class, 'deleteClassSubjectGroup'])->name('class.subject-group.destroy');

        });
        Route::resource('class', ClassSchoolController::class);

        /*** Class Section ***/
        Route::group(['prefix' => 'class-section'], static function () {
            Route::delete('class-teacher/remove/{id}/{class_section_id}', [ClassSectionController::class, 'removeClassTeacher']);
            Route::delete('subject-teacher/remove/{class_section_id}/{teacher_id}/{subject_id}', [ClassSectionController::class, 'removeSubjectTeacher']);
            Route::put("/{id}/restore", [ClassSectionController::class, 'restore'])->name('class-section.restore');
            Route::delete("/{id}/trash", [ClassSectionController::class, 'trash'])->name('class-section.trash');
        });
        Route::resource('class-section', ClassSectionController::class);

        /*** Teachers ***/
        Route::group(['prefix' => 'teachers'], static function () {
            Route::put("/{id}/restore", [TeacherController::class, 'restore'])->name('teachers.restore');
            Route::delete("/{id}/deleted", [TeacherController::class, 'trash'])->name('teachers.trash');
            Route::put("change/status/{id}", [TeacherController::class, 'changeStatus'])->name('teachers.change-status');
            Route::post("/change-status-bulk", [TeacherController::class, 'changeStatusBulk'])->name('staff.change-status-bulk');
        });
        Route::resource('teachers', TeacherController::class);


        /*** Parents ***/
        Route::get('/guardian/search', [GuardianController::class, 'search']);
        Route::resource('guardian', GuardianController::class);

        /*** Students ***/
        Route::group(['prefix' => 'students'], static function () {
            Route::get('create-bulk', [StudentController::class, 'createBulkData'])->name('students.create-bulk-data');
            Route::post('store-bulk', [StudentController::class, 'storeBulkData'])->name('students.store-bulk-data');
            Route::get('download-file', [StudentController::class, 'downloadSampleFile'])->name('student.bulk-data-sample');
            Route::delete('change-status/{id}', [StudentController::class, 'changeStatus'])->name('student.change-status');
            /*** Reset Password ***/
            Route::get('reset-password', [StudentController::class, 'resetPasswordIndex'])->name('students.reset-password.index');
            Route::post('reset-password', [StudentController::class, 'resetPasswordUpdate'])->name('student.reset-password.update');
            Route::get('reset-password-list', [StudentController::class, 'resetPasswordShow'])->name('student.reset-password.show');

            /*** Roll Number ***/
            Route::get('roll-number', [StudentController::class, 'rollNumberIndex'])->name('students.roll-number.index');
            Route::post('roll-number', [StudentController::class, 'rollNumberUpdate'])->name('students.roll-number.update');
            Route::get('roll-number-list', [StudentController::class, 'rollNumberShow'])->name('students.roll-number.show');
            Route::post("change-status-bulk", [StudentController::class, 'changeStatusBulk'])->name('students.change-status-bulk');

            Route::delete("/{id}/deleted", [StudentController::class, 'trash'])->name('student.trash');
        });
        Route::resource('students', StudentController::class);


        /*** Timetable ***/
        Route::group(['prefix' => 'timetable'], static function () {
            Route::put('/settings', [TimetableController::class, 'updateTimetableSettings'])->name('timetable.settings');
            Route::group(['prefix' => '/teacher'], static function () {
                Route::get('/', [TimetableController::class, 'teacherIndex'])->name('timetable.teacher.index');
                Route::get('/list', [TimetableController::class, 'teacherList'])->name('timetable.teacher.list');
                Route::get('/show/{teacher_id}', [TimetableController::class, 'teacherShow'])->name('timetable.teacher.show');
            });
        });
        Route::resource('timetable', TimetableController::class);

        /*** Attendance ***/
        // TODO : Improve this
        Route::group(['prefix' => 'attendance'], static function () {
            Route::get('view-attendance', [AttendanceController::class, 'view'])->name("attendance.view");
            Route::get('student-attendance-list', [AttendanceController::class, 'attendance_show'])->name('attendance.list.show');
            Route::get('getAttendanceData', [AttendanceController::class, 'getAttendanceData']);
        });

        Route::resource('attendance', AttendanceController::class);

        /*** Lesson ***/
        Route::group(['prefix' => 'lesson'], static function () {
            Route::get('/search', [LessonController::class, 'search'])->name('lesson.search');
            Route::put("/{id}/restore", [LessonController::class, 'restore'])->name('lesson.restore');
            Route::delete("/{id}/deleted", [LessonController::class, 'trash'])->name('lesson.trash');

        });
        Route::resource('lesson', LessonController::class);
        Route::delete('file/delete/{id}', [LessonController::class, 'deleteFile'])->name('file.delete');

        /*** Lesson Topic ***/
        Route::group(['prefix' => 'lesson-topic'], static function () {
            Route::put("/{id}/restore", [LessonTopicController::class, 'restore'])->name('lesson-topic.restore');
            Route::delete("/{id}/deleted", [LessonTopicController::class, 'trash'])->name('lesson-topic.trash');
        });
        Route::resource('lesson-topic', LessonTopicController::class);


        /*** Announcement ***/
        Route::group(['prefix' => 'announcement'], static function () {
            Route::put("/{id}/restore", [AnnouncementController::class, 'restore'])->name('announcement.restore');
            Route::delete("/{id}/deleted", [AnnouncementController::class, 'trash'])->name('announcement.trash');
            Route::delete("file/delete/{id}", [AnnouncementController::class, 'fileDelete'])->name('announcement.fileDelete');

        });
        Route::resource('announcement', AnnouncementController::class);

        /*** Holiday ***/
        Route::resource('holiday', HolidayController::class);

        /*** Assignment ***/
        // TODO : Improve this
        Route::get('assignment-submission', [AssignmentController::class, 'viewAssignmentSubmission'])->name('assignment.submission');
        Route::put('assignment-submission/{id}', [AssignmentController::class, 'updateAssignmentSubmission'])->name('assignment.submission.update');
        Route::get('assignment-submission-list', [AssignmentController::class, 'assignmentSubmissionList'])->name('assignment.submission.list');
        Route::put("assignment/{id}/restore", [AssignmentController::class, 'restore'])->name('assignment.restore');
        Route::delete("assignment/{id}/deleted", [AssignmentController::class, 'trash'])->name('assignment.trash');
        Route::resource('assignment', AssignmentController::class);

        /*** Sliders ***/
        Route::resource('sliders', SliderController::class);

        /*** Session Years ***/
        Route::group(['prefix' => 'session-year'], static function () {
            Route::put("/{id}/restore", [SessionYearController::class, 'restore'])->name('session-year.restore');
            Route::delete("/{id}/deleted", [SessionYearController::class, 'trash'])->name('session-year.trash');
            Route::put("/{id}/default", [SessionYearController::class, 'default'])->name('session-year.default');
        });
        Route::resource('session-year', SessionYearController::class);


        /*** Exams ***/

        // Grades
        Route::resource('exam/grade', GradeController::class, ['as' => 'exam']);

        // TODO : Improve this
        // Exam Timetables
        Route::resource('exam/timetable', ExamTimetableController::class, ['as' => 'exam']);
        //        Route::get('exam/timetable/{id}', [ExamController::class, 'indexExamTimetable'])->name('exam.timetable.index');
        Route::post('exams/update-timetable', [ExamController::class, 'updateExamTimetable'])->name('exams.update-timetable');
        Route::delete('exams/delete-timetable/{id}', [ExamController::class, 'deleteExamTimetable'])->name('exams.delete-timetable');

        //Exam Marks
        Route::post('exams/submit-marks', [ExamController::class, 'submitMarks'])->name('exams.submit-marks');
        Route::get('exams/upload-marks', [ExamController::class, 'uploadMarks'])->name('exams.upload-marks');
        Route::get('exams/marks-list', [ExamController::class, 'marksList'])->name('exams.marks-list');

        // Exam Result
        Route::get('exams/exam-result', [ExamController::class, 'getExamResultIndex'])->name('exams.get-result');
        Route::get('exams/show-result', [ExamController::class, 'showExamResult'])->name('exams.show-result');
        Route::post('exams/update-result-marks', [ExamController::class, 'updateExamResultMarks'])->name('exams.update-result-marks');

        // Exams
        Route::get('exams/get-subjects/{exam_id}', [ExamController::class, 'getSubjectByExam'])->name('exams.subject');
        Route::post('exams/publish/{id}', [ExamController::class, 'publishExamResult'])->name('exams.publish');
        Route::put("exams/{id}/restore", [ExamController::class, 'restore'])->name('exams.restore');
        Route::delete("exams/{id}/deleted", [ExamController::class, 'trash'])->name('exams.trash');
        Route::resource('exams', ExamController::class);

        // TODO make two groups promote student and transfer student and classify the routes related to their group
        Route::resource('promote-student', PromoteStudentController::class);
        Route::get('getPromoteData', [PromoteStudentController::class, 'getPromoteData']);
        Route::post('transfer-student-store', [PromoteStudentController::class, 'storeTransferStudent'])->name('transfer-student.store');
        Route::get('transfer-student-list', [PromoteStudentController::class, 'showTransferStudent'])->name('transfer-student.show');

        // TODO : Improve this
        /*** Language ***/
        Route::get('language-sample', [LanguageController::class, 'language_sample']);
        Route::get('language-list', [LanguageController::class, 'show']);
        // Route::put("language/{id}/restore", [LanguageController::class, 'restore'])->name('language.restore');
        // Route::delete("language/{id}/deleted", [LanguageController::class, 'trash'])->name('language.trash');
        Route::resource('language', LanguageController::class);
        // Route::get('set-language/{lang}', [LanguageController::class, 'set_language']);
        //        Route::get('sendtest', [SettingController::class, 'test_mail']);

        Route::group(['prefix' => 'fees-type'], static function () {
            Route::put("/{id}/restore", [FeesTypeController::class, 'restore'])->name('fees-type.restore');
            Route::delete("/{id}/deleted", [FeesTypeController::class, 'trash'])->name('fees-type.trash');
        });
        Route::resource('fees-type', FeesTypeController::class);

        Route::group(['prefix' => 'fees'], static function () {
            // Fees
            Route::put("/{id}/restore", [FeesController::class, 'restore'])->name('fees.restore');
            Route::delete("/{id}/delete", [FeesController::class, 'trash'])->name('fees.trash');
            Route::delete("/installment/{id}", [FeesController::class, 'deleteInstallment'])->name('fees.installment.delete');
            Route::delete("/class-type/{id}", [FeesController::class, 'deleteClassType'])->name('fees.class-type.delete');
            Route::get("/search", [FeesController::class, 'search'])->name('fees.search');


            // Fees Paid
            Route::get('/paid', [FeesController::class, 'feesPaidListIndex'])->name('fees.paid.index');
            Route::get('/paid/list', [FeesController::class, 'feesPaidList'])->name('fees.paid.list');

            Route::get('/pay/compulsory/{feesID}/{studentID}', [FeesController::class, 'payCompulsoryFeesIndex'])->name('fees.compulsory.index');
            Route::post('pay/compulsory', [FeesController::class, 'payCompulsoryFeesStore'])->name('fees.compulsory.store');

            // Optional Fees Payment Offline
            Route::get('/pay/optional/{feesID}/{studentID}', [FeesController::class, 'payOptionalFeesIndex'])->name('fees.optional.index');
            Route::post('pay/optional', [FeesController::class, 'payOptionalFeesStore'])->name('fees.optional.store');

            Route::post('/paid/store', [FeesController::class, 'feesPaidStore'])->name('fees.paid.store');
            Route::put('/paid/update/{id}', [FeesController::class, 'feesPaidUpdate'])->name('fees.paid.update');
            Route::delete('/paid/remove-optional-fee/{id}', [FeesController::class, 'removeOptionalFees'])->name('fees.paid.remove.optional.fees');
            Route::delete('/paid/remove-installment-fees/{id}', [FeesController::class, 'removeInstallmentFees'])->name('fees.paid.remove.installment.fees');
            // Fees Config
            Route::get('/config', [FeesController::class, 'feesConfigIndex'])->name('fees.config.index');
            Route::post('/config/update', [FeesController::class, 'feesConfigUpdate'])->name('fees.config.update');

            Route::post('/optional-paid/store', [FeesController::class, 'optionalFeesPaidStore'])->name('fees.optional-paid.store');


            // Transaction list
            Route::get('/transaction-logs', [FeesController::class, 'feesTransactionsLogsIndex'])->name('fees.transactions.log.index');
            Route::get('/transaction-logs/list', [FeesController::class, 'feesTransactionsLogsList'])->name('fees.transactions.log.list');

            // Receipt
            Route::get('/paid/receipt-pdf/{id}', [FeesController::class, 'feesPaidReceiptPDF'])->name('fees.paid.receipt.pdf');
        });
        Route::resource('fees', FeesController::class);


        // Online Exam
        Route::group(['prefix' => 'online-exam'], static function () {
            Route::put("/{id}/restore", [OnlineExamController::class, 'restore'])->name('online-exam.restore');
            Route::delete("/{id}/deleted", [OnlineExamController::class, 'trash'])->name('online-exam.trash');
            Route::get('/add-questions-index/{id}', [OnlineExamController::class, 'addQuestionIndex'])->name('online-exam.add.questions.index');
            Route::post('/add-new-question', [OnlineExamController::class, 'storeExamQuestionChoices'])->name('online-exam.add-new-question');
            Route::get('/get-class-questions/{id}', [OnlineExamController::class, 'getClassQuestions'])->name('online-exam-question.get-class-questions');
            Route::post('/store-questions-choices', [OnlineExamController::class, 'storeQuestionsChoices'])->name('online-exam.store-choice-question');
            Route::delete('/remove-choiced-question/{id}', [OnlineExamController::class, 'removeQuestionsChoices'])->name('online-exam.remove-choice-question');
            Route::get('/result/{id}', [OnlineExamController::class, 'onlineExamResultIndex'])->name('online-exam.result.index');
            Route::get('/result-show/{id}', [OnlineExamController::class, 'showOnlineExamResult'])->name('online-exam.result.show');
        });
        Route::resource('online-exam', OnlineExamController::class);
        // Route::get('get-subject-online-exam', [OnlineExamController::class, 'getSubjects']);


        Route::group(['prefix' => 'online-exam-question'], static function () {
            Route::delete('/remove-option/{id}', [OnlineExamQuestionController::class, 'removeOptions']);
        });
        Route::resource('online-exam-question', OnlineExamQuestionController::class);
        // End Online Exam Routes

        /*** System Settings ***/
        Route::group(['prefix' => 'system-settings'], static function () {
            Route::get('fcm', [SystemSettingsController::class, 'fcmIndex'])->name('system-settings.fcm');
            Route::get('privacy-policy', [SystemSettingsController::class, 'privacyPolicy'])->name('system-settings.privacy-policy');
            Route::get('terms-condition', [SystemSettingsController::class, 'termsConditions'])->name('system-settings.terms-condition');
            Route::get('contact-us', [SystemSettingsController::class, 'contactUs'])->name('system-settings.contact-us');
            Route::get('about-us', [SystemSettingsController::class, 'aboutUs'])->name('system-settings.about-us');

            /*** Email Settings ***/
            Route::get('email', [SystemSettingsController::class, 'emailIndex'])->name('system-settings.email.index');
            Route::post('email', [SystemSettingsController::class, 'emailUpdate'])->name('system-settings.email.update');
            Route::post('email/verify', [SystemSettingsController::class, 'verifyEmailConfiguration'])->name('system-settings.email.verify');

            /*** App Settings ***/
            Route::get('app', [SystemSettingsController::class, 'appSettingsIndex'])->name('system-settings.app');
            Route::post('app', [SystemSettingsController::class, 'appSettingsUpdate'])->name('system-settings.app.update');

            /*** Payment Settings ***/
            Route::get('payment', [SystemSettingsController::class, 'paymentIndex'])->name('system-settings.payment.index');
            Route::post('payment', [SystemSettingsController::class, 'paymentUpdate'])->name('system-settings.payment.update');

            Route::get('front-site-settings', [SystemSettingsController::class, 'front_site_settings'])->name('system-settings.front-site-settings');
            Route::post('front-site-settings', [SystemSettingsController::class, 'front_site_settings_update'])->name('system-settings.front-site-settings.update');
            Route::get('subscription-settings', [SystemSettingsController::class, 'subscription_settings'])->name('system-settings.subscription-settings');
            Route::post('subscription-settings', [SystemSettingsController::class, 'subscription_settings_update'])->name('system-settings.subscription-settings-store');

            Route::get('school-terms-conditions', [SystemSettingsController::class, 'school_terms_condition'])->name('system-settings.school-terms-condition');

        });

        Route::resource('system-settings', SystemSettingsController::class);

        /*** School Settings ***/
        Route::group(['prefix' => 'school-settings'], static function () {
            Route::get('online-exam', [SchoolSettingsController::class, 'onlineExamIndex'])->name('school-settings.online-exam.index');
            Route::post('online-exam', [SchoolSettingsController::class, 'onlineExamStore'])->name('school-settings.online-exam.store');
        });
        Route::resource('school-settings', SchoolSettingsController::class);

        Route::get('system-update', [SystemUpdateController::class, 'index'])->name('system-update.index');
        Route::post('system-update', [SystemUpdateController::class, 'update'])->name('system-update.update');

        /*** School ***/
        Route::group(['prefix' => 'schools'], static function () {
            Route::put("/{id}/restore", [SchoolController::class, 'restore'])->name('schools.restore');
            Route::delete("/{id}/deleted", [SchoolController::class, 'trash'])->name('schools.trash');
            Route::get('/admin/search', [SchoolController::class, 'adminSearch']);
            Route::put('/admin/update', [SchoolController::class, 'updateAdmin']);
            Route::PUT('/change/status/{id}', [SchoolController::class, 'changeStatus']);
            Route::get('/admin/search', [SchoolController::class, 'searchAdmin']);

        });
        Route::resource('schools', SchoolController::class);

        /*** Form Fields ***/
        Route::group(['prefix' => 'form-fields'], static function () {
            Route::post('/update-rank', [FormFieldsController::class, 'updateRankOfFields']);
            Route::put("/{id}/restore", [FormFieldsController::class, 'restore'])->name('form-fields.restore');
            Route::delete("/{id}/deleted", [FormFieldsController::class, 'trash'])->name('form-fields.trash');
        });
        Route::resource('form-fields', FormFieldsController::class);

        /*** Package ***/
        Route::group(['prefix' => 'package'], static function () {
            Route::get('status/{id}', [PackageController::class, 'status']);
            Route::put('restore/{id}', [PackageController::class, 'restore'])->name('package.restore');
            Route::delete('trash/{id}', [PackageController::class, 'trash'])->name('package.trash');
            Route::PATCH('change/rank', [PackageController::class, 'change_rank']);

        });
        Route::resource('package', PackageController::class);

        // Features
        Route::get('features', [PackageController::class, 'features_list']);
        Route::get('features/show', [PackageController::class, 'features_show'])->name('features.show');

        // Subscription
        Route::group(['prefix' => 'subscriptions'], static function () {
            Route::get('plan/{id}', [SubscriptionController::class, 'plan']);
            Route::get('history', [SubscriptionController::class, 'history'])->name('subscriptions.history');
            Route::get('cancel-upcoming/{id?}', [SubscriptionController::class, 'cancel_upcoming'])->name('subscriptions.cancel.upcoming');
            Route::get('confirm-upcoming-plan/{id}', [SubscriptionController::class, 'confirm_upcoming_plan']);
            Route::get('payment/success/{checkout_session_id}/{id}', [SubscriptionController::class, 'payment_success']);
            Route::get('payment/cancel', [SubscriptionController::class, 'payment_cancel']);
            Route::get('bill/receipt/{id}', [SubscriptionController::class, 'bill_receipt']);
            Route::get('report', [SubscriptionController::class, 'subscription_report']);
            Route::get('report/show/{status?}', [SubscriptionController::class, 'subscription_report_show']);
            Route::put('update-expiry', [SubscriptionController::class, 'update_expiry'])->name('subscription.update.expiry');
            Route::put('change-bill-date', [SubscriptionController::class, 'change_bill_date'])->name('subscription.change.bill.date');
            Route::get('start-immediate-plan/{id?}', [SubscriptionController::class, 'start_immediate_plan']);
            Route::put('update-current-plan', [SubscriptionController::class, 'update_current_plan'])->name('subscription.update-current-plan');
            Route::get('generate-bill/{id?}', [SubscriptionController::class, 'generate_bill']);
            Route::get('transactions', [SubscriptionController::class, 'transactions_log']);
            Route::get('transactions/list', [SubscriptionController::class, 'subscription_transaction_list']);

        });
        Route::resource('subscriptions', SubscriptionController::class);

        // Addons
        Route::group(['prefix' => 'addons'], static function () {
            Route::put('restore/{id}', [AddonController::class, 'restore'])->name('addons.restore');
            Route::delete('trash/{id}', [AddonController::class, 'trash'])->name('addons.trash');
            Route::put('status/{id}', [AddonController::class, 'status'])->name('addons.status');
            Route::get('plan', [AddonController::class, 'plan'])->name('addons.plan');
            Route::get('subscribe/{id}', [AddonController::class, 'subscribe'])->name('addons.subscribe');
            Route::get('discontinue/{id}', [AddonController::class, 'discontinue'])->name('addons.discontinue');

        });
        Route::resource('addons', AddonController::class);

        // Expense Category
        Route::group(['prefix' => 'expense-category'], static function () {
            Route::put('restore/{id}', [ExpenseCategoryController::class, 'restore'])->name('expense-category.restore');
            Route::delete('trash/{id}', [ExpenseCategoryController::class, 'trash'])->name('expense-category.trash');

        });
        Route::resource('expense-category', ExpenseCategoryController::class);

        // Expense
        Route::resource('expense', ExpenseController::class);
        // Payroll
        Route::resource('payroll', PayrollController::class)->only(['index', 'store', 'show']);

        // Leave
        Route::group(['prefix' => 'leave'], static function () {
            Route::get('request', [LeaveController::class, 'leave_request'])->name('leave.request');
            Route::get('request/show', [LeaveController::class, 'leave_request_show'])->name('leave.request.show');
            Route::put('status/update', [LeaveController::class, 'leave_status_update'])->name('leave.status.update');
            Route::get('filter', [LeaveController::class, 'filter_leave']);
            Route::get('report', [LeaveController::class, 'report'])->name('leave.report');
            Route::get('detail', [LeaveController::class, 'detail'])->name('leave.detail');

        });

        Route::resource('leave', LeaveController::class);
        Route::resource('leave-master', LeaveMasterController::class);

        // Semester
        Route::group(['prefix' => 'semester'], static function () {
            Route::put('restore/{id}', [SemesterController::class, 'restore'])->name('semester.restore');
            Route::delete('trash/{id}', [SemesterController::class, 'trash'])->name('semester.trash');
        });
        Route::resource('semester', SemesterController::class);

        Route::group(['prefix' => 'stream'], static function () {
            Route::put('restore/{id}', [StreamController::class, 'restore'])->name('stream.restore');
            Route::delete('trash/{id}', [StreamController::class, 'trash'])->name('stream.trash');
        });
        Route::resource('stream', StreamController::class);


        Route::group(['prefix' => 'shift'], static function () {
            Route::put('restore/{id}', [ShiftController::class, 'restore'])->name('shift.restore');
            Route::delete('trash/{id}', [ShiftController::class, 'trash'])->name('shift.trash');
        });
        Route::resource('shift', ShiftController::class);

        Route::resource('faqs', FaqController::class);

        Route::get('users/status', [UserController::class, 'status']);
        Route::get('users/show', [UserController::class, 'show'])->name('users.show');
        Route::post('users/status', [UserController::class, 'status_change']);

        Route::group(['prefix' => 'related-data'], static function () {
            Route::get('/{table}/{id}', [Controller::class, 'relatedDataIndex'])->name('related-data.index');
            Route::delete('delete/{table}/{id}', [Controller::class, 'relatedDataDestroy'])->name('related-data.trash');
        });

        Route::resource('guidances', GuidanceController::class);
    });
});

// webhooks
Route::post('webhook/razorpay', [WebhookController::class, 'razorpay']);
Route::post('webhook/stripe', [WebhookController::class, 'stripe']);

Route::post('subscription/webhook/stripe', [SubscriptionWebhookController::class, 'stripe']);


Route::get('page/privacy-policy', static function () {
    $cache = app(CachingService::class);
    echo htmlspecialchars_decode($cache->getSystemSettings('privacy_policy'));
})->name('public.privacy-policy');

Route::get('page/terms-conditions', static function () {
    $cache = app(CachingService::class);
    echo htmlspecialchars_decode($cache->getSystemSettings('terms_condition'));
})->name('public.terms-conditions');

Route::get('school-terms-condition', static function () {
    $cache = app(CachingService::class);
    echo htmlspecialchars_decode($cache->getSystemSettings('school_terms_condition'));
});
Route::get('clear', static function () {
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('optimize:clear');
    return redirect()->back();
});

Route::get('storage-link', static function () {
    try {
        Artisan::call('storage:link');
        echo "storage link created";
    } catch (Exception) {
        echo "Storage Link already exists";
    }
    return redirect()->back();
});


Route::get('migrate', static function () {
    Artisan::call('migrate');
//    return redirect()->back();
    echo "Done";
    return false;
});

Route::get('migrate-rollback', static function () {
    Artisan::call('migrate:rollback');
    echo "Done";
    return false;
});
Route::get('installation-seeder', static function () {
    Artisan::call('db:seed --class=InstallationSeeder');
    echo "Done";
    return false;
});
//
Route::get('dummy-seeder', static function () {
//    Artisan::call('db:seed --class=DummyDataSeeder');
    Artisan::call('db:seed');
    echo "Done";
    return false;
});

//Route::get('test', static function () {
//    // Replace 'A' with the table you are interested in
//    $table = 'mediums';
//    $id = 1;
//    $databaseName = config('database.connections.mysql.database');
//
//    $relatedTables = DB::select("SELECT TABLE_NAME,COLUMN_NAME
//            FROM information_schema.KEY_COLUMN_USAGE
//            WHERE REFERENCED_TABLE_NAME = ? AND TABLE_SCHEMA = ?", [$table, $databaseName]);
//    $data = [];
//
//    //    dd($relatedTables);
//
//    foreach ($relatedTables as $relatedTable) {
//
//        $getTableSchema = DB::select("SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
//            FROM information_schema.KEY_COLUMN_USAGE
//            WHERE TABLE_NAME = ? AND TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME IS NOT NULL", [$relatedTable->TABLE_NAME, $databaseName]);
//
//        //        dd($getTableSchema);
//        DB::enableQueryLog();
//        $q = DB::table($relatedTable->TABLE_NAME)->where($relatedTable->TABLE_NAME . "." . $relatedTable->COLUMN_NAME, $id);
//
//        //Build Join query for all the foreign key using the Table Schema
//        foreach ($getTableSchema as $foreignKey) {
//            if ($foreignKey->REFERENCED_TABLE_NAME != 'schools') {
//                $q->join($foreignKey->REFERENCED_TABLE_NAME, $foreignKey->REFERENCED_TABLE_NAME . "." . $foreignKey->REFERENCED_COLUMN_NAME, '=', $relatedTable->TABLE_NAME . "." . $foreignKey->COLUMN_NAME);
//            }
//        }
//
//        //        $q = $this->buildQueryForSpecificTable($q, $relatedTable->TABLE_NAME);
//
//        $data[$relatedTable->TABLE_NAME] = $q->select('*')->get()->toArray();
//        print_r($data[$relatedTable->TABLE_NAME]);
//        //        dd(DB::getQueryLog());
//        //            $data[$relatedTable->TABLE_NAME] = DB::table($relatedTable->TABLE_NAME)->where($relatedTable->COLUMN_NAME, $id)->get()->toArray();
//    }
//
//    //    dd($data);
//    //
//    //    $data = [];
//    //
//    //    dd($referencingTables);
//    //    foreach ($referencingTables as $table) {
//    //        $data[$table->REFERENCED_TABLE_NAME] = DB::table($table->TABLE_NAME)->where($table->REFERENCED_COLUMN_NAME, $id)->get()->toArray();
//    //    }
//
//    // Now $referencingTables contains an array of tables that reference 'A'
//});

Route::get('/js/lang.js', static function () {
    //    https://medium.com/@serhii.matrunchyk/using-laravel-localization-with-javascript-and-vuejs-23064d0c210e
    header('Content-Type: text/javascript');
    $labels = \Illuminate\Support\Facades\Cache::remember('lang.js', 3600, static function () {
        $lang = app()->getLocale();
        $files = resource_path('lang/' . $lang . '.json');
        return File::get($files);
    });
    echo('window.trans = ' . $labels);
    exit();
})->name('assets.lang');

Route::get('test-code', static function () {

});

Route::get('cache-flush', static function () {
    \Illuminate\Support\Facades\Cache::flush();
    return redirect()->back();
});


Route::get('demo-tokens', static function () {
    echo "<pre>";

    $guardian = User::where('email', 'guardian@gmail.com')->first();
    if (!empty($guardian)) {
        echo "Demo Guardian Token<br>";
        echo Cache::rememberForever('demoGuardianToken', static function () use ($guardian) {
            return $guardian->createToken($guardian->first_name)->plainTextToken;
        });
    }


    $student = User::where('email', 'student@gmail.com')->first();
    if (!empty($student)) {
        echo "<br><br>Demo Student Token<br>";
        echo Cache::rememberForever('demoStudentToken', static function () use ($student) {
            return $student->createToken($student->first_name)->plainTextToken;
        });
    }
});
