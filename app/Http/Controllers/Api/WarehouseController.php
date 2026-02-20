<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Services\NumberSequenceService;
use App\Traits\HasIndexQuery;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    use HasIndexQuery;

    public function index(Request $request)
    {
        return $this->paginatedResponse(Warehouse::query(), $request, [
            'searchFields' => ['code', 'name'],
            'defaultSort'  => 'code',
        ]);
    }

    public function store(Request $request)
    {
        if (!$request->code || str_starts_with($request->code, 'AUTOCODE')) {
            $request->merge(['code' => NumberSequenceService::generate('warehouse')]);
        }

        $request->validate(Warehouse::storeRules());

        $warehouse = Warehouse::create($request->only(['code', 'name', 'description', 'is_active']));

        return $this->storeResponse($warehouse);
    }

    public function show(Warehouse $warehouse)
    {
        return $this->showResponse($warehouse);
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $request->validate(Warehouse::updateRules($warehouse->id));

        $warehouse->update($request->only(['name', 'description', 'is_active']));

        return $this->updateResponse($warehouse);
    }

    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();

        return $this->destroyResponse($warehouse);
    }

    public function all()
    {
        $warehouses = Warehouse::where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'uuid', 'code', 'name']);

        return response()->json([
            'status' => 'success',
            'data'   => $warehouses,
        ]);
    }
}
