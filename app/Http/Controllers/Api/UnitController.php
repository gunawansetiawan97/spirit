<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $query = Unit::query();

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
            'code' => 'required|string|max:20|unique:units,code',
            'name' => 'required|string|max:100',
            'is_active' => 'boolean',
        ]);
 
        $unit = Unit::create($request->only(['code','name','is_active']));

        return response()->json([
            'status' => 'success',
            'message' => 'Unit berhasil dibuat',
            'data' => $unit,
        ], 201);

    }

    /**
     * Display the specified resource.
     */
    public function show(Unit $unit)
    {
    
        $unit->load([
            'createdBy:id,name',
            'updatedBy:id,name',
            'approvedBy:id,name',
            'printedBy:id,name',
        ]);

        $data = $unit->toArray();
        $data['created_by_user'] = $unit->createdBy;
        $data['updated_by_user'] = $unit->updatedBy;
        $data['approved_by_user'] = $unit->approvedBy;
        $data['printed_by_user'] = $unit->printedBy;

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Unit $unit)
    {
           $request->validate([
            'code' => 'required|string|max:20|unique:units,code,' . $unit->id,
            'is_active' => 'boolean',
        ]);
 
        $unit->update($request->only(['code', 'name', 'is_active']));

        return response()->json([
            'status' => 'success',
            'message' => 'Unit berhasil diupdate',
            'data' => $unit,
        ]);
    
    }

    public function destroy(Unit $unit)
    {
        $unit->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Unit berhasil dihapus',
        ]);
    }

    public function all()
    {
        $types = Unit::where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'uuid', 'code', 'name']);

        return response()->json([
            'status' => 'success',
            'data' => $types,
        ]);
    }
}
