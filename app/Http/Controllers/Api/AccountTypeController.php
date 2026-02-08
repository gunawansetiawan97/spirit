<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountType;
use Illuminate\Http\Request;

class AccountTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $query = AccountType::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('code', 'like', "%{$request->search}%");
            });
        }

        if ($request->is_active !== null) {
            $query->where('is_active', $request->is_active);
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

        $users = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:account_types,code',
            'is_active' => 'boolean',
        ]);

        $request['name'] = $request['code'];
        $account_type = AccountType::create($request->only(['code','name','is_active']));

        return response()->json([
            'status' => 'success',
            'message' => 'Tipe Akun berhasil dibuat',
            'data' => $account_type,
        ], 201);

    }

    /**
     * Display the specified resource.
     */
    public function show(AccountType $account_type)
    {
    
        $account_type->load([
            'createdBy:id,name',
            'updatedBy:id,name',
            'approvedBy:id,name',
            'printedBy:id,name',
        ]);

        $data = $account_type->toArray();
        $data['created_by_user'] = $account_type->createdBy;
        $data['updated_by_user'] = $account_type->updatedBy;
        $data['approved_by_user'] = $account_type->approvedBy;
        $data['printed_by_user'] = $account_type->printedBy;

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AccountType $account_type)
    {
           $request->validate([
            'code' => 'required|string|max:20|unique:account_types,code,' . $account_type->id,
            'is_active' => 'boolean',
        ]);

        $request['name'] = $request['code'];
        $account_type->update($request->only(['code', 'name', 'is_active']));

        return response()->json([
            'status' => 'success',
            'message' => 'Tipe Akun berhasil diupdate',
            'data' => $account_type,
        ]);
    
    }

    public function destroy(AccountType $account_type)
    {
        $account_type->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Tipe Akun berhasil dihapus',
        ]);
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
