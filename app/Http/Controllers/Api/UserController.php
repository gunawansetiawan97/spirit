<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\HasIndexQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use HasIndexQuery;

    public function index(Request $request)
    {
        $query = User::with(['role:id,uuid,code,name', 'branches:id,uuid,code,name']);

        // Custom filter: branch_id via pivot relation
        if ($request->branch_id) {
            $query->whereHas('branches', function ($q) use ($request) {
                $q->where('branches.id', $request->branch_id);
            });
        }

        return $this->paginatedResponse($query, $request, [
            'searchFields' => ['name', 'email'],
            'filterFields' => ['role_id'],
            'defaultSort' => 'name',
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(User::storeRules());

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
            'branch_id' => $request->branch_ids[0],
            'is_active' => $request->is_active ?? true,
        ]);

        $user->branches()->sync($request->branch_ids);
        $user->load(['role:id,uuid,code,name', 'branches:id,uuid,code,name']);

        return $this->storeResponse($user);
    }

    public function show(User $user)
    {
        return $this->showResponse(
            $user,
            ['role:id,uuid,code,name', 'branches:id,uuid,code,name'],
            ['createdBy:id,name', 'updatedBy:id,name']
        );
    }

    public function update(Request $request, User $user)
    {
        $request->validate(User::updateRules($user->id));

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => $request->role_id,
            'branch_id' => $request->branch_ids[0],
            'is_active' => $request->is_active ?? $user->is_active,
        ];

        if ($request->password) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        $user->branches()->sync($request->branch_ids);
        $user->load(['role:id,uuid,code,name', 'branches:id,uuid,code,name']);

        return $this->updateResponse($user);
    }

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak dapat menghapus akun sendiri',
            ], 422);
        }

        $user->branches()->detach();
        $user->delete();

        return $this->destroyResponse($user);
    }
}
