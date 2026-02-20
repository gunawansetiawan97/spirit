<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Unit;
use App\Services\NumberSequenceService;
use App\Traits\HasIndexQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    use HasIndexQuery;

    public function index(Request $request)
    {
        return $this->paginatedResponse(
            Product::with(['category:id,code,name', 'brand:id,code,name']),
            $request,
            [
                'searchFields' => ['code', 'name'],
                'filterFields' => ['product_category_id', 'product_brand_id', 'type'],
                'defaultSort' => 'code',
            ]
        );
    }

    public function store(Request $request)
    {
        if (!$request->code || str_starts_with($request->code, 'AUTOCODE')) {
            $request->merge(['code' => NumberSequenceService::generate('product')]);
        }

        $request->validate(Product::storeRules());

        $product = Product::create($request->only([
            'product_category_id', 'product_brand_id', 'code', 'name', 'type',
            'min_stock', 'max_stock', 'description',
            'coa_inventory_id', 'coa_cogs_id', 'coa_sales_id', 'is_active',
        ]));

        $unitChanges = $this->syncUnits($product, $request->units ?? []);
        $imageChanges = $this->storeImages($product, $request);

        // Log child record changes on parent (old/new format)
        $old = array_merge($unitChanges['old'] ?? [], $imageChanges['old'] ?? []);
        $new = array_merge($unitChanges['new'] ?? [], $imageChanges['new'] ?? []);
        if (!empty($old) || !empty($new)) {
            $product->logActivity('children_updated', ['old' => $old, 'new' => $new]);
        }

        $product->load(['category:id,code,name', 'brand:id,code,name', 'units.unit', 'images']);

        return $this->storeResponse($product);
    }

    public function show(Product $product)
    {
        return $this->showResponse($product, [
            'category:id,uuid,code,name',
            'brand:id,uuid,code,name',
            'units.unit:id,uuid,code,name',
            'images',
            'coaInventory:id,uuid,code,name',
            'coaCogs:id,uuid,code,name',
            'coaSales:id,uuid,code,name',
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $request->validate(Product::updateRules($product->id));

        $product->update($request->only([
            'product_category_id', 'product_brand_id', 'name', 'type',
            'min_stock', 'max_stock', 'description',
            'coa_inventory_id', 'coa_cogs_id', 'coa_sales_id', 'is_active',
        ]));

        $unitChanges = $this->syncUnits($product, $request->units ?? []);
        $imageChanges = $this->storeImages($product, $request);
        $deleteChanges = $this->deleteImages($request->deleted_images ?? []);

        // Log child record changes on parent (old/new format)
        $old = array_merge($unitChanges['old'] ?? [], $imageChanges['old'] ?? [], $deleteChanges['old'] ?? []);
        $new = array_merge($unitChanges['new'] ?? [], $imageChanges['new'] ?? [], $deleteChanges['new'] ?? []);
        if (!empty($old) || !empty($new)) {
            $product->logActivity('children_updated', ['old' => $old, 'new' => $new]);
        }

        $product->load(['category:id,code,name', 'brand:id,code,name', 'units.unit', 'images']);

        return $this->updateResponse($product);
    }

    public function destroy(Product $product)
    {
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $product->delete();

        return $this->destroyResponse($product);
    }

    public function all(Request $request)
    {
        $query = Product::where('is_active', true)->orderBy('name');

        if ($request->type) {
            $query->where('type', $request->type);
        }

        $products = $query->get(['id', 'uuid', 'code', 'name', 'type']);

        return response()->json([
            'status' => 'success',
            'data' => $products,
        ]);
    }

    /**
     * Sync product units with smart diff.
     * Returns ['old' => [...], 'new' => [...]] or null if no changes.
     */
    private function syncUnits(Product $product, array $units): ?array
    {
        $existing = $product->units()->with('unit:id,code,name')->get();
        $newUnitIds = collect($units)->pluck('unit_id')->filter()->values();

        $old = [];
        $new = [];

        // Delete removed units
        $toRemove = $existing->filter(fn ($pu) => !$newUnitIds->contains($pu->unit_id));
        foreach ($toRemove as $pu) {
            $name = $pu->unit?->name ?? "unit_id:{$pu->unit_id}";
            $old["Satuan {$name}"] = "konversi: {$pu->conversion}";
            $new["Satuan {$name}"] = null;
            $pu->forceDelete();
        }

        // Add or update units
        $addedUnitIds = [];
        foreach ($units as $unitData) {
            if (empty($unitData['unit_id'])) continue;

            $existingUnit = $existing->firstWhere('unit_id', $unitData['unit_id']);

            if ($existingUnit) {
                $name = $existingUnit->unit?->name ?? "unit_id:{$unitData['unit_id']}";
                $newConversion = (float) ($unitData['conversion'] ?? 1);
                $newIsBase = (bool) ($unitData['is_base_unit'] ?? false);

                if ((float) $existingUnit->conversion !== $newConversion) {
                    $old["Satuan {$name} (konversi)"] = (string) $existingUnit->conversion;
                    $new["Satuan {$name} (konversi)"] = (string) $newConversion;
                }
                if ((bool) $existingUnit->is_base_unit !== $newIsBase) {
                    $old["Satuan {$name} (satuan dasar)"] = $existingUnit->is_base_unit ? 'Ya' : 'Tidak';
                    $new["Satuan {$name} (satuan dasar)"] = $newIsBase ? 'Ya' : 'Tidak';
                }

                if ((float) $existingUnit->conversion !== $newConversion || (bool) $existingUnit->is_base_unit !== $newIsBase) {
                    $existingUnit->update([
                        'conversion' => $newConversion,
                        'is_base_unit' => $newIsBase,
                    ]);
                }
            } else {
                $product->units()->create([
                    'unit_id' => $unitData['unit_id'],
                    'conversion' => $unitData['conversion'] ?? 1,
                    'is_base_unit' => $unitData['is_base_unit'] ?? false,
                ]);
                $addedUnitIds[$unitData['unit_id']] = $unitData;
            }
        }

        // Resolve names for added units
        if (!empty($addedUnitIds)) {
            $unitNames = Unit::whereIn('id', array_keys($addedUnitIds))->pluck('name', 'id');
            foreach ($addedUnitIds as $unitId => $unitData) {
                $name = $unitNames[$unitId] ?? "unit_id:{$unitId}";
                $old["Satuan {$name}"] = null;
                $new["Satuan {$name}"] = "konversi: " . ($unitData['conversion'] ?? 1);
            }
        }

        if (empty($old) && empty($new)) {
            return null;
        }

        return ['old' => $old, 'new' => $new];
    }

    /**
     * Store uploaded images. Returns ['old' => [...], 'new' => [...]] or null.
     */
    private function storeImages(Product $product, Request $request): ?array
    {
        if (!$request->hasFile('images')) {
            return null;
        }

        $lastSort = $product->images()->max('sort_order') ?? 0;
        $old = [];
        $new = [];

        foreach ($request->file('images') as $file) {
            $path = $file->store('products', 'public');
            $product->images()->create([
                'image_path' => $path,
                'sort_order' => ++$lastSort,
            ]);
            $fileName = $file->getClientOriginalName();
            $old["Gambar {$fileName}"] = null;
            $new["Gambar {$fileName}"] = 'ditambah';
        }

        return ['old' => $old, 'new' => $new];
    }

    /**
     * Delete images by IDs. Returns ['old' => [...], 'new' => [...]] or null.
     */
    private function deleteImages(array $imageIds): ?array
    {
        if (empty($imageIds)) {
            return null;
        }

        $images = ProductImage::whereIn('id', $imageIds)->get();
        $old = [];
        $new = [];

        foreach ($images as $image) {
            Storage::disk('public')->delete($image->image_path);
            $fileName = basename($image->image_path);
            $old["Gambar {$fileName}"] = 'ada';
            $new["Gambar {$fileName}"] = null;
            $image->delete();
        }

        return empty($old) ? null : ['old' => $old, 'new' => $new];
    }
}
