<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\NumberSequenceService;
use App\Traits\HasIndexQuery;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    use HasIndexQuery;

    public function index(Request $request)
    {
        return $this->paginatedResponse(Branch::withCount('users'), $request, [
            'searchFields' => ['code', 'name'],
            'defaultSort' => 'name',
        ]);
    }

    public function store(Request $request)
    {
        if (!$request->code || str_starts_with($request->code, 'AUTOCODE')) {
            $request->merge(['code' => NumberSequenceService::generate('branch')]);
        }

        $request->validate(Branch::storeRules());

        $branch = Branch::create($request->only(['code', 'name', 'address', 'phone', 'is_active']));

        return $this->storeResponse($branch);
    }

    public function show(Branch $branch)
    {
        return $this->showResponse($branch);
    }

    public function update(Request $request, Branch $branch)
    {
        $request->validate(Branch::updateRules($branch->id));

        $branch->update($request->only(['code', 'name', 'address', 'phone', 'is_active']));

        return $this->updateResponse($branch); 
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

        return $this->destroyResponse($branch); 
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
