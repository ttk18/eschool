<?php

namespace App\Http\Controllers;

use App\Models\FeesAdvance;
use App\Repositories\ClassSchool\ClassSchoolInterface;
use App\Repositories\CompulsoryFee\CompulsoryFeeInterface;
use App\Repositories\Fees\FeesInterface;
use App\Repositories\FeesClassType\FeesClassTypeInterface;
use App\Repositories\FeesInstallment\FeesInstallmentInterface;
use App\Repositories\FeesPaid\FeesPaidInterface;
use App\Repositories\FeesType\FeesTypeInterface;
use App\Repositories\Medium\MediumInterface;
use App\Repositories\OptionalFee\OptionalFeeInterface;
use App\Repositories\PaymentConfiguration\PaymentConfigurationInterface;
use App\Repositories\PaymentTransaction\PaymentTransactionInterface;
use App\Repositories\SchoolSetting\SchoolSettingInterface;
use App\Repositories\SessionYear\SessionYearInterface;
use App\Repositories\Student\StudentInterface;
use App\Repositories\SystemSetting\SystemSettingInterface;
use App\Repositories\User\UserInterface;
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\ResponseService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class FeesController extends Controller {
    private FeesInterface $fees;
    private SessionYearInterface $sessionYear;
    private FeesInstallmentInterface $feesInstallment;
    private SchoolSettingInterface $schoolSettings;
    private MediumInterface $medium;
    private FeesTypeInterface $feesType;
    private ClassSchoolInterface $classes;
    private FeesClassTypeInterface $feesClassType;
    private UserInterface $user;
    private FeesPaidInterface $feesPaid;
    private CompulsoryFeeInterface $compulsoryFee;
    private OptionalFeeInterface $optionalFee;
    private CachingService $cache;
    private PaymentConfigurationInterface $paymentConfigurations;
    private ClassSchoolInterface $class;
    private StudentInterface $student;
    private PaymentTransactionInterface $paymentTransaction;
    private SystemSettingInterface $systemSetting;

    public function __construct(FeesInterface $fees, SessionYearInterface $sessionYear, FeesInstallmentInterface $feesInstallment, SchoolSettingInterface $schoolSettings, MediumInterface $medium, FeesTypeInterface $feesType, ClassSchoolInterface $classes, FeesClassTypeInterface $feesClassType, UserInterface $user, FeesPaidInterface $feesPaid, CompulsoryFeeInterface $compulsoryFee, OptionalFeeInterface $optionalFee, CachingService $cache, PaymentConfigurationInterface $paymentConfigurations, ClassSchoolInterface $classSchool, StudentInterface $student, PaymentTransactionInterface $paymentTransaction, SystemSettingInterface $systemSetting) {
        $this->fees = $fees;
        $this->sessionYear = $sessionYear;
        $this->feesInstallment = $feesInstallment;
        $this->schoolSettings = $schoolSettings;
        $this->medium = $medium;
        $this->feesType = $feesType;
        $this->classes = $classes;
        $this->feesClassType = $feesClassType;
        $this->user = $user;
        $this->feesPaid = $feesPaid;
        $this->compulsoryFee = $compulsoryFee;
        $this->optionalFee = $optionalFee;
        $this->cache = $cache;
        $this->paymentConfigurations = $paymentConfigurations;
        $this->class = $classSchool;
        $this->student = $student;
        $this->paymentTransaction = $paymentTransaction;
        $this->systemSetting = $systemSetting;
    }

    /* START : Fees Module */
    public function index() {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-list');
        $classes = $this->class->all(['*'], ['stream', 'medium', 'stream']);
        $feesTypeData = $this->feesType->all();
        return view('fees.index', compact('classes', 'feesTypeData'));
    }

    public function store(Request $request) {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-create');
        $request->validate([
            'include_fee_installments'        => 'required|boolean',
            'due_date'                        => 'required|date',
            'due_charges'                     => 'required|numeric',
            'class_id'                        => 'required|array',
            'class_id.*'                      => 'required|numeric',
            'fees_type'                       => 'required|array',
            'fees_type.*'                     => 'required|array',
            'fees_type.*.fees_type_id'        => 'required|numeric',
            'fees_type.*.amount'              => 'required|numeric',
            'fees_type.*.optional'            => 'required|boolean',
            'fees_installments'               => 'required_if:include_fee_installments,1|array',
            'fees_installments.*.name'        => 'required',
            'fees_installments.*.due_date'    => 'required|date',
            'fees_installments.*.due_charges' => 'required|numeric'
        ]);
        try {
            DB::beginTransaction();
            $sessionYear = $this->cache->getDefaultSessionYear();
            $classes = $this->class->builder()->whereIn("id", $request->class_id)->with('stream', 'medium')->get();

            foreach ($request->class_id as $class_id) {
                $class = $classes->first(function ($data) use ($class_id) {
                    return $data->id == $class_id;
                });
                $name = (!empty($request->name)) ? $request->name . " - " : "";
                $fees = $this->fees->create([
                    'name'            => $name . $class->full_name,
                    'due_date'        => $request->due_date,
                    'due_charges'     => $request->due_charges,
                    'class_id'        => $class_id,
                    'session_year_id' => $sessionYear->id
                ]);
                $feeClassType = [];
                foreach ($request->fees_type as $data) {
                    $feeClassType[] = array(
                        "fees_id"      => $fees->id,
                        "class_id"     => $class_id,
                        "fees_type_id" => $data['fees_type_id'],
                        "amount"       => $data['amount'],
                        "optional"     => $data['optional'],
                    );
                }

                if (count($feeClassType) > 0) {
                    $this->feesClassType->upsert($feeClassType, ['class_id', 'fees_type_id'], ['amount', 'optional']);
                }

                if ($request->include_fee_installments && count($request->fees_installments)) {
                    $installmentData = array();
                    foreach ($request->fees_installments as $data) {
                        $data = (object)$data;
                        $installmentData[] = array(
                            'name'            => $data->name,
                            'due_date'        => date('Y-m-d', strtotime($data->due_date)),
                            'due_charges'     => $data->due_charges,
                            'fees_id'         => $fees->id,
                            'session_year_id' => $sessionYear->id,
                        );
                    }
                    $this->feesInstallment->createBulk($installmentData);
                }
            }

            DB::commit();
            ResponseService::successResponse('Data Stored Successfully');
        } catch (Throwable $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e, "FeesController -> Store Method");
            ResponseService::errorResponse();
        }
    }

    public function show() {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');
        $showDeleted = request('show_deleted');

        $sql = $this->fees->builder()->with('installments', 'class:id,name,stream_id,medium_id', 'class.medium:id,name', 'class.stream:id,name', 'fees_class_type.fees_type:id,name')
            ->when($search, function ($query) use ($search) {
                $query->where('id', 'LIKE', "%$search%")
                    ->orwhere('name', 'LIKE', "%$search%")
                    ->orwhere('due_date', 'LIKE', "%$search%")
                    ->orwhere('due_charges', 'LIKE', "%$search%");
            })->when(!empty($showDeleted), function ($query) {
                $query->onlyTrashed();
            });

        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;
        foreach ($res as $row) {
            $operate = '';
            if ($showDeleted) {
                $operate .= BootstrapTableService::restoreButton(route('fees.restore', $row->id));
                $operate .= BootstrapTableService::trashButton(route('fees.trash', $row->id));
            } else {
                $operate .= BootstrapTableService::editButton(route('fees.edit', $row->id), false);
                $operate .= BootstrapTableService::deleteButton(route('fees.destroy', $row->id));
            }

            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $tempRow['compulsory_fees'] = number_format($row->fees_class_type->filter(function ($data) {
                return $data->optional == 0;
            })->sum('amount'), 2);
            $tempRow['total_fees'] = number_format($row->fees_class_type->sum('amount'), 2);
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function edit($id) {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-edit');
        $classes = $this->class->all(['*'], ['stream', 'medium', 'stream']);
        $feesTypeData = $this->feesType->all();
        $fees = $this->fees->builder()->with(['fees_class_type', 'installments', 'class.medium'])->withCount('fees_paid')->findOrFail($id);
        return view('fees.edit', compact('classes', 'feesTypeData', 'fees'));
    }

    public function update(Request $request, $id) {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-edit');

        $request->validate([
            'include_fee_installments'        => 'required|boolean',
            'due_date'                        => 'required|date',
            'due_charges'                     => 'required|numeric',
            'fees_type'                       => 'required|array',
            'fees_type.*'                     => 'required|array',
            'fees_type.*.fees_type_id'        => 'required|numeric',
            'fees_type.*.amount'              => 'required|numeric',
            'fees_type.*.optional'            => 'required|boolean',
            'fees_installments'               => 'nullable|array',
            'fees_installments.*.name'        => 'required',
            'fees_installments.*.due_date'    => 'required|date',
            'fees_installments.*.due_charges' => 'required|numeric'
        ]);
        try {
            DB::beginTransaction();
            $sessionYear = $this->cache->getDefaultSessionYear();

            // Fees Data Store
            $feesData = array(
                'name'        => $request->name,
                'due_date'    => $request->due_date,
                'due_charges' => $request->due_charges
            );
            $fees = $this->fees->update($id, $feesData);

            foreach ($request->fees_type as $data) {
                $feeClassType[] = array(
                    "id"           => $data['id'],
                    "fees_id"      => $fees->id,
                    "class_id"     => $fees->class_id,
                    "fees_type_id" => $data['fees_type_id'],
                    "amount"       => $data['amount'],
                    "optional"     => $data['optional'],
                );
            }

            if (isset($feeClassType)) {
                $this->feesClassType->upsert($feeClassType, ['id'], ['fees_type_id', 'amount', 'optional']);
            }

            if (!empty($request->fees_installments)) {
                $installmentData = array();
                foreach ($request->fees_installments as $data) {
                    $data = (object)$data;
                    $installmentData[] = array(
                        'id'              => $data->id,
                        'name'            => $data->name,
                        'due_date'        => date('Y-m-d', strtotime($data->due_date)),
                        'due_charges'     => $data->due_charges,
                        'fees_id'         => $fees->id,
                        'session_year_id' => $sessionYear->id
                    );
                }

                $this->feesInstallment->upsert($installmentData, ['id'], ['name', 'due_date', 'due_charges', 'fees_id', 'session_year_id']);
            }

            DB::commit();
            ResponseService::successRedirectResponse(route('fees.index'), 'Data Update Successfully');
        } catch (Throwable) {
            DB::rollback();
            ResponseService::errorRedirectResponse();
        }
    }

    public function destroy($id) {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenSendJson('fees-delete');
        try {
            DB::beginTransaction();
            $this->fees->deleteById($id);
            DB::commit();
            ResponseService::successResponse("Data Deleted Successfully");
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "FeesController -> Store Method");
            ResponseService::errorResponse();
        }
    }

    public function restore(int $id) {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-delete');
        try {
            $this->fees->findOnlyTrashedById($id)->restore();
            ResponseService::successResponse("Data Restored Successfully");
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function search(Request $request) {
        ResponseService::noFeatureThenRedirect('Fees Management');
        try {
            $data = $this->fees->builder()->where('session_year_id', $request->session_year_id)->get();
            ResponseService::successResponse("Data Restored Successfully", $data);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function trash($id) {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-delete');
        try {
            $this->fees->findOnlyTrashedById($id)->forceDelete();
            ResponseService::successResponse("Data Deleted Permanently");
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    /* END : Fees Module */

    public function deleteInstallment($id) {
        ResponseService::noFeatureThenRedirect('Fees Management');
        try {
            DB::beginTransaction();
            $this->feesInstallment->DeleteById($id);
            DB::commit();
            ResponseService::successResponse("Data Deleted Successfully");
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function deleteClassType($id) {
        ResponseService::noFeatureThenRedirect('Fees Management');
        try {
            DB::beginTransaction();
            $this->feesClassType->DeleteById($id);
            DB::commit();
            ResponseService::successResponse("Data Deleted Successfully");
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function removeOptionalFees($id) {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-paid');
        try {
            DB::beginTransaction();

            // Get Fees Paid ID and Amount of Fees Transaction Table
            $optionalFeeData = $this->optionalFee->findById($id);
            $feesPaidId = $optionalFeeData->fees_paid_id;
            $optionalFeeAmount = $optionalFeeData->amount;

            $this->optionalFee->permanentlyDeleteById($id); // Permanently Delete Optional Fees Data

            // Check Fees Transactions Entry
            $feesPaidDataQuery = $this->feesPaid->builder()->where('id', $feesPaidId);
            if ($feesPaidDataQuery->count()) {
                // Get Fees Paid Data
                $feesPaidAmount = $feesPaidDataQuery->first()->amount; // Get Fees Paid Amount
                $finalAmount = $feesPaidAmount - $optionalFeeAmount; // Calculate Final Amount
                if ($finalAmount > 0) {
                    $this->feesPaid->update($feesPaidId, ['amount' => $finalAmount]); // Update Fees Paid Data with Final Amount
                } else {
                    $this->feesPaid->permanentlyDeleteById($feesPaidId);
                }
            } else {
                $this->feesPaid->permanentlyDeleteById($feesPaidId);
            }

            DB::commit();
            ResponseService::successResponse('Data Deleted Successfully');
        } catch (Throwable $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function removeInstallmentFees($compulsoryFeesPaidID) {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-paid');
        try {
            DB::beginTransaction();

            // Get Fees Paid ID and Amount of Fees Transaction Table
            $installmentFeeTransaction = $this->compulsoryFee->findById($compulsoryFeesPaidID);
            $feesPaidId = $installmentFeeTransaction->fees_paid_id;
            $feesTransactionAmount = $installmentFeeTransaction->amount;

            $this->compulsoryFee->permanentlyDeleteById($compulsoryFeesPaidID); // Permanently Delete Fees Transaction Data

            // Check Fees Transactions Entry
            $feesPaidDataQuery = $this->feesPaid->builder()->where('id', $feesPaidId);
            if ($feesPaidDataQuery->count()) {
                // Get Fees Paid Data
                $feesPaidAmount = $feesPaidDataQuery->first()->amount; // Get Fees Paid Amount
                $finalAmount = $feesPaidAmount - $feesTransactionAmount; // Calculate Final Amount
                if ($finalAmount > 0) {
                    $this->feesPaid->update($feesPaidId, ['amount' => $finalAmount, 'is_fully_paid' => 0]); // Update Fees Paid Data with Final Amount
                } else {
                    $this->feesPaid->permanentlyDeleteById($feesPaidId);
                }
            } else {
                $this->feesPaid->permanentlyDeleteById($feesPaidId);
            }

            DB::commit();
            ResponseService::successResponse('Data Deleted Successfully');
        } catch (Throwable $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function feesConfigIndex() {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-config');

        // List of the names to be fetched
        $names = array('currency_code', 'currency_symbol',);

        $settings = $this->schoolSettings->getBulkData($names); // Passing the array of names and gets the array of data
        $domain = request()->getSchemeAndHttpHost(); // Get Current Web Domain

        $stripeData = $this->paymentConfigurations->all()->where('payment_method', 'stripe')->first();
        return view('fees.fees_config', compact('settings', 'domain', 'stripeData'));
    }

    public function feesConfigUpdate(Request $request) {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-config');
        $request->validate(['stripe_status' => 'required', 'stripe_publishable_key' => 'required_if:stripe_status,1|nullable', 'stripe_secret_key' => 'required_if:stripe_status,1|nullable', 'stripe_webhook_secret' => 'required_if:stripe_status,1|nullable', 'stripe_webhook_url' => 'required_if:stripe_status,1|nullable', 'currency_code' => 'required|max:10', 'currency_symbol' => 'required|max:5',]);
        try {
            $this->paymentConfigurations->updateOrCreate(['payment_method' => strtolower('stripe')], ['api_key' => $request->stripe_publishable_key, 'secret_key' => $request->stripe_secret_key, 'webhook_secret_key' => $request->stripe_webhook_secret, 'status' => $request->stripe_status]);


            // Store Currency Code and Currency Symbol in School Settings
            $settings = array('currency_code', 'currency_symbol');

            $data = array();
            foreach ($settings as $row) {
                $data[] = [
                    "name" => $row,
                    "data" => $row == 'school_name' ? str_replace('"', '', $request->$row) : $request->$row, "type" => "string"
                ];
            }

            $this->schoolSettings->upsert($data, ["name"], ["data"]);
            Cache::flush();

            ResponseService::successResponse('Data Updated Successfully');

        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function feesTransactionsLogsIndex() {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-paid');
        $session_year_all = $this->sessionYear->all(['id', 'name', 'default']);
        $classes = $this->classes->builder()->orderByRaw('CONVERT(name, SIGNED) asc')->with('medium', 'stream', 'sections')->get();
        $mediums = $this->medium->builder()->orderBy('id', 'ASC')->get();
        return response(view('fees.fees_transaction_logs', compact('classes', 'mediums', 'session_year_all')));
    }

    public function feesTransactionsLogsList(Request $request) {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-paid');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');

        //Fetching Students Data on Basis of Class Section ID with Relation fees paid
        $sql = $this->paymentTransaction->builder()->doesntHave('subscription_bill')->with('user:id,first_name,last_name');

        if (!empty($request->search)) {
            $search = $request->search;
            $sql->where(function ($q) use ($search) {
                $q->where('id', 'LIKE', "%$search%")
                    ->orwhere('order_id', 'LIKE', "%$search%")->orwhere('payment_id', 'LIKE', "%$search%")
                    ->orwhere('payment_gateway', 'LIKE', "%$search%")->orwhere('amount', 'LIKE', "%$search%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->orwhere('first_name', 'LIKE', "%$search%")->orwhere('last_name', 'LIKE', "%$search%");
                    });
            });
        }

        if (!empty($request->payment_status)) {
            $sql->where('payment_status', $request->payment_status);
        }
        $total = $sql->count();
        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;
        foreach ($res as $row) {
            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    /* START : Fees Paid Module */
    public function feesPaidListIndex() {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-paid');

        // Fees Data With Few Selected Data
        $fees = $this->fees->builder()->select(['id', 'name'])->get();
        $classes = $this->classes->all(['*'], ['medium', 'sections']);
//        $session_year_all = $this->sessionYear->builder()->where('default', 1)->get();
        $session_year_all = $this->sessionYear->all(['id', 'name', 'default']);
        return response(view('fees.fees_paid', compact('fees', 'classes', 'session_year_all')));
    }

    public function feesPaidList(Request $request) {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-paid');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $feesId = (int)request('fees_id');
        $requestSessionYearId = (int)request('session_year_id');

        $sessionYearId = $requestSessionYearId ?? $this->cache->getDefaultSessionYear()->id;
        $fees = $this->fees->findById($feesId, ['*'], ['fees_class_type.fees_type:id,name', 'installments:id,name,due_date,due_charges,fees_id']);

        $sql = $this->user->builder()->role('Student')->select('id', 'first_name', 'last_name')->with([
            'student'          => function ($query) {
                $query->select('id', 'class_section_id', 'user_id')->with(['class_section' => function ($query) {
                    $query->select('id', 'class_id', 'section_id', 'medium_id')->with('class:id,name', 'section:id,name', 'medium:id,name');
                }]);
            }, 'optional_fees' => function ($query) {
                $query->with('fees_class_type');
            }, 'fees_paid'     => function ($q) use ($fees) {
                $q->where('fees_id', $fees->id);
            },
            'compulsory_fees'])->whereHas('student.class_section', function ($q) use ($fees) {
            $q->where('class_id', $fees->class_id);
        });
        if (!empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where(function ($q) use ($search) {
                $q->where('id', 'LIKE', "%$search%")->orWhere('first_name', 'LIKE', "%$search%")->orWhere('last_name', 'LIKE', "%$search%");
            });
        }

        if ($request->paid_status == 0) {
            $sql->whereDoesntHave('fees_paid', function ($q) use ($fees) {
                $q->where('fees_id', $fees->id);
            })->orWhereHas('fees_paid', function ($q) use ($fees) {
                $q->where(['fees_id' => $fees->id, 'is_fully_paid' => 0]);
            });
        } else {
            $sql->whereHas('fees_paid', function ($q) use ($fees) {
                $q->where(['fees_id' => $fees->id, 'is_fully_paid' => 1]);
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
            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            // Calculate Minimum amount for installment
            if (count($fees->installments) > 0) {
                collect($fees->installments)->map(function ($data) use ($fees) {
                    $data['minimum_amount'] = $fees->total_compulsory_fees / count($fees->installments);
                    $data['total_amount'] = $data['minimum_amount'] + 0; //Due charges
                    return $data;
                });
            }
            $tempRow['fees'] = $fees->toArray();
            $tempRow['fees_status'] = null;
            $operate = '<div class="dropdown"><button class="btn btn-xs btn-gradient-success btn-rounded btn-icon dropdown-toggle" type="button" data-toggle="dropdown"><i class="fa fa-dollar"></i></button><div class="dropdown-menu">';
            $operate .= '<a href="' . route('fees.compulsory.index', [$fees->id, $row->id]) . '" class="compulsory-data dropdown-item" title="' . trans('Compulsory Fees') . '"><i class="fa fa-dollar text-success mr-2"></i>' . trans('compulsory') . ' ' . trans('fees') . '</a>';

            if (count($fees->optional_fees) > 0) {
                $operate .= '<div class="dropdown-divider"></div><a href="' . route('fees.optional.index', [$fees->id, $row->id]) . '" class="optional-data dropdown-item" title="' . trans('Optional Fees') . '"><i class="fa fa-dollar text-success mr-2"></i>' . trans('optional') . ' ' . trans('fees') . '</a>';
            }
            $operate .= '</div></div>&nbsp;&nbsp;';


            if (!empty($row->fees_paid->is_fully_paid)) {
                $operate .= ($fees->session_year_id == $sessionYearId) ? $operate : "";
                $operate .= BootstrapTableService::button('fa fa-file-pdf-o', route('fees.paid.receipt.pdf', $row->fees_paid->id), ['btn', 'btn-xs', 'btn-gradient-info', 'btn-rounded', 'btn-icon', 'generate-paid-fees-pdf'], ['target' => "_blank", 'data-id' => $row->fees_paid->id, 'title' => trans('generate_pdf') . ' ' . trans('fees')]);
                $tempRow['fees_status'] = $row->fees_paid->is_fully_paid;
            }

            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function feesPaidReceiptPDF($feesPaidId) {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-paid');
        try {
            $feesPaid = $this->feesPaid->builder()->where('id', $feesPaidId)->with([
                'fees.fees_class_type.fees_type',
                'compulsory_fee.installment_fee:id,name',
                'optional_fee' => function ($q) {
                    $q->with(['fees_class_type' => function ($q) {
                        $q->select('id', 'fees_type_id')->with('fees_type:id,name');
                    }
                    ]);
                }])->firstOrFail();
            $student = $this->student->builder()->with('user:id,first_name,last_name')->whereHas('user', function ($q) use ($feesPaid) {
                $q->where('id', $feesPaid->student_id);
            })->firstOrFail();

            $systemVerticalLogo = $this->systemSetting->builder()->where('name', 'vertical_logo')->first();
            $schoolVerticalLogo = $this->schoolSettings->builder()->where('name', 'vertical_logo')->first();
            $school = $this->cache->getSchoolSettings();

//            return view('fees.fees_receipt', compact('systemLogo', 'school', 'feesPaid', 'student'));
            $pdf = Pdf::loadView('fees.fees_receipt', compact('systemVerticalLogo', 'school', 'feesPaid', 'student', 'schoolVerticalLogo'));
            return $pdf->stream('fees-receipt.pdf');
        } catch (Throwable) {
            ResponseService::errorRedirectResponse();
            return false;
        }
    }

    public function payCompulsoryFeesIndex($feesID, $studentID) {
        ResponseService::noFeatureThenRedirect('Fees Management');
        //        ResponseService::noPermissionThenRedirect('fees-edit');
        $fees = $this->fees->findById($feesID, ['*'], ['fees_class_type.fees_type:id,name', 'installments:id,name,due_date,due_charges,fees_id']);
        $oneInstallmentPaid = false;

        $student = $this->user->builder()->role('Student')->select('id', 'first_name', 'last_name')
            ->with(['student' => function ($query) {
                $query->select('id', 'class_section_id', 'user_id', 'guardian_id')->with(['class_section' => function ($query) {
                    $query->select('id', 'class_id', 'section_id', 'medium_id')->with('class:id,name', 'section:id,name', 'medium:id,name');
                }]);
            }, 'fees_paid'    => function ($q) use ($feesID) {
                $q->where('fees_id', $feesID)->first();
            }, 'compulsory_fees.advance_fees'])->findOrFail($studentID);

        if (!empty($student->fees_paid) && $student->fees_paid->is_fully_paid) {
            ResponseService::successRedirectResponse(route('fees.paid.index'), 'Compulsory Fees Already Paid');
        }

        if (count($fees->installments) > 0) {
            $totalFeesAmount = $fees->total_compulsory_fees;
            $totalInstallments = count($fees->installments);

            collect($fees->installments)->map(function ($installment) use ($student, &$totalFeesAmount, &$totalInstallments, $fees, &$oneInstallmentPaid) {

                $installmentPaid = $student->compulsory_fees->first(function ($compulsoryFees) use ($installment) {
                    return $compulsoryFees->installment_id == $installment->id;
                });

                if (!empty($installmentPaid)) {
                    // Removing the Paid installments from total installments so that minimum amount can be calculated for the remaining installments.
                    --$totalInstallments;
                    $oneInstallmentPaid = true;
                    $totalFeesAmount -= $installmentPaid->amount;
                    $installment['is_paid'] = (object)$installmentPaid->toArray();
                    $installment['minimum_amount'] = $totalFeesAmount / $totalInstallments;
                    $installment['maximum_amount'] = $totalFeesAmount;

                } else {
                    $installment['is_paid'] = [];
                    $installment['minimum_amount'] = $totalFeesAmount / $totalInstallments;
                    $installment['maximum_amount'] = $totalFeesAmount;
                }

                if (strtotime(date('Y-m-d')) > strtotime($installment['due_date'])) {
                    $installment['due_charges_amount'] = ($installment['minimum_amount'] * $installment['due_charges']) / 100;
                } else {
                    $installment['due_charges_amount'] = 0;
                }

                $installment['total_amount'] = $installment['minimum_amount'] + $installment['due_charges_amount'];
                $fees->remaining_amount = $totalFeesAmount;
                return $installment;
            });
        }
        return view('fees.pay-compulsory', compact('fees', 'student', 'oneInstallmentPaid'));
    }

    public function payCompulsoryFeesStore(Request $request) {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-paid');

        $request->validate([
            'fees_id'            => 'required|numeric',
            'student_id'         => 'required|numeric',
            'installment_mode'   => 'required|boolean',
            'installment_fees'   => 'array',
            'installment_fees.*' => 'required_if:installment_mode,1|array|required_array_keys:id,due_charges,amount'

        ], [
            'installment_fees.required_if' => 'Please select at least one installment'
        ]);
        try {
            DB::beginTransaction();
            $fees = $this->fees->findById($request->fees_id, ['*'], ['fees_class_type.fees_type:id,name', 'installments:id,name,due_date,due_charges,fees_id']);
            if (count($fees->installments) > 0) {
                collect($fees->installments)->map(function ($data) use ($fees) {
                    $data['minimum_amount'] = $fees->total_compulsory_fees / count($fees->installments);
                    $data['total_amount'] = $data['minimum_amount'] + 0; //Due charges
                    return $data;
                });
            }

            $feesPaid = $this->feesPaid->builder()->where([
                'fees_id'    => $request->fees_id,
                'student_id' => $request->student_id
            ])->first();

            if (!empty($feesPaid) && $feesPaid->is_fully_paid) {
                ResponseService::errorResponse("Compulsory Fees already Paid");
            }

            $amount = 0;
            // If Fees Paid Doesn't Exists
            if ($request->installment_mode) {
                if (!empty($request->installment_fees)) {
                    $amount = array_sum(array_column($request->installment_fees, 'amount'));
                }
                $amount += $request->advance;
            } else {
                $amount = $request->total_amount;
            }

            if (empty($feesPaid)) {
                $feesPaidResult = $this->feesPaid->create([
                    'date'                => date('Y-m-d', strtotime($request->date)),
                    'is_fully_paid'       => $amount >= $fees->total_compulsory_fees,
                    'is_used_installment' => $request->installment_mode,
                    'fees_id'             => $request->fees_id,
                    'student_id'          => $request->student_id,
                    'amount'              => $amount,
                ]);
            } else {
                $feesPaidResult = $this->feesPaid->update($feesPaid->id, [
                    'amount'        => $request->total_amount + $feesPaid->amount,
                    'is_fully_paid' => ($amount + $feesPaid->amount) >= $fees->total_compulsory_fees
                ]);
            }

            if ($request->installment_mode == 1) {
                if (!empty($request->installment_fees)) {
                    foreach ($request->installment_fees as $installment_fee) {
                        $compulsoryFeeData = array(
                            'student_id'     => $request->student_id,
                            'type'           => 'Installment Payment',
                            'installment_id' => $installment_fee['id'],
                            'mode'           => $request->mode,
                            'cheque_no'      => $request->mode == 2 ? $request->cheque_no : null,
                            'amount'         => $installment_fee['amount'],
                            'due_charges'    => $installment_fee['due_charges'] ?? null,
                            'fees_paid_id'   => $feesPaidResult->id,
                            'date'           => date('Y-m-d', strtotime($request->date))
                        );
                        $this->compulsoryFee->create($compulsoryFeeData);
                    }
                }
            } else {
                $compulsoryFeeData = array(
                    'type'         => 'Full Payment',
                    'student_id'   => $request->student_id,
                    'mode'         => $request->mode,
                    'cheque_no'    => $request->mode == 2 ? $request->cheque_no : null,
                    'amount'       => $request->total_amount,
                    'due_charges'  => $request->due_charges_amount ?? null,
                    'fees_paid_id' => $feesPaidResult->id,
                    'date'         => date('Y-m-d', strtotime($request->date))
                );
                $this->compulsoryFee->create($compulsoryFeeData);
            }


            // Add advance amount in installment
            if ($request->advance > 0) {
                $updateCompulsoryFees = $this->compulsoryFee->builder()->where('student_id', $request->student_id)->with('fees_paid')->whereHas('fees_paid', function ($q) use ($request) {
                    $q->where('fees_id', $request->fees_id);
                })->orderBy('id', 'DESC')->first();

                $updateCompulsoryFees->amount += $request->advance;
                $updateCompulsoryFees->save();

                FeesAdvance::create([
                    'compulsory_fee_id' => $updateCompulsoryFees->id,
                    'student_id'        => $request->student_id,
                    'parent_id'         => $request->parent_id,
                    'amount'            => $request->advance
                ]);
            }
            DB::commit();
            ResponseService::successResponse("Data Updated SuccessFully");
        } catch (Throwable $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e, 'FeesController -> compulsoryFeesPaidStore method ');
            ResponseService::errorResponse();
        }
    }

    public function payOptionalFeesIndex($feesID, $studentID) {
        ResponseService::noFeatureThenRedirect('Fees Management');
        //        ResponseService::noPermissionThenRedirect('fees-edit');
        $fees = $this->fees->findById($feesID, ['*'], ['fees_class_type.fees_type:id,name', 'installments:id,name,due_date,due_charges,fees_id']);

        $student = $this->user->builder()->role('Student')->select('id', 'first_name', 'last_name')
            ->with(['student' => function ($query) {
                $query->select('id', 'class_section_id', 'user_id', 'session_year_id')->with(['class_section' => function ($query) {
                    $query->select('id', 'class_id', 'section_id', 'medium_id')->with('class:id,name', 'section:id,name', 'medium:id,name');
                }]);
            }, 'fees_paid'    => function ($q) use ($feesID) {
                $q->where('fees_id', $feesID)->first();
            }])->findOrFail($studentID);


        $optionalFeesData = $this->feesClassType->builder()
            ->where(['class_id' => $student->student->class_section->class_id, 'optional' => 1])
            ->with([
                'fees_type',
                'optional_fees_paid' => function ($query) use ($student) {
                    $query->where('student_id', $student->id)->whereHas('fees_paid', function ($subQuery1) use ($student) {
                        $subQuery1->whereHas('fees', function ($subQuery2) use ($student) {
                            $subQuery2->where('session_year_id', $student->student->session_year_id);
                        });
                    });
                }
            ])
            ->get();

        return view('fees.pay-optional', compact('fees', 'student', 'optionalFeesData'));
    }

    public function payOptionalFeesStore(Request $request) {
        ResponseService::noFeatureThenRedirect('Fees Management');
        ResponseService::noPermissionThenRedirect('fees-paid');
        $request->validate([
            'fees_id'    => 'required|numeric',
            'student_id' => 'required|numeric',
        ]);
        try {
            DB::beginTransaction();

            // First Store in Fees Paid table to get Fees Paid ID
            $feesPaid = $this->feesPaid->builder()->where([
                'fees_id'    => $request->fees_id,
                'student_id' => $request->student_id
            ])->first();

            // If Fees Paid Doesn't Exists
            if (empty($feesPaid)) {
                $feesPaidResult = $this->feesPaid->create([
                    'date'                => date('Y-m-d', strtotime($request->date)),
                    'is_fully_paid'       => 0,
                    'is_used_installment' => 0,
                    'fees_id'             => $request->fees_id,
                    'student_id'          => $request->student_id,
                    'amount'              => $request->total_amount,
                ]);
            } else {
                $feesPaidResult = $this->feesPaid->update($feesPaid->id, [
                    'amount' => $request->total_amount + $feesPaid->amount
                ]);
            }


            $optionalFeesPaymentData = array();

            // Loop to the Optional Fees
            if (!empty($request->fees_class_type)) {
                foreach ($request->fees_class_type as $key => $feesClassType) {
                    if (isset($feesClassType['id'])) {
                        $optionalFeesPaymentData[] = array(
                            'student_id'    => $request->student_id,
                            'class_id'      => $request->class_id,
                            'fees_class_id' => $feesClassType['id'],
                            'mode'          => $request->mode,
                            'cheque_no'     => $request->mode == 2 ? $request->cheque_no : null,
                            'amount'        => $feesClassType['amount'],
                            'fees_paid_id'  => $feesPaidResult->id,
                            'date'          => date('Y-m-d', strtotime($request->date)),
                            'status'        => "Success",
                            'created_at'    => now(),
                            'updated_at'    => now()
                        );
                    }
                }
            }

            $this->optionalFee->createBulk($optionalFeesPaymentData);

            DB::commit();
            ResponseService::successResponse("Data Updated SuccessFully");
        } catch (Throwable $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e, 'FeesController -> compulsoryFeesPaidStore method ');
            ResponseService::errorResponse();
        }
    }
    /* END : Fees Paid Module */
}

