<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentDetail;
use App\Services\NumberSequenceService;
use App\Services\StockLedgerService;
use App\Traits\HasIndexQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockAdjustmentController extends Controller
{
    use HasIndexQuery;

    public function index(Request $request)
    {
        return $this->paginatedResponse(
            StockAdjustment::with(['warehouse:id,code,name', 'adjustmentType:id,code,name']),
            $request,
            [
                'searchFields' => ['code', 'description'],
                'filterFields' => ['warehouse_id', 'adjustment_type_id', 'status'],
                'defaultSort'  => '-date',
            ]
        );
    }

    public function store(Request $request)
    {
        $request->merge([
            'code' => NumberSequenceService::generate('stock-adjustment'),
        ]);

        $request->validate(StockAdjustment::storeRules());

        return DB::transaction(function () use ($request) {
            $adjustment = StockAdjustment::create([
                'code'               => $request->code,
                'date'               => $request->date,
                'warehouse_id'       => $request->warehouse_id,
                'adjustment_type_id' => $request->adjustment_type_id,
                'description'        => $request->description,
                'status'             => 'posted',
            ]);

            $this->syncDetails($adjustment, $request->details ?? []);

            $adjustment->load([
                'warehouse:id,code,name',
                'adjustmentType:id,code,name',
                'details.product:id,code,name',
                'details.unit:id,code,name',
            ]);

            return $this->storeResponse($adjustment);
        });
    }

    public function show(StockAdjustment $stockAdjustment)
    {
        return $this->showResponse($stockAdjustment, [
            'warehouse:id,uuid,code,name',
            'adjustmentType:id,uuid,code,name',
            'details.product:id,uuid,code,name',
            'details.product.units.unit:id,uuid,code,name',
            'details.unit:id,uuid,code,name',
        ]);
    }

    public function update(Request $request, StockAdjustment $stockAdjustment)
    {
        $request->validate(StockAdjustment::updateRules($stockAdjustment->id));

        return DB::transaction(function () use ($request, $stockAdjustment) {
            // Reverse existing ledger entries
            StockLedgerService::reverse('StockAdjustment', $stockAdjustment->id);

            $stockAdjustment->update([
                'date'               => $request->date,
                'warehouse_id'       => $request->warehouse_id,
                'adjustment_type_id' => $request->adjustment_type_id,
                'description'        => $request->description,
            ]);

            // Remove old details and re-record
            $stockAdjustment->details()->delete();
            $this->syncDetails($stockAdjustment, $request->details ?? []);

            $stockAdjustment->load([
                'warehouse:id,code,name',
                'adjustmentType:id,code,name',
                'details.product:id,code,name',
                'details.unit:id,code,name',
            ]);

            return $this->updateResponse($stockAdjustment);
        });
    }

    public function destroy(StockAdjustment $stockAdjustment)
    {
        DB::transaction(function () use ($stockAdjustment) {
            StockLedgerService::reverse('StockAdjustment', $stockAdjustment->id);
            $stockAdjustment->delete();
        });

        return $this->destroyResponse($stockAdjustment);
    }

    // -------------------------------------------------------------------------

    /**
     * Create detail rows and record each to stock ledger.
     */
    private function syncDetails(StockAdjustment $adjustment, array $details): void
    {
        foreach ($details as $row) {
            $detail = $adjustment->details()->create([
                'product_id'   => $row['product_id'],
                'direction'    => $row['direction'],
                'batch_number' => $row['batch_number'] ?? null,
                'unit_id'      => $row['unit_id'],
                'qty'          => $row['qty'],
                'unit_cost'    => $row['unit_cost'] ?? 0,
                'description'  => $row['description'] ?? null,
            ]);

            StockLedgerService::record([
                'transaction_date' => $adjustment->date->startOfDay(),
                'product_id'       => $row['product_id'],
                'warehouse_id'     => $adjustment->warehouse_id,
                'ref_type'         => 'StockAdjustment',
                'ref_id'           => $adjustment->id,
                'uom_id'           => $row['unit_id'],
                'batch_number'     => $row['batch_number'] ?? null,
                'qty_in'           => $row['direction'] === 'in'  ? $row['qty'] : 0,
                'qty_out'          => $row['direction'] === 'out' ? $row['qty'] : 0,
                'unit_cost'        => $row['unit_cost'] ?? 0,
            ]);
        }
    }
}
