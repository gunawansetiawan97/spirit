<?php

namespace App\Services;

use App\Models\ProductUnit;
use App\Models\StockLedger;
use Illuminate\Support\Facades\DB;

class StockLedgerService
{
    /**
     * Record a single stock movement in the stock ledger.
     *
     * @param array $data {
     *   transaction_date: string|Carbon,
     *   product_id: int,
     *   warehouse_id: int,
     *   ref_type: string,   // e.g. 'StockAdjustment'
     *   ref_id: int,
     *   uom_id: int,        // unit used for input
     *   batch_number: string|null,
     *   qty_in: float,      // 0 if this is an out transaction
     *   qty_out: float,     // 0 if this is an in transaction
     *   unit_cost: float,   // optional, default 0
     * }
     */
    public static function record(array $data): StockLedger
    {
        return DB::transaction(function () use ($data) {
            // Get conversion factor from product_units
            $productUnit = ProductUnit::where('product_id', $data['product_id'])
                ->where('unit_id', $data['uom_id'])
                ->first();

            $conversion = $productUnit ? (float) $productUnit->conversion : 1.0;

            $qtyIn  = (float) ($data['qty_in']  ?? 0);
            $qtyOut = (float) ($data['qty_out'] ?? 0);

            $baseQtyIn  = $qtyIn  * $conversion;
            $baseQtyOut = $qtyOut * $conversion;

            // Get last balance for this product + warehouse + batch (with row lock)
            $lastLedger = StockLedger::where('product_id', $data['product_id'])
                ->where('warehouse_id', $data['warehouse_id'])
                ->where('batch_number', $data['batch_number'] ?? null)
                ->lockForUpdate()
                ->latest('id')
                ->first();

            $previousBalance = $lastLedger ? (float) $lastLedger->base_balance : 0.0;
            $newBalance      = $previousBalance + $baseQtyIn - $baseQtyOut;

            $unitCost   = (float) ($data['unit_cost'] ?? 0);
            $totalValue = $baseQtyIn * $unitCost;

            return StockLedger::create([
                'transaction_date' => $data['transaction_date'],
                'product_id'       => $data['product_id'],
                'warehouse_id'     => $data['warehouse_id'],
                'ref_type'         => $data['ref_type'],
                'ref_id'           => $data['ref_id'],
                'uom_id'           => $data['uom_id'],
                'batch_number'     => $data['batch_number'] ?? null,
                'qty_in'           => $qtyIn,
                'qty_out'          => $qtyOut,
                'base_qty_in'      => $baseQtyIn,
                'base_qty_out'     => $baseQtyOut,
                'base_balance'     => $newBalance,
                'unit_cost'        => $unitCost,
                'total_value'      => $totalValue,
            ]);
        });
    }

    /**
     * Record multiple stock movements at once (wrapped in one transaction).
     */
    public static function recordBatch(array $items): void
    {
        DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                static::record($item);
            }
        });
    }

    /**
     * Reverse all stock ledger entries for a given ref (cancellation).
     * Creates mirror entries with in/out swapped.
     */
    public static function reverse(string $refType, int $refId): void
    {
        $entries = StockLedger::where('ref_type', $refType)
            ->where('ref_id', $refId)
            ->get();

        DB::transaction(function () use ($entries, $refType, $refId) {
            foreach ($entries as $entry) {
                static::record([
                    'transaction_date' => now(),
                    'product_id'       => $entry->product_id,
                    'warehouse_id'     => $entry->warehouse_id,
                    'ref_type'         => $refType . '/Reversal',
                    'ref_id'           => $refId,
                    'uom_id'           => $entry->uom_id,
                    'batch_number'     => $entry->batch_number,
                    'qty_in'           => (float) $entry->qty_out,
                    'qty_out'          => (float) $entry->qty_in,
                    'unit_cost'        => (float) $entry->unit_cost,
                ]);
            }
        });
    }

    /**
     * Get available batch stocks for a product in a warehouse.
     * Returns batches with positive base_balance.
     */
    public static function getAvailableBatches(int $productId, int $warehouseId): array
    {
        return StockLedger::selectRaw('
                batch_number,
                SUM(base_qty_in) - SUM(base_qty_out) AS available_qty
            ')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->groupBy('batch_number')
            ->havingRaw('SUM(base_qty_in) - SUM(base_qty_out) > 0')
            ->orderBy('batch_number')
            ->get()
            ->toArray();
    }
}
