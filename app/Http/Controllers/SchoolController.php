<?php

namespace App\Http\Controllers;

use App\Repositories\Package\PackageInterface;
use App\Repositories\School\SchoolInterface;
use App\Repositories\Subscription\SubscriptionInterface;
use App\Repositories\SubscriptionFeature\SubscriptionFeatureInterface;
use App\Repositories\User\UserInterface;
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\ResponseService;
use App\Services\SchoolDataService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;
use TypeError;

class SchoolController extends Controller {

    // Initializing the schools Repository
    private SchoolInterface $schoolsRepository;
    private UserInterface $userRepository;
    private PackageInterface $package;
    private SubscriptionInterface $subscription;
    private SubscriptionFeatureInterface $subscriptionFeature;
    private CachingService $cache;

    public function __construct(SchoolInterface $school, UserInterface $user, PackageInterface $package, SubscriptionInterface $subscription, SubscriptionFeatureInterface $subscriptionFeature, CachingService $cache) {
        $this->schoolsRepository = $school;
        $this->userRepository = $user;
        $this->package = $package;
        $this->subscription = $subscription;
        $this->subscriptionFeature = $subscriptionFeature;
        $this->cache = $cache;
    }


    public function index() {
        ResponseService::noPermissionThenRedirect('schools-list');
        $packages = $this->package->builder()->orderBy('rank')->pluck('name','id')->toArray();

        return view('schools.index', compact('packages'));
    }

    public function store(Request $request) {
        ResponseService::noAnyPermissionThenRedirect(['schools-create']);
        $validator = Validator::make($request->all(), [
            'school_name'          => 'required',
            'school_support_email' => 'required|unique:schools,support_email',
            'school_support_phone' => 'required|numeric|digits_between:10,16',
            'school_tagline'       => 'required',
            'school_address'       => 'required',
            'school_image'         => 'required|mimes:jpg,jpeg,png,svg,svg+xml',
            'admin_first_name'     => 'required',
            'admin_last_name'      => 'required',
            'admin_contact'        => 'required|digits_between:10,16',
            'admin_email'          => 'required|unique:users,email',
            'admin_image'          => 'required|mimes:jpg,jpeg,png,svg,svg+xml',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            DB::beginTransaction();

            $school_data = array(
                'name'          => $request->school_name,
                'address'       => $request->school_address,
                'support_email' => $request->school_support_email,
                'support_phone' => $request->school_support_phone,
                'tagline'       => $request->school_tagline,
                'logo'          => $request->file('school_image')
            );
            // Call store function of Schools Repository
            $schoolData = $this->schoolsRepository->create($school_data);

            $admin_data = array(
                'first_name' => $request->admin_first_name,
                'last_name'  => $request->admin_last_name,
                'mobile'     => $request->admin_contact,
                'email'      => $request->admin_email,
                'password'   => Hash::make($request->admin_contact),
                'school_id'  => $schoolData->id,
                'image'      => $request->file('admin_image')
            );

            //Call store function of User Repository and get the admin data
            $user = $this->userRepository->create($admin_data);
            $user->assignRole('School Admin');

            // Update Admin id to School Data
            $schoolData = $this->schoolsRepository->update($schoolData->id, ['admin_id' => $user->id]);

            $schoolService = app(SchoolDataService::class);
            // Add Pre School Settings By Default
            $schoolService->preSettingsSetup($schoolData);


            // Assign package
            if ($request->assign_package) {
                $systemSettings = $this->cache->getSystemSettings();
                $package = $this->package->builder()->with('package_feature')->whereId($request->assign_package)->first();
                $end_date = '';
                // Check selected plan is trail or regular plan
                if ($package->is_trial) {
                    $end_date = Carbon::now()->addDays(($systemSettings['trial_days']))->format('Y-m-d');
                } else {
                    $end_date = Carbon::now()->addDays(($systemSettings['billing_cycle_in_days'] - 1))->format('Y-m-d');
                }
                $subscription_data = [
                    'school_id' => $schoolData->id,
                    'package_id' => $package->id,
                    'name' => $package->name,
                    'student_charge' => $package->student_charge,
                    'staff_charge' => $package->staff_charge,
                    'start_date' => Carbon::now()->format('Y-m-d'),
                    'end_date' => $end_date,
                    'billing_cycle' => $systemSettings['billing_cycle_in_days'],
                ];

                $subscription = $this->subscription->create($subscription_data);

                $subscription_features = array();
                foreach ($package->package_feature as $key => $feature) {
                    $subscription_features[] = [
                        'subscription_id' => $subscription->id,
                        'feature_id' => $feature->feature_id
                    ];
                }
                $this->subscriptionFeature->upsert($subscription_features,['subscription_id','feature_id'],['subscription_id','feature_id']);

                $this->cache->removeSchoolCache(config('constants.CACHE.SCHOOL.FEATURES'));

            }

            DB::commit();
            $data = [
                'subject'     => 'Welcome to ' . $request->school_name,
                'name'        => $request->admin_first_name,
                'email'       => $request->admin_email,
                'password'    => $request->admin_contact,
                'school_name' => $request->school_name
            ];

            Mail::send('schools.email', $data, static function ($message) use ($data) {
                $message->to($data['email'])->subject($data['subject']);
            });
            ResponseService::successResponse('Data Stored Successfully');

        } catch (Throwable $e) {
            if (Str::contains($e->getMessage(), ['Failed', 'Mail', 'Mailer', 'MailManager'])) {
                ResponseService::warningResponse("School Registered successfully. But Email not sent.");
            } else {
                DB::rollBack();
                ResponseService::logErrorResponse($e, "School Controller -> Store method");
                ResponseService::errorResponse();
            }

        }
    }

    public function show() {
        ResponseService::noPermissionThenRedirect('schools-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'ASC');
        $search = request('search');
        $package_id = request('package_id');
        $showDeleted = request('show_deleted');
        $today_date = Carbon::now()->format('Y-m-d');

        $sql = $this->schoolsRepository->builder()->with('user:id,first_name,last_name,email,image,mobile')->with(['subscription' => function($q) use($today_date){
            $q->whereDate('start_date','<=',$today_date)->whereDate('end_date','>=',$today_date);
        }])->with('subscription.package')
            //search query
            ->where(function ($query) use ($search) {
                $query->when($search, function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('name', 'LIKE', "%$search%")
                            ->orWhere('support_email', 'LIKE', "%$search%")
                            ->orWhere('support_phone', 'LIKE', "%$search%")
                            ->orWhere('tagline', 'LIKE', "%$search%")
                            ->orWhere('address', 'LIKE', "%$search%")
                            ->orWhereHas('user', function ($query) use ($search) {
                                $query->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", "%$search%");
                            });
                    });
                    $query->where(function ($query) use ($search) {
                        $query->where('name', 'LIKE', "%$search%")
                            ->orWhere('support_email', 'LIKE', "%$search%")
                            ->orWhere('support_phone', 'LIKE', "%$search%")
                            ->orWhere('tagline', 'LIKE', "%$search%")
                            ->orWhere('address', 'LIKE', "%$search%")
                            ->orWhereHas('user', function ($query) use ($search) {
                                $query->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", "%$search%");
                            });
                    });
                });
            })->when(!empty($showDeleted), function ($query) {
                $query->onlyTrashed();
            });

        if ($package_id) {
            $sql->whereHas('subscription',function($q) use($package_id, $today_date) {
                $q->where('package_id',$package_id)->whereDate('start_date','<=',$today_date)->whereDate('end_date','>=',$today_date);
            });
        }


        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;

        foreach ($res as $row) {
            if ($showDeleted) {
                //Show Restore and Hard Delete Buttons
                $operate = BootstrapTableService::restoreButton(route('schools.restore', $row->id));
                $operate .= BootstrapTableService::trashButton(route('schools.trash', $row->id));
            } else {
                $operate = BootstrapTableService::button('fa fa-user', '#', ['update-admin-data', 'btn-gradient-info'], ['title' => trans("change_admin"), 'data-toggle' => "modal", 'data-target' => "#editAdminModal"]);

                if ($row->status == 0) {
                    $operate .= BootstrapTableService::button('fa fa-check', '#', ['change-school-status', 'btn-gradient-success'], ['title' => trans("activate_school"), 'data-id' => $row->id]);
                } else {
                    $operate .= BootstrapTableService::button('fa fa-times', '#', ['change-school-status', 'btn-gradient-warning'], ['title' => trans("deactivate_school"), 'data-id' => $row->id]);
                }

                $operate .= BootstrapTableService::editButton(route('schools.update', $row->id));
                $operate .= BootstrapTableService::deleteButton(route('schools.destroy', $row->id));
            }


            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            if (count($row->subscription)) {
                $package = $row->subscription()->whereDate('start_date','<=',$today_date)->whereDate('end_date','>=',$today_date)->first();
                if ($package) {
                    $tempRow['active_plan'] = $package->name;
                }
            } else {
                $tempRow['active_plan'] = '-';
            }
            
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function update(Request $request, $id) {
        ResponseService::noPermissionThenSendJson(['schools-edit']);
        $validator = Validator::make($request->all(), [
            'edit_school_name'          => 'required',
            'edit_school_support_email' => 'required|unique:schools,support_email,' . $id,
            'edit_school_support_phone' => 'required|numeric|digits_between:10,16',
            'edit_school_tagline'       => 'required',
            'edit_school_address'       => 'required',
            'edit_school_image'         => 'nullable|mimes:jpg,jpeg,png,svg,svg+xml',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $school_data = array(
                'name'          => $request->edit_school_name,
                'address'       => $request->edit_school_address,
                'support_email' => $request->edit_school_support_email,
                'support_phone' => $request->edit_school_support_phone,
                'tagline'       => $request->edit_school_tagline,
            );

            if ($request->hasFile('edit_school_image')) {
                $school_data['logo'] = $request->file('edit_school_image');
            }

            $this->schoolsRepository->update($request->edit_id, $school_data); // Call update function of Schools Repository
            ResponseService::successResponse('Data Updated Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "School Controller -> Update method");
            ResponseService::errorResponse();
        }
    }

    public function destroy($id) {
        ResponseService::noPermissionThenSendJson('schools-delete');
        try {
            $this->schoolsRepository->deleteById($id);
            ResponseService::successResponse('Data Deleted Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "School Controller -> Delete method");
            ResponseService::errorResponse();
        }
    }

    public function restore(int $id) {
        ResponseService::noPermissionThenSendJson('schools-delete');
        try {
            $this->schoolsRepository->findOnlyTrashedById($id)->restore();
            ResponseService::successResponse("Data Restored Successfully");
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function trash($id) {
        ResponseService::noPermissionThenSendJson('schools-delete');
        try {
            $this->schoolsRepository->findOnlyTrashedById($id)->forceDelete();
            ResponseService::successResponse("Data Deleted Permanently");
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function adminSearch(Request $request) {
        $adminData = $this->userRepository->getTrashedAdminData($request->email);
        if (!empty($adminData)) {
            $response = ['error' => false, 'data' => $adminData];
        } else {
            $response = ['error' => true, 'message' => trans('no_data_found')];
        }
        return response()->json($response);
    }

    public function updateAdmin(Request $request) {
        ResponseService::noAnyPermissionThenRedirect(['schools-edit']);
        $validator = Validator::make($request->all(), [
            "edit_id"               => 'required',
            "edit_admin_email"      => 'required',
            "edit_admin_first_name" => 'required',
            "edit_admin_last_name"  => 'required',
            "edit_admin_contact"    => 'required|digits_between:10,16',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            DB::beginTransaction();

            // If Email is not the ID then Check the following requirements
            if (!is_numeric($request->edit_admin_email)) {
                $validator = Validator::make($request->all(), [
                    "edit_admin_email" => 'required|email|unique:users,email',
                ],
                    [
                        "edit_admin_email.required" => trans('email_is_required'),
                        "edit_admin_email.email"    => trans('enter_valid_email'),
                        "edit_admin_email.unique"   => trans('email_already_in_use'),
                    ]);
                if ($validator->fails()) {
                    ResponseService::validationError($validator->errors()->first());
                }
            }

            $admin_data = array(
                'school_id'  => $request->edit_id,
                'email'      => $request->edit_admin_email,
                'first_name' => $request->edit_admin_first_name,
                'last_name'  => $request->edit_admin_last_name,
                'contact'    => $request->edit_admin_contact,
            );
            $this->schoolsRepository->updateSchoolAdmin($admin_data, $request->edit_admin_image); // Call updateSchoolAdmin function of Schools Repository
            DB::commit();
            ResponseService::successResponse('Data Updated Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "School Controller -> Update Admin method");
            ResponseService::errorResponse();
        }
    }

    public function changeStatus($id) {
        ResponseService::noAnyPermissionThenRedirect(['schools-edit']);
        try {
            DB::beginTransaction();
            $school = $this->schoolsRepository->findById($id);
            $status = ['status' => $school->status == 0 ? 1 : 0];
            $this->schoolsRepository->update($id, $status);
            DB::commit();
            ResponseService::successResponse('Data updated successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "School Controller -> Change Status");
            ResponseService::errorResponse();
        }
    }

    public function searchAdmin(Request $request) {
        ResponseService::noAnyPermissionThenRedirect(['schools-create', 'schools-edit']);
        $parent = $this->userRepository->builder()->role('School Admin')->where(function ($query) use ($request) {
            $query->where('email', 'like', '%' . $request->email . '%')
                ->orWhere('first_name', 'like', '%' . $request->email . '%')
                ->orWhere('last_name', 'like', '%' . $request->email . '%');
        })->get();

        if (!empty($parent)) {
            $response = [
                'error' => false,
                'data'  => $parent
            ];
        } else {
            $response = [
                'error'   => true,
                'message' => trans('no_data_found')
            ];
        }
        return response()->json($response);
    }

    public function registration(Request $request) {
        Validator::make($request->all(), [
            'school_name'          => 'required',
            'school_support_email' => 'required',
            'school_support_phone' => 'required|numeric|digits_between:10,16',
            'school_tagline'       => 'required',
            'school_address'       => 'required',
            'admin_first_name'     => 'required',
            'admin_last_name'      => 'required',
            'admin_contact'        => 'required|numeric|digits_between:10,16',
            'admin_email'          => 'required',
        ]);

        $school = $this->schoolsRepository->builder()->where('support_email', $request->school_support_email)->first();
        $user = $this->userRepository->builder()->where('email', $request->admin_email)->first();
        if ($school || $user) {
            return redirect('/login')->with('error', 'School or User email already exists');
        }

        try {
            DB::beginTransaction();

            $school_data = array(
                'name'          => $request->school_name,
                'address'       => $request->school_address,
                'support_email' => $request->school_support_email,
                'support_phone' => $request->school_support_phone,
                'tagline'       => $request->school_tagline,
                'logo'          => 'no_image_available.jpg',
                'status'        => 1
            );
            // Call store function of Schools Repository
            $schoolData = $this->schoolsRepository->create($school_data);

            $admin_data = array(
                'first_name' => $request->admin_first_name,
                'last_name'  => $request->admin_last_name,
                'mobile'     => $request->admin_contact,
                'email'      => $request->admin_email,
                'password'   => Hash::make($request->admin_contact),
                'school_id'  => $schoolData->id,
                'image'      => 'dummy_logo.jpg'
            );

            //Call store function of User Repository and get the admin data
            $user = $this->userRepository->create($admin_data);
            $user->assignRole('School Admin');

            // Update Admin id to School Data
            $schoolData = $this->schoolsRepository->update($schoolData->id, ['admin_id' => $user->id]);

            $schoolService = app(SchoolDataService::class);
            // Add Pre School Settings By Default
            $schoolService->preSettingsSetup($schoolData);
            DB::commit();
            return redirect('/login')->with('success', 'School Registration Successfully');

        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "School Controller -> Registration method");
            ResponseService::errorResponse();
        }
    }

}
