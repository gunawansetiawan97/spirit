<?php

namespace App\Services;

use App\Models\ProductUnit;
use App\Models\StockLedger;
use Carbon\Carbon;
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
     *   ref_type: string,        // e.g. 'StockAdjustment'
     *   ref_id: int,
     *   code: string|null,       // human-readable document code, e.g. 'SA-2026-0001'
     *   uom_id: int,             // unit used for input
     *   batch_number: string|null,
     *   qty_in: float,           // 0 if this is an out transaction
     *   qty_out: float,          // 0 if this is an in transaction
     *   unit_cost: float,        // optional, default 0
     * }
     */
    public static function record(array $data): StockLedger
    {
        return DB::transaction(function () use ($data) {
            $productUnit = ProductUnit::where('product_id', $data['product_id'])
                ->where('unit_id', $data['uom_id'])
                ->first();

            $conversion = $productUnit ? (float) $productUnit->conversion : 1.0;

            $qtyIn  = (float) ($data['qty_in']  ?? 0);
            $qtyOut = (float) ($data['qty_out'] ?? 0);

            $baseQtyIn  = $qtyIn  * $conversion;
            $baseQtyOut = $qtyOut * $conversion;

            $batchNumber = !empty($data['batch_number']) ? $data['batch_number'] : null;

            // Get last balance for this product + warehouse + batch (with row lock)
            $lastLedger = StockLedger::where('product_id', $data['product_id'])
                ->where('warehouse_id', $data['warehouse_id'])
                ->where('batch_number', $batchNumber)
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
                'branch_id'        => $data['branch_id'] ?? null,
                'ref_type'         => $data['ref_type'],
                'ref_id'           => $data['ref_id'],
                'code'             => $data['code'] ?? null,
                'uom_id'           => $data['uom_id'],
                'batch_number'     => $batchNumber,
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
     * Delete all stock ledger entries for a given ref.
     * Use this instead of reverse() when you want a clean undo (no reversal rows).
     */
    public static function deleteByRef(string $refType, int $refId): void
    {
        StockLedger::where('ref_type', $refType)
            ->where('ref_id', $refId)
            ->delete();
    }

    /**
     * Reverse all stock ledger entries for a given ref (append-style cancellation).
     * Creates mirror entries with in/out swapped. Kept for modules that need audit trail.
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
                    'code'             => $entry->code,
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
     * Get available stock qty for a product/warehouse/batch as of a given date.
     * Used to validate that stock won't go negative when approving outbound movements.
     */
    public static function checkAvailableQty(
        int $productId,
        int $warehouseId,
        ?string $batchNumber,
        Carbon $asOfDate
    ): float {
        $batchNumber = !empty($batchNumber) ? $batchNumber : null;

        $result = StockLedger::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('batch_number', $batchNumber)
            ->where('transaction_date', '<=', $asOfDate->toDateString())
            ->selectRaw('SUM(base_qty_in) - SUM(base_qty_out) AS available_qty')
            ->value('available_qty');

        return (float) ($result ?? 0);
    }

    /**
     * Get the minimum running balance for a product/warehouse/batch
     * at any point from $fromDate onwards (inclusive).
     *
     * Used to validate that disapproving an IN document won't cause
     * negative stock at any subsequent date.
     *
     * Algorithm:
     *   1. Compute cumulative balance strictly BEFORE fromDate.
     *   2. Walk all ledger rows from fromDate onwards in chronological order,
     *      tracking the running cumulative balance.
     *   3. Return the lowest value the running balance ever reaches.
     *
     * If min_balance >= qty_to_remove, disapproval is safe.
     */
    public static function checkMinBalanceFromDate(
        int $productId,
        int $warehouseId,
        ?string $batchNumber,
        string $fromDate   // YYYY-MM-DD
    ): float {
        $batchNumber = !empty($batchNumber) ? $batchNumber : null;

        // Cumulative balance of all rows BEFORE fromDate
        $balanceBefore = (float) StockLedger::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('batch_number', $batchNumber)
            ->where('transaction_date', '<', $fromDate)
            ->selectRaw('COALESCE(SUM(base_qty_in) - SUM(base_qty_out), 0) AS bal')
            ->value('bal');

        // All ledger rows from fromDate onwards, ordered chronologically
        $entries = StockLedger::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('batch_number', $batchNumber)
            ->where('transaction_date', '>=', $fromDate)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get(['base_qty_in', 'base_qty_out']);

        if ($entries->isEmpty()) {
            return $balanceBefore;
        }

        $minBalance = PHP_FLOAT_MAX;
        $running    = $balanceBefore;

        foreach ($entries as $entry) {
            $running   += (float) $entry->base_qty_in - (float) $entry->base_qty_out;
            $minBalance = min($minBalance, $running);
        }

        return $minBalance;
    }

    /**
     * Get available batch stocks for a product in a warehouse.
     * Returns batches with positive available qty.
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
