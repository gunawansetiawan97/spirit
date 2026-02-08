<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountGroup;
use Illuminate\Http\Request;

class AccountGroupController extends Controller
{
    public function index(Request $request)
    {
        $query = AccountGroup::with('accountType:id,code,name');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('group_name', 'like', "%{$request->search}%")
                    ->orWhereHas('accountType', function ($q2) use ($request) {
                        $q2->where('code', 'like', "%{$request->search}%");
                    });
            });
        }

        if ($request->is_active !== null) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->account_type_id) {
            $query->where('account_type_id', $request->account_type_id);
        }

        $sortBy = $request->sort_by ?? 'group_name';
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
            'account_type_id' => 'required|exists:account_types,id',
            'group_name' => 'required|string|max:100',
            'normal_balance' => 'required|in:Debit,Credit',
            'is_active' => 'boolean',
        ]);

        $group = AccountGroup::create($request->only([
            'account_type_id', 'group_name', 'normal_balance', 'is_active',
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Group Akun berhasil dibuat',
            'data' => $group,
        ], 201);
    }

    public function show(AccountGroup $account_group)
    {
        $account_group->load([
            'accountType:id,code,name',
            'createdBy:id,name',
            'updatedBy:id,name',
            'approvedBy:id,name',
            'printedBy:id,name',
        ]);

        $data = $account_group->toArray();
        $data['created_by_user'] = $account_group->createdBy;
        $data['updated_by_user'] = $account_group->updatedBy;
        $data['approved_by_user'] = $account_group->approvedBy;
        $data['printed_by_user'] = $account_group->printedBy;

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function update(Request $request, AccountGroup $account_group)
    {
        $request->validate([
            'account_type_id' => 'required|exists:account_types,id',
            'group_name' => 'required|string|max:100',
            'normal_balance' => 'required|in:Debit,Credit',
            'is_active' => 'boolean',
        ]);

        $account_group->update($request->only([
            'account_type_id', 'group_name', 'normal_balance', 'is_active',
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Group Akun berhasil diupdate',
            'data' => $account_group,
        ]);
    }

    public function destroy(AccountGroup $account_group)
    {
        if ($account_group->chartOfAccounts()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Group Akun tidak dapat dihapus karena masih digunakan oleh Chart of Account',
            ], 422);
        }

        $account_group->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Group Akun berhasil dihapus',
        ]);
    }

    public function all()
    {
        $groups = AccountGroup::with('accountType:id,code,name')
            ->active()
            ->orderBy('group_name')
            ->get(['id', 'uuid', 'account_type_id', 'group_name', 'normal_balance']);

        return response()->json([
            'status' => 'success',
            'data' => $groups,
        ]);
    }
}
