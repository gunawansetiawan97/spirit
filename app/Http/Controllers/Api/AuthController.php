<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::with(['role.permissions', 'branch'])
            ->where('email', $request->email)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Akun Anda tidak aktif. Hubungi administrator.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'uuid' => $user->uuid,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role ? [
                        'id' => $user->role->id,
                        'uuid' => $user->role->uuid,
                        'code' => $user->role->code,
                        'name' => $user->role->name,
                    ] : null,
                    'branch' => $user->branch ? [
                        'id' => $user->branch->id,
                        'uuid' => $user->branch->uuid,
                        'code' => $user->branch->code,
                        'name' => $user->branch->name,
                    ] : null,
                ],
                'permissions' => $user->getPermissions(),
                'menus' => $user->getMenus(),
                'token' => $token,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil',
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load(['role.permissions', 'branch']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'uuid' => $user->uuid,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role ? [
                        'id' => $user->role->id,
                        'uuid' => $user->role->uuid,
                        'code' => $user->role->code,
                        'name' => $user->role->name,
                    ] : null,
                    'branch' => $user->branch ? [
                        'id' => $user->branch->id,
                        'uuid' => $user->branch->uuid,
                        'code' => $user->branch->code,
                        'name' => $user->branch->name,
                    ] : null,
                ],
                'permissions' => $user->getPermissions(),
                'menus' => $user->getMenus(),
            ],
        ]);
    }

    public function switchBranch(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
        ]);

        $branch = Branch::findOrFail($request->branch_id);

        return response()->json([
            'status' => 'success',
            'message' => 'Cabang berhasil diganti',
            'data' => [
                'branch' => [
                    'id' => $branch->id,
                    'uuid' => $branch->uuid,
                    'code' => $branch->code,
                    'name' => $branch->name,
                ],
            ],
        ]);
    }

    public function branches()
    {
        $branches = Branch::active()->orderBy('name')->get(['id', 'uuid', 'code', 'name']);

        return response()->json([
            'status' => 'success',
            'data' => $branches,
        ]);
    }
}
