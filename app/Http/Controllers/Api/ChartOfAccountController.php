<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Traits\HasIndexQuery;
use Illuminate\Http\Request;

class ChartOfAccountController extends Controller
{
    use HasIndexQuery;

    public function index(Request $request)
    {
        return $this->paginatedResponse(
            ChartOfAccount::with([
                'accountGroup:id,group_name,normal_balance,account_type_id',
                'accountGroup.accountType:id,code,name',
                'parent:id,code,name',
            ]),
            $request,
            [
                'searchFields' => ['code', 'name'],
                'filterFields' => ['posting_type', 'account_group_id'],
                'defaultSort' => 'code',
            ]
        );
    }

    public function store(Request $request)
    {
        $request->validate(ChartOfAccount::storeRules());

        $coa = ChartOfAccount::create($request->only([
            'code', 'name', 'account_group_id', 'posting_type', 'parent_id',
            'is_active', 'allow_manual_journal', 'currency', 'cost_center',
        ]));

        return $this->storeResponse($coa); 
    }

    public function show(ChartOfAccount $chart_of_account)
    {
        return $this->showResponse($chart_of_account, [
            'accountGroup:id,group_name,normal_balance,account_type_id',
            'accountGroup.accountType:id,code,name',
            'parent:id,uuid,code,name',
        ]);
    }

    public function update(Request $request, ChartOfAccount $chart_of_account)
    {
        $request->validate(ChartOfAccount::updateRules($chart_of_account->id));

        $chart_of_account->update($request->only([
            'code', 'name', 'account_group_id', 'posting_type', 'parent_id',
            'is_active', 'allow_manual_journal', 'currency', 'cost_center',
        ]));

        return $this->updateResponse($chart_of_account); 
    }

    public function destroy(ChartOfAccount $chart_of_account)
    {
        if ($chart_of_account->children()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'COA tidak dapat dihapus karena masih memiliki akun anak',
            ], 422);
        }

        $chart_of_account->delete();

        return $this->destroyResponse($chart_of_account);
    }

    public function all(Request $request)
    {
        $query = ChartOfAccount::active()->orderBy('code');

        if ($request->posting_only) {
            $query->posting();
        }

        $coas = $query->get(['id', 'uuid', 'code', 'name']);

        return response()->json([
            'status' => 'success',
            'data' => $coas,
        ]);
    }
}
