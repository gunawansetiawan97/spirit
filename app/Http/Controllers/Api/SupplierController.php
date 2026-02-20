<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Services\NumberSequenceService;
use App\Traits\HasIndexQuery;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    use HasIndexQuery;

    public function index(Request $request)
    {
        return $this->paginatedResponse(
            Supplier::with(['coa:id,uuid,code,name', 'coaDp:id,uuid,code,name']),
            $request,
            [
                'searchFields' => ['code', 'name', 'phone', 'city'],
                'defaultSort'  => 'name',
            ]
        );
    }

    public function store(Request $request)
    {
        if (!$request->code || str_starts_with($request->code, 'AUTOCODE')) {
            $request->merge(['code' => NumberSequenceService::generate('supplier')]);
        }

        $request->validate(Supplier::storeRules());

        $supplier = Supplier::create($request->only([
            'code', 'name', 'phone', 'address',
            'coa_id', 'coa_dp_id', 'city',
            'npwp_no', 'npwp_name', 'npwp_address', 'nik',
            'is_active',
        ]));

        return $this->storeResponse($supplier);
    }

    public function show(Supplier $supplier)
    {
        return $this->showResponse($supplier, [
            'coa:id,uuid,code,name',
            'coaDp:id,uuid,code,name',
        ]);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $request->validate(Supplier::updateRules($supplier->id));

        $supplier->update($request->only([
            'name', 'phone', 'address',
            'coa_id', 'coa_dp_id', 'city',
            'npwp_no', 'npwp_name', 'npwp_address', 'nik',
            'is_active',
        ]));

        return $this->updateResponse($supplier);
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return $this->destroyResponse($supplier);
    }

    public function all()
    {
        $suppliers = Supplier::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'uuid', 'code', 'name']);

        return response()->json([
            'status' => 'success',
            'data'   => $suppliers,
        ]);
    }
}
