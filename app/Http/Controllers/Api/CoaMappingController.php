<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CoaMapping;
use Illuminate\Http\Request;

class CoaMappingController extends Controller
{
    public function index(Request $request)
    {
        $query = CoaMapping::with('coa:id,code,name');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('module', 'like', "%{$request->search}%")
                    ->orWhere('mapping_key', 'like', "%{$request->search}%")
                    ->orWhere('description', 'like', "%{$request->search}%")
                    ->orWhereHas('coa', function ($q2) use ($request) {
                        $q2->where('code', 'like', "%{$request->search}%")
                            ->orWhere('name', 'like', "%{$request->search}%");
                    });
            });
        }

        if ($request->is_active !== null) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->module) {
            $query->where('module', $request->module);
        }

        $sortBy = $request->sort_by ?? 'module';
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
            'module' => 'required|string|max:50',
            'mapping_key' => 'required|string|max:50|unique:coa_mappings,mapping_key,NULL,id,module,' . $request->module,
            'coa_id' => 'required|exists:chart_of_accounts,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $mapping = CoaMapping::create($request->only([
            'module', 'mapping_key', 'coa_id', 'description', 'is_active',
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'COA Mapping berhasil dibuat',
            'data' => $mapping,
        ], 201);
    }

    public function show(CoaMapping $coa_mapping)
    {
        $coa_mapping->load([
            'coa:id,uuid,code,name',
            'createdBy:id,name',
            'updatedBy:id,name',
            'approvedBy:id,name',
            'printedBy:id,name',
        ]);

        $data = $coa_mapping->toArray();
        $data['created_by_user'] = $coa_mapping->createdBy;
        $data['updated_by_user'] = $coa_mapping->updatedBy;
        $data['approved_by_user'] = $coa_mapping->approvedBy;
        $data['printed_by_user'] = $coa_mapping->printedBy;

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function update(Request $request, CoaMapping $coa_mapping)
    {
        $request->validate([
            'module' => 'required|string|max:50',
            'mapping_key' => 'required|string|max:50|unique:coa_mappings,mapping_key,' . $coa_mapping->id . ',id,module,' . $request->module,
            'coa_id' => 'required|exists:chart_of_accounts,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $coa_mapping->update($request->only([
            'module', 'mapping_key', 'coa_id', 'description', 'is_active',
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'COA Mapping berhasil diupdate',
            'data' => $coa_mapping,
        ]);
    }

    public function destroy(CoaMapping $coa_mapping)
    {
        $coa_mapping->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'COA Mapping berhasil dihapus',
        ]);
    }
}
