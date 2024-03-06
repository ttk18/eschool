<?php

namespace App\Http\Controllers;

use App\Repositories\Expense\ExpenseInterface;
use App\Repositories\ExpenseCategory\ExpenseCategoryInterface;
use App\Repositories\SessionYear\SessionYearInterface;
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class ExpenseController extends Controller {

    private ExpenseInterface $expense;
    private ExpenseCategoryInterface $expenseCategory;
    private SessionYearInterface $sessionYear;

    public function __construct(ExpenseInterface $expense, ExpenseCategoryInterface $expenseCategory, SessionYearInterface $sessionYear) {
        $this->expense = $expense;
        $this->expenseCategory = $expenseCategory;
        $this->sessionYear = $sessionYear;
    }

    public function index() {
        ResponseService::noFeatureThenRedirect('Expense Management');
        ResponseService::noAnyPermissionThenRedirect(['expense-create', 'expense-list']);

        $expenseCategory = $this->expenseCategory->builder()->pluck('name', 'id')->toArray();
        $sessionYear = $this->sessionYear->builder()->pluck('name', 'id');
        $current_session_year = app(CachingService::class)->getDefaultSessionYear();

        $months = sessionYearWiseMonth();

        return view('expense.index', compact('expenseCategory', 'sessionYear', 'current_session_year','months'));
    }


    public function create() {
        ResponseService::noFeatureThenRedirect('Expense Management');
        ResponseService::noPermissionThenRedirect('expense-create');
    }


    public function store(Request $request) {
        ResponseService::noFeatureThenRedirect('Expense Management');
        ResponseService::noPermissionThenRedirect('expense-create');
        try {
            DB::beginTransaction();
            $data = ['category_id' => $request->category_id, 'title' => $request->title, 'ref_no' => $request->ref_no, 'amount' => $request->amount, 'date' => date('Y-m-d', strtotime($request->date)), 'description' => $request->description, 'session_year_id' => $request->session_year_id];
            $this->expense->create($data);
            DB::commit();
            ResponseService::successResponse('Data Stored Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "Expense Controller -> Store Method");
            ResponseService::errorResponse();
        }
    }


    public function show($id) {
        ResponseService::noFeatureThenRedirect('Expense Management');
        ResponseService::noPermissionThenRedirect('expense-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'date');
        $order = request('order', 'DESC');
        $search = request('search');
        $category_id = request('category_id');
        $session_year_id = request('session_year_id');
        $month = request('month');

        $sql = $this->expense->builder()->with('category')->select('*', DB::raw('SUM(amount) as total_salary'))->groupBy('month', 'date')->where(function ($query) use ($search) {
                $query->when($search, function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('title', 'LIKE', "%$search%")->orWhere('ref_no', 'LIKE', "%$search%")->orWhere('amount', 'LIKE', "%$search%")->orWhere('date', 'LIKE', "%$search%")->orWhere('description', 'LIKE', "%$search%")->orWhereHas('category', function ($q) use ($search) {
                                $q->Where('name', 'LIKE', "%$search%");
                            });
                    });
                });
            });

        if ($category_id) {
            if ($category_id != 'salary') {
                $sql->where('category_id', $category_id)->whereNull('staff_id');
            } else {
                $sql->whereNotNull('staff_id');

            }
        }

        if ($session_year_id) {
            $sql->where('session_year_id', $session_year_id);
        }

        if ($month) {
            $sql->whereMonth('date', $month);
        }

        $total = $sql->get()->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;

        foreach ($res as $row) {
            $operate = '';
            if (!$row->month) {
                $operate .= BootstrapTableService::editButton(route('expense.update', $row->id));
                $operate .= BootstrapTableService::deleteButton(route('expense.destroy', $row->id));
            }

            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $tempRow['amount'] = $row->total_salary;
            if ($row->staff_id) {
                $tempRow['category.name'] = 'Salary';
            }
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }


    public function update(Request $request, $id) {
        ResponseService::noFeatureThenRedirect('Expense Management');
        ResponseService::noPermissionThenSendJson('expense-edit');
        try {
            DB::beginTransaction();
            $data = ['category_id' => $request->category_id, 'title' => $request->title, 'ref_no' => $request->ref_no, 'amount' => $request->amount, 'date' => date('Y-m-d', strtotime($request->date)), 'description' => $request->description, 'session_year_id' => $request->session_year_id,];
            $this->expense->update($id, $data);
            DB::commit();
            ResponseService::successResponse('Data Updated Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "Expense Controller -> Update Method");
            ResponseService::errorResponse();
        }
    }


    public function destroy($id) {
        ResponseService::noFeatureThenRedirect('Expense Management');
        ResponseService::noPermissionThenSendJson('expense-delete');

        try {
            DB::beginTransaction();
            $this->expense->deleteById($id);
            DB::commit();
            ResponseService::successResponse('Data Deleted Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "Expense Controller -> Destroy Method");
            ResponseService::errorResponse();
        }
    }
}
