<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountGroup;
use App\Traits\HasIndexQuery;
use Illuminate\Http\Request;

class AccountGroupController extends Controller
{
    use HasIndexQuery;

    public function index(Request $request)
    {
        return $this->paginatedResponse(
            AccountGroup::with('accountType:id,code,name'),
            $request,
            [
                'searchFields' => ['group_name', 'accountType.code'],
                'filterFields' => ['account_type_id'],
                'defaultSort' => 'group_name',
            ]
        );
    }

    public function store(Request $request)
    {
        $request->validate(AccountGroup::storeRules());

        $group = AccountGroup::create($request->only([
            'account_type_id', 'group_name', 'normal_balance', 'is_active',
        ]));

        return $this->storeResponse($group);
    }

    public function show(AccountGroup $account_group)
    {
        return $this->showResponse($account_group, ['accountType:id,code,name']);
    }

    public function update(Request $request, AccountGroup $account_group)
    {
        $request->validate(AccountGroup::updateRules($account_group->id));

        $account_group->update($request->only([
            'account_type_id', 'group_name', 'normal_balance', 'is_active',
        ]));

        return $this->updateResponse($account_group);
 
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

        return $this->destroyResponse($account_group);
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
