<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $query = Branch::withCount('users');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('code', 'like', "%{$request->search}%")
                    ->orWhere('name', 'like', "%{$request->search}%");
            });
        }

        if ($request->is_active !== null) {
            $query->where('is_active', $request->is_active);
        }

        $sortBy = $request->sort_by ?? 'name';
        $sortDirection = $request->sort_direction ?? 'asc';
        $query->orderBy($sortBy, $sortDirection);

        $perPage = $request->per_page ?? 10;

        if ($perPage == -1) {
            $branches = $query->get();

            return response()->json([
                'status' => 'success',
                'data' => $branches,
                'meta' => [
                    'current_page' => 1,
                    'per_page' => $branches->count(),
                    'total' => $branches->count(),
                    'last_page' => 1,
                ],
            ]);
        }

        $branches = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $branches->items(),
            'meta' => [
                'current_page' => $branches->currentPage(),
                'per_page' => $branches->perPage(),
                'total' => $branches->total(),
                'last_page' => $branches->lastPage(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:branches,code',
            'name' => 'required|string|max:100',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $branch = Branch::create($request->only(['code', 'name', 'address', 'phone', 'is_active']));

        return response()->json([
            'status' => 'success',
            'message' => 'Cabang berhasil dibuat',
            'data' => $branch,
        ], 201);
    }

    public function show(Branch $branch)
    {
        return response()->json([
            'status' => 'success',
            'data' => $branch,
        ]);
    }

    public function update(Request $request, Branch $branch)
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:branches,code,' . $branch->id,
            'name' => 'required|string|max:100',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $branch->update($request->only(['code', 'name', 'address', 'phone', 'is_active']));

        return response()->json([
            'status' => 'success',
            'message' => 'Cabang berhasil diupdate',
            'data' => $branch,
        ]);
    }

    public function destroy(Branch $branch)
    {
        if ($branch->users()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cabang tidak dapat dihapus karena masih digunakan oleh user',
            ], 422);
        }

        $branch->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Cabang berhasil dihapus',
        ]);
    }

    public function all()
    {
        $branches = Branch::active()->orderBy('name')->get(['id', 'uuid', 'code', 'name']);

        return response()->json([
            'status' => 'success',
            'data' => $branches,
        ]);
    }
}
