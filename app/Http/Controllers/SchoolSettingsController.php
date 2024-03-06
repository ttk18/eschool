<?php

namespace App\Http\Controllers;

use App\Repositories\ClassSection\ClassSectionInterface;
use App\Repositories\PaymentConfiguration\PaymentConfigurationInterface;
use App\Repositories\SchoolSetting\SchoolSettingInterface;
use App\Repositories\Student\StudentInterface;
use App\Services\CachingService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;


class SchoolSettingsController extends Controller {
    // Initializing the Settings Repository
    private SchoolSettingInterface $schoolSettings;
    private CachingService $cache;
    private ClassSectionInterface $classSection;
    private StudentInterface $student;

    public function __construct(SchoolSettingInterface $schoolSettings, CachingService $cachingService, ClassSectionInterface $classSection, StudentInterface $student, PaymentConfigurationInterface $paymentConfiguration) {
        $this->schoolSettings = $schoolSettings;
        $this->cache = $cachingService;
        $this->classSection = $classSection;
        $this->student = $student;
        $this->paymentConfiguration = $paymentConfiguration;
    }

    public function index() {
        ResponseService::noPermissionThenRedirect('school-setting-manage');
        $settings = $this->cache->getSchoolSettings();
        return view('school-settings.general-settings', compact('settings'));
    }


    public function store(Request $request) {
        ResponseService::noPermissionThenRedirect('school-setting-manage');
        $settings = [
            'school_name'             => 'required|max:255|regex:/^[a-zA-Z0-9 ]+$/',
            'school_email'            => 'required|email',
            'school_phone'            => 'required',
            'school_address'          => 'required',
            'favicon'                 => 'nullable|image|max:2048',
            'horizontal_logo'         => 'nullable|image|max:2048',
            'vertical_logo'           => 'nullable|image|max:2048',
            'roll_number_sort_column' => 'nullable|in:first_name,last_name',
            'roll_number_sort_order'  => 'nullable|in:asc,desc',
            'change_roll_number'      => 'nullable',
        ];
        $validator = Validator::make($request->all(), $settings);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $data = array();
            // dd(implode(',',$request->holiday_days));
            foreach ($settings as $key => $rule) {
                if ($key == 'horizontal_logo' || $key == 'vertical_logo' || $key == 'favicon') {
                    if ($request->hasFile($key)) {
                        // TODO : Remove the old files from server
                        $data[] = [
                            "name" => $key,
                            "data" => $request->file($key),
                            "type" => "file"
                        ];
                    }
                } else {
                    $data[] = [
                        "name" => $key,
                        "data" => $request->$key,
                        "type" => "string"
                    ];
                }
            }
            $this->schoolSettings->upsert($data, ["name"], ["data"]);
            $this->cache->removeSchoolCache(config('constants.CACHE.SCHOOL.SETTINGS'));

            if ($request->change_roll_number) {
                // Get Sort And Order
                $sort = $request->roll_number_sort_column;
                $order = $request->roll_number_sort_order;

                //Get Class Section's Data With Student Sorted By There Names
                $classSections = $this->classSection->builder()
                    ->with(['students' => function ($query) use ($sort, $order) {
                        $query->join('users', 'students.user_id', '=', 'users.id')
                            ->select('students.*', 'users.first_name', 'users.last_name')
                            ->orderBy('users.' . $sort, $order);
                    }])
                    ->get();

                // Loop towards Class Section Data And make Array To get Student's id and Count Roll Number
                $studentArray = array();
                foreach ($classSections as $classSection) {
                    if (isset($classSection->students) && $classSection->students->isNotEmpty()) {
                        foreach ($classSection->students as $key => $student) {
                            $studentArray[] = array(
                                'id'               => $student->id,
                                'class_section_id' => $student->class_section_id,
                                'roll_number'      => (int)$key + 1
                            );
                        }
                    }
                }

                // Update Roll Number Of Students
                $this->student->upsert($studentArray, ['id'], ['roll_number']);

            }
            DB::commit();
            ResponseService::successResponse('Data Updated Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "SchoolSettings Controller -> Store method");
            ResponseService::errorResponse();
        }
    }

    public function onlineExamIndex() {
        ResponseService::noPermissionThenRedirect('school-setting-manage');
        $onlineExamTermsConditions = $this->schoolSettings->getSpecificData('online_exam_terms_condition');
        $name = 'online_exam_terms_condition';
        return response(view('online_exam.terms_conditions', compact('onlineExamTermsConditions', 'name')));
    }

    public function onlineExamStore(Request $request) {
        ResponseService::noPermissionThenRedirect('school-setting-manage');
        try {
            DB::beginTransaction();
            $this->schoolSettings->updateOrCreate(["name" => $request->name], ["data" => $request->data, "type" => "string"]);
            DB::commit();
            $this->cache->removeSchoolCache(config('constants.CACHE.SCHOOL.SETTINGS'));
            ResponseService::successResponse('Data Updated Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "SchoolSettings Controller -> storeOnlineExamTermsCondition method");
            ResponseService::errorResponse();
        }
    }
}
