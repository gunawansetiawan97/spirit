<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductBrand;
use App\Services\NumberSequenceService;
use App\Traits\HasIndexQuery;
use Illuminate\Http\Request;

class ProductBrandController extends Controller
{
    use HasIndexQuery;

    public function index(Request $request)
    {
        return $this->paginatedResponse(ProductBrand::query(), $request, [
            'searchFields' => ['code', 'name'],
            'defaultSort' => 'code',
        ]);
    }

    public function store(Request $request)
    {
        if (!$request->code || str_starts_with($request->code, 'AUTOCODE')) {
            $request->merge(['code' => NumberSequenceService::generate('product-brand')]);
        }

        $request->validate(ProductBrand::storeRules());

        $brand = ProductBrand::create($request->only([
            'code', 'name', 'description', 'is_active',
        ]));

        return $this->storeResponse($brand);
    }

    public function show(ProductBrand $productBrand)
    {
        return $this->showResponse($productBrand);
    }

    public function update(Request $request, ProductBrand $productBrand)
    {
        $request->validate(ProductBrand::updateRules($productBrand->id));

        $productBrand->update($request->only([
            'name', 'description', 'is_active',
        ]));

        return $this->updateResponse($productBrand);
    }

    public function destroy(ProductBrand $productBrand)
    {
        $productBrand->delete();

        return $this->destroyResponse($productBrand);
    }

    public function all()
    {
        $brands = ProductBrand::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'uuid', 'code', 'name']);

        return response()->json([
            'status' => 'success',
            'data' => $brands,
        ]);
    }
}
