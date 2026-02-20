<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdjustmentType;
use App\Services\NumberSequenceService;
use App\Traits\HasIndexQuery;
use Illuminate\Http\Request;

class AdjustmentTypeController extends Controller
{
    use HasIndexQuery;

    public function index(Request $request)
    {
        return $this->paginatedResponse(
            AdjustmentType::with(['coa:id,uuid,code,name']),
            $request,
            [
                'searchFields' => ['code', 'name'],
                'defaultSort'  => 'code',
            ]
        );
    }

    public function store(Request $request)
    {
        if (!$request->code || str_starts_with($request->code, 'AUTOCODE')) {
            $request->merge(['code' => NumberSequenceService::generate('adjustment-type')]);
        }

        $request->validate(AdjustmentType::storeRules());

        $type = AdjustmentType::create($request->only(['code', 'name', 'coa_id', 'description', 'is_active']));

        return $this->storeResponse($type);
    }

    public function show(AdjustmentType $adjustmentType)
    {
        return $this->showResponse($adjustmentType, ['coa:id,uuid,code,name']);
    }

    public function update(Request $request, AdjustmentType $adjustmentType)
    {
        $request->validate(AdjustmentType::updateRules($adjustmentType->id));

        $adjustmentType->update($request->only(['name', 'coa_id', 'description', 'is_active']));

        return $this->updateResponse($adjustmentType);
    }

    public function destroy(AdjustmentType $adjustmentType)
    {
        $adjustmentType->delete();

        return $this->destroyResponse($adjustmentType);
    }

    public function all()
    {
        $types = AdjustmentType::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'uuid', 'code', 'name']);

        return response()->json([
            'status' => 'success',
            'data'   => $types,
        ]);
    }
}
