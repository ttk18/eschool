<?php

namespace App\Http\Controllers;

use App\Repositories\Expense\ExpenseInterface;
use App\Repositories\LeaveMaster\LeaveMasterInterface;
use App\Repositories\SessionYear\SessionYearInterface;
use App\Repositories\Staff\StaffInterface;
use App\Services\CachingService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class PayrollController extends Controller {
    private SessionYearInterface $sessionYear;
    private StaffInterface $staff;
    private ExpenseInterface $expense;
    private LeaveMasterInterface $leaveMaster;

    public function __construct(SessionYearInterface $sessionYear, StaffInterface $staff, ExpenseInterface $expense, LeaveMasterInterface $leaveMaster) {
        $this->sessionYear = $sessionYear;
        $this->staff = $staff;
        $this->expense = $expense;
        $this->leaveMaster = $leaveMaster;
    }

    public function index() {
        //
        ResponseService::noFeatureThenRedirect('Expense Management');
        ResponseService::noPermissionThenRedirect('payroll-list');

        $sessionYear = $this->sessionYear->builder()->orderBy('start_date', 'ASC')->first();
        $sessionYear = date('Y', strtotime($sessionYear->start_date));
        // Get months starting from session year
        $months = sessionYearWiseMonth();

        return view('payroll.index', compact('sessionYear', 'months'));
    }

    public function create() {
        //
        ResponseService::noFeatureThenRedirect('Expense Management');
        ResponseService::noPermissionThenRedirect('payroll-create');
    }

    public function store(Request $request) {
        //
        ResponseService::noFeatureThenRedirect('Expense Management');
        ResponseService::noPermissionThenRedirect('payroll-create');

        $request->validate([
            'net_salary' => 'required',
            'date'       => 'required'
        ], [
            'net_salary.required' => trans('no_records_found')
        ]);

        try {
            DB::beginTransaction();
            $sessionYear = app(CachingService::class)->getDefaultSessionYear();
            $data = array();
            foreach ($request->net_salary as $key => $salary) {
                $data[] = [
                    'title'           => Carbon::create()->month($request->month)->format('F') . ' - ' . $request->year,
                    'description'     => 'Salary',
                    'month'           => $request->month,
                    'year'            => $request->year,
                    'staff_id'        => $key,
                    'basic_salary'    => $request->basic_salary[$key],
                    'paid_leaves'     => $request->paid_leave[$key],
                    'amount'          => $salary,
                    'session_year_id' => $sessionYear->id,
                    'date'            => date('Y-m-d', strtotime($request->date)),
                ];
            }
            $this->expense->upsert($data, ['staff_id', 'month', 'year'], ['amount', 'session_year_id']);
            DB::commit();
            ResponseService::successResponse('Data Stored Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, 'Payroll Controller -> Store method');
            ResponseService::errorResponse();
        }
    }

    public function show() {
        ResponseService::noFeatureThenRedirect('Expense Management');
        ResponseService::noPermissionThenRedirect('payroll-list');

        $sort = request('sort', 'rank');
        $order = request('order', 'ASC');
        $search = request('search');
        $month = request('month');
        $year = request('year');

        $leaveMaster = $this->leaveMaster->builder()->whereHas('session_year', function ($q) use ($month, $year) {
            $q->where(function ($q) use ($month, $year) {
                $q->whereMonth('start_date', '<=', $month)->whereYear('start_date', $year);
            })->orWhere(function ($q) use ($month, $year) {
                $q->whereMonth('start_date', '>=', $month)->whereYear('end_date', '<=', $year);
            });
        })->first();

        $sql = $this->staff->builder()->with(['user', 'expense', 'leave' => function ($q) use ($month) {
            $q->where('status', 1)->withCount(['leave_detail as full_leave' => function ($q) use ($month) {
                $q->whereMonth('date', $month)->where('type', 'Full');
            }])->withCount(['leave_detail as half_leave' => function ($q) use ($month) {
                $q->whereMonth('date', $month)->whereNot('type', 'Full');
            }]);
        }])->whereHas('user', function ($q) {
            $q->Owner();
        })->when($search, function ($query) use ($search) {
            $query->where(function ($query) use ($search) {
                $query->orwhereHas('user', function ($q) use ($search) {
                    $q->where('first_name', 'LIKE', "%$search%")->orwhere('last_name', 'LIKE', "%$search%");
                });
            });
        });

        $total = $sql->count();

        $sql->orderBy($sort, $order);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;

        foreach ($res as $row) {
            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $salary_deduction = 0;
            $salary = $row->salary;
            $full_leave = isset($row->leave) ? $row->leave->sum('full_leave') : 0;
            $half_leave = isset($row->leave) ? ($row->leave->sum('half_leave') / 2) : 0;
            $total_leave = $full_leave + $half_leave;
            $tempRow['total_leaves'] = $total_leave;
            $tempRow['salary_deduction'] = $salary_deduction;

            if (isset($row->expense)) {
                // TODO : this line can be converted into filter searching instead of searching from query
                $expense = $row->expense()->where('month', $month)->where('year', $year)->first();
                if ($expense) {
                    $status = 1;
                    $tempRow['salary'] = $expense->basic_salary;
                    $salary = $expense->getRawOriginal('basic_salary');

                    $tempRow['status'] = $status;
                    $tempRow['paid_leaves'] = $expense->paid_leaves;
                    if ($expense->paid_leaves < $total_leave) {
                        if ($leaveMaster && $leaveMaster->leaves) {
                            $unpaid_leave = $total_leave - $leaveMaster->leaves;
                            $per_day_salary = $salary / 30;
                            $salary_deduction = $unpaid_leave * $per_day_salary;
                        }
                        $tempRow['salary_deduction'] = $salary_deduction;
                    }
                    $tempRow['net_salary'] = $expense->amount;
                } else if ($leaveMaster) {
                    $tempRow['paid_leaves'] = $leaveMaster->leaves;
                    if ($leaveMaster->leaves < $total_leave) {
                        if ($leaveMaster->leaves) {
                            $unpaid_leave = $total_leave - $leaveMaster->leaves;
                            $per_day_salary = $salary / 30;
                            $salary_deduction = $unpaid_leave * $per_day_salary;
                        }
                        $tempRow['salary_deduction'] = $salary_deduction;
                    }
                    $tempRow['net_salary'] = $salary - $salary_deduction;
                }
            }
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

}
