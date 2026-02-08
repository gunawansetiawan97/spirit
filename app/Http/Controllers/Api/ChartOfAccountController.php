<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;

class ChartOfAccountController extends Controller
{
    public function index(Request $request)
    {
        $query = ChartOfAccount::with([
            'accountGroup:id,group_name,normal_balance,account_type_id',
            'accountGroup.accountType:id,code,name',
            'parent:id,code,name',
        ]);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('code', 'like', "%{$request->search}%")
                    ->orWhere('name', 'like', "%{$request->search}%");
            });
        }

        if ($request->is_active !== null) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->posting_type) {
            $query->where('posting_type', $request->posting_type);
        }

        if ($request->account_group_id) {
            $query->where('account_group_id', $request->account_group_id);
        }

        $sortBy = $request->sort_by ?? 'code';
        $sortDirection = $request->sort_direction ?? 'asc';
        $query->orderBy($sortBy, $sortDirection);

        $perPage = $request->per_page ?? 10;

        if ($perPage == -1) {
            $all = $query->get();
            return response()->json([
                'status' => 'success',
                'data' => $all,
                'meta' => [
                    'current_page' => 1,
                    'per_page' => $all->count(),
                    'total' => $all->count(),
                    'last_page' => 1,
                ],
            ]);
        }

        $paginated = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:chart_of_accounts,code',
            'name' => 'required|string|max:100',
            'account_group_id' => 'required|exists:account_groups,id',
            'posting_type' => 'required|in:Posting,Header',
            'parent_id' => 'nullable|exists:chart_of_accounts,id',
            'is_active' => 'boolean',
            'allow_manual_journal' => 'boolean',
            'currency' => 'required|string|max:5',
            'cost_center' => 'boolean',
        ]);

        $coa = ChartOfAccount::create($request->only([
            'code', 'name', 'account_group_id', 'posting_type', 'parent_id',
            'is_active', 'allow_manual_journal', 'currency', 'cost_center',
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Chart of Account berhasil dibuat',
            'data' => $coa,
        ], 201);
    }

    public function show(ChartOfAccount $chart_of_account)
    {
        $chart_of_account->load([
            'accountGroup:id,group_name,normal_balance,account_type_id',
            'accountGroup.accountType:id,code,name',
            'parent:id,uuid,code,name',
            'createdBy:id,name',
            'updatedBy:id,name',
            'approvedBy:id,name',
            'printedBy:id,name',
        ]);

        $data = $chart_of_account->toArray();
        $data['created_by_user'] = $chart_of_account->createdBy;
        $data['updated_by_user'] = $chart_of_account->updatedBy;
        $data['approved_by_user'] = $chart_of_account->approvedBy;
        $data['printed_by_user'] = $chart_of_account->printedBy;

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function update(Request $request, ChartOfAccount $chart_of_account)
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:chart_of_accounts,code,' . $chart_of_account->id,
            'name' => 'required|string|max:100',
            'account_group_id' => 'required|exists:account_groups,id',
            'posting_type' => 'required|in:Posting,Header',
            'parent_id' => 'nullable|exists:chart_of_accounts,id',
            'is_active' => 'boolean',
            'allow_manual_journal' => 'boolean',
            'currency' => 'required|string|max:5',
            'cost_center' => 'boolean',
        ]);

        $chart_of_account->update($request->only([
            'code', 'name', 'account_group_id', 'posting_type', 'parent_id',
            'is_active', 'allow_manual_journal', 'currency', 'cost_center',
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Chart of Account berhasil diupdate',
            'data' => $chart_of_account,
        ]);
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

        return response()->json([
            'status' => 'success',
            'message' => 'Chart of Account berhasil dihapus',
        ]);
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
