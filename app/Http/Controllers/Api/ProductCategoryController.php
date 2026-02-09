<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Services\NumberSequenceService;
use App\Traits\HasIndexQuery;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    use HasIndexQuery;

    public function index(Request $request)
    {
        $query = ProductCategory::with([
            'coaInventory:id,uuid,code,name',
            'coaCogs:id,uuid,code,name',
            'coaSales:id,uuid,code,name',
        ]);

        return $this->paginatedResponse($query, $request, [
            'searchFields' => ['code', 'name'],
            'defaultSort' => 'code',
        ]);
    }

    public function store(Request $request)
    {
        if (!$request->code || str_starts_with($request->code, 'AUTOCODE')) {
            $request->merge(['code' => NumberSequenceService::generate('product-category')]);
        }

        $request->validate(ProductCategory::storeRules());

        $category = ProductCategory::create($request->only([
            'code', 'name', 'description',
            'coa_inventory_id', 'coa_cogs_id', 'coa_sales_id',
            'is_active',
        ]));

        return $this->storeResponse($category);
    }

    public function show(ProductCategory $productCategory)
    {
        return $this->showResponse($productCategory, [
            'coaInventory:id,uuid,code,name',
            'coaCogs:id,uuid,code,name',
            'coaSales:id,uuid,code,name',
        ]);
    }

    public function update(Request $request, ProductCategory $productCategory)
    {
        $request->validate(ProductCategory::updateRules($productCategory->id));

        $productCategory->update($request->only([
            'name', 'description',
            'coa_inventory_id', 'coa_cogs_id', 'coa_sales_id',
            'is_active',
        ]));

        return $this->updateResponse($productCategory);
    }

    public function destroy(ProductCategory $productCategory)
    {
        $productCategory->delete();

        return $this->destroyResponse($productCategory);
    }

    public function all()
    {
        $categories = ProductCategory::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'uuid', 'code', 'name']);

        return response()->json([
            'status' => 'success',
            'data' => $categories,
        ]);
    }
}
