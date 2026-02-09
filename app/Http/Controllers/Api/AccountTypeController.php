<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountType;
use App\Traits\HasIndexQuery;
use Illuminate\Http\Request;

class AccountTypeController extends Controller
{
    use HasIndexQuery;

    public function index(Request $request)
    {
        return $this->paginatedResponse(AccountType::query(), $request, [
            'searchFields' => ['code'],
            'defaultSort' => 'code',
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(AccountType::storeRules());

        $request['name'] = $request['code'];
        $account_type = AccountType::create($request->only(['code', 'name', 'is_active']));
 
        
        return $this->storeResponse($account_type);
    }

    public function show(AccountType $account_type)
    {
        return $this->showResponse($account_type);
    }

    public function update(Request $request, AccountType $account_type)
    {
        $request->validate(AccountType::updateRules($account_type->id));

        $request['name'] = $request['code'];
        $account_type->update($request->only(['code', 'name', 'is_active']));

        return $this->updateResponse($account_type); 
    }

    public function destroy(AccountType $account_type)
    {
        $account_type->delete();

        return $this->destroyResponse($account_type);
    }

    public function all()
    {
        $types = AccountType::where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'uuid', 'code', 'name']);

        return response()->json([
            'status' => 'success',
            'data' => $types,
        ]);
    }
}
