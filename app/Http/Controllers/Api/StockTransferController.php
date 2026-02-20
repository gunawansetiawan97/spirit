<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductUnit;
use App\Models\StockTransfer;
use App\Services\NumberSequenceService;
use App\Services\StockLedgerService;
use App\Traits\HasIndexQuery;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    use HasIndexQuery;

    public function index(Request $request)
    {
        return $this->paginatedResponse(
            StockTransfer::with(['fromWarehouse:id,code,name', 'toWarehouse:id,code,name']),
            $request,
            [
                'searchFields'     => ['code', 'description'],
                'filterFields'     => ['from_warehouse_id', 'to_warehouse_id', 'status'],
                'defaultSort'      => 'date',
                'defaultDirection' => 'desc',
            ]
        );
    }

    public function store(Request $request)
    {
        $request->validate($this->detailRules());
        $request->validate(['code' => 'required|string|max:50|unique:stock_transfers,code']);

        return DB::transaction(function () use ($request) {
            $transfer = StockTransfer::create([
                'code'               => $request->code,
                'date'               => $request->date,
                'from_warehouse_id'  => $request->from_warehouse_id,
                'to_warehouse_id'    => $request->to_warehouse_id,
                'description'        => $request->description,
                'status'             => 'draft',
            ]);

            $this->saveDetails($transfer, $request->details ?? []);

            $transfer->load([
                'fromWarehouse:id,code,name',
                'toWarehouse:id,code,name',
                'details.product:id,code,name',
                'details.unit:id,code,name',
            ]);

            return $this->storeResponse($transfer);
        });
    }

    public function show(StockTransfer $stockTransfer)
    {
        return $this->showResponse($stockTransfer, [
            'fromWarehouse:id,uuid,code,name',
            'toWarehouse:id,uuid,code,name',
            'details.product:id,uuid,code,name',
            'details.product.units.unit:id,uuid,code,name',
            'details.unit:id,uuid,code,name',
        ]);
    }

    public function update(Request $request, StockTransfer $stockTransfer)
    {
        if ($stockTransfer->status !== 'draft') {
            return response()->json(['message' => 'Dokumen yang sudah diapprove tidak dapat diubah.'], 422);
        }

        $request->validate($this->detailRules());

        return DB::transaction(function () use ($request, $stockTransfer) {
            $stockTransfer->update([
                'date'              => $request->date,
                'from_warehouse_id' => $request->from_warehouse_id,
                'to_warehouse_id'   => $request->to_warehouse_id,
                'description'       => $request->description,
            ]);

            $oldDetails = $stockTransfer->details()
                ->with('product:id,name', 'unit:id,code')
                ->get();
            $oldRows = $this->formatDetailRows($oldDetails);

            $stockTransfer->details()->delete();
            $this->saveDetails($stockTransfer, $request->details ?? []);

            $newDetails = $stockTransfer->details()
                ->with('product:id,name', 'unit:id,code')
                ->get();
            $newRows = $this->formatDetailRows($newDetails);

            $stockTransfer->logActivity('children_updated', [
                'old' => $oldRows,
                'new' => $newRows,
            ]);

            $stockTransfer->load([
                'fromWarehouse:id,code,name',
                'toWarehouse:id,code,name',
                'details.product:id,code,name',
                'details.unit:id,code,name',
            ]);

            return $this->updateResponse($stockTransfer);
        });
    }

    public function destroy(StockTransfer $stockTransfer)
    {
        if ($stockTransfer->status !== 'draft') {
            return response()->json(['message' => 'Dokumen yang sudah diapprove tidak dapat dihapus.'], 422);
        }

        $stockTransfer->delete();

        return $this->destroyResponse($stockTransfer);
    }

    public function approve(StockTransfer $stockTransfer)
    {
        if ($stockTransfer->status !== 'draft') {
            return response()->json(['message' => 'Dokumen sudah diapprove sebelumnya.'], 422);
        }

        $details = $stockTransfer->details()->with('product:id,name')->get();

        if ($details->isEmpty()) {
            return response()->json(['message' => 'Dokumen tidak memiliki detail barang.'], 422);
        }

        // Stock availability check — source warehouse must have enough stock
        foreach ($details as $detail) {
            $productUnit = ProductUnit::where('product_id', $detail->product_id)
                ->where('unit_id', $detail->unit_id)
                ->first();
            $conversion      = $productUnit ? (float) $productUnit->conversion : 1.0;
            $requiredBaseQty = (float) $detail->qty * $conversion;

            $available = StockLedgerService::checkAvailableQty(
                $detail->product_id,
                $stockTransfer->from_warehouse_id,
                $detail->batch_number,
                Carbon::parse($stockTransfer->date)
            );

            if ($available < $requiredBaseQty) {
                $productName = $detail->product?->name ?? "ID #{$detail->product_id}";
                $batchInfo   = $detail->batch_number ? " (batch: {$detail->batch_number})" : '';
                return response()->json([
                    'message' => "Stok tidak mencukupi untuk {$productName}{$batchInfo}. "
                               . "Tersedia: " . number_format($available, 4, ',', '.') . ", "
                               . "dibutuhkan: " . number_format($requiredBaseQty, 4, ',', '.') . ".",
                    'errors'  => ['stock' => ['Stok tidak mencukupi.']],
                ], 422);
            }
        }

        return DB::transaction(function () use ($stockTransfer, $details) {
            $isDraftCode = str_starts_with($stockTransfer->code, 'AUTOCODE/')
                        || str_starts_with($stockTransfer->code, 'DRAFT-');
            $realCode = $isDraftCode
                ? NumberSequenceService::generate('stock-transfer')
                : $stockTransfer->code;

            StockLedgerService::deleteByRef('StockTransfer', $stockTransfer->id);

            foreach ($details as $detail) {
                $qty = (float) $detail->qty;

                // OUT from source warehouse
                StockLedgerService::record([
                    'transaction_date' => $stockTransfer->date,
                    'product_id'       => $detail->product_id,
                    'warehouse_id'     => $stockTransfer->from_warehouse_id,
                    'branch_id'        => $stockTransfer->branch_id,
                    'ref_type'         => 'StockTransfer',
                    'ref_id'           => $stockTransfer->id,
                    'code'             => $realCode,
                    'uom_id'           => $detail->unit_id,
                    'batch_number'     => $detail->batch_number,
                    'qty_in'           => 0,
                    'qty_out'          => $qty,
                    'unit_cost'        => (float) ($detail->unit_cost ?? 0),
                ]);

                // IN to destination warehouse
                StockLedgerService::record([
                    'transaction_date' => $stockTransfer->date,
                    'product_id'       => $detail->product_id,
                    'warehouse_id'     => $stockTransfer->to_warehouse_id,
                    'branch_id'        => $stockTransfer->branch_id,
                    'ref_type'         => 'StockTransfer',
                    'ref_id'           => $stockTransfer->id,
                    'code'             => $realCode,
                    'uom_id'           => $detail->unit_id,
                    'batch_number'     => $detail->batch_number,
                    'qty_in'           => $qty,
                    'qty_out'          => 0,
                    'unit_cost'        => (float) ($detail->unit_cost ?? 0),
                ]);
            }

            $stockTransfer->update([
                'code'   => $realCode,
                'status' => 'posted',
            ]);

            $stockTransfer->approved_by = Auth::id();
            $stockTransfer->approved_at = now();
            $stockTransfer->saveQuietly();
            $stockTransfer->logActivity('approved');

            return $this->updateResponse($stockTransfer->fresh([
                'fromWarehouse:id,code,name',
                'toWarehouse:id,code,name',
            ]));
        });
    }

    public function disapprove(StockTransfer $stockTransfer)
    {
        if ($stockTransfer->status !== 'posted') {
            return response()->json(['message' => 'Hanya dokumen yang sudah diapprove yang dapat di-disapprove.'], 422);
        }

        $details  = $stockTransfer->details()->with('product:id,name')->get();
        $fromDate = Carbon::parse($stockTransfer->date)->toDateString();

        // Ensure removing the IN entries (to_warehouse) won't cause negative stock
        foreach ($details as $detail) {
            $productUnit = ProductUnit::where('product_id', $detail->product_id)
                ->where('unit_id', $detail->unit_id)
                ->first();
            $conversion      = $productUnit ? (float) $productUnit->conversion : 1.0;
            $baseQtyToRemove = (float) $detail->qty * $conversion;

            $minBalance = StockLedgerService::checkMinBalanceFromDate(
                $detail->product_id,
                $stockTransfer->to_warehouse_id,
                $detail->batch_number,
                $fromDate
            );

            if ($minBalance < $baseQtyToRemove) {
                $productName = $detail->product?->name ?? "ID #{$detail->product_id}";
                $batchInfo   = $detail->batch_number ? " (batch: {$detail->batch_number})" : '';
                return response()->json([
                    'message' => "Tidak dapat membatalkan: stok {$productName}{$batchInfo} di gudang tujuan sudah digunakan. "
                               . "Saldo minimum: " . number_format($minBalance, 4, ',', '.') . ", "
                               . "akan dihapus: " . number_format($baseQtyToRemove, 4, ',', '.') . ".",
                    'errors'  => ['stock' => ['Stok sudah terpakai oleh transaksi lain.']],
                ], 422);
            }
        }

        DB::transaction(function () use ($stockTransfer) {
            StockLedgerService::deleteByRef('StockTransfer', $stockTransfer->id);
            $stockTransfer->update(['status' => 'draft']);

            $stockTransfer->approved_by = null;
            $stockTransfer->approved_at = null;
            $stockTransfer->saveQuietly();
            $stockTransfer->logActivity('unapproved');
        });

        return $this->updateResponse($stockTransfer->fresh([
            'fromWarehouse:id,code,name',
            'toWarehouse:id,code,name',
        ]));
    }

    // -------------------------------------------------------------------------

    private function detailRules(): array
    {
        return [
            'date'               => 'required|date',
            'from_warehouse_id'  => 'required|exists:warehouses,id',
            'to_warehouse_id'    => 'required|exists:warehouses,id|different:from_warehouse_id',
            'description'        => 'nullable|string',
            'details'            => 'required|array|min:1',
            'details.*.product_id'   => 'required|exists:products,id',
            'details.*.batch_number' => 'nullable|string|max:50',
            'details.*.unit_id'      => 'required|exists:units,id',
            'details.*.qty'          => 'required|numeric|min:0.0001',
            'details.*.unit_cost'    => 'nullable|numeric|min:0',
            'details.*.description'  => 'nullable|string',
        ];
    }

    private function formatDetailRows($details): array
    {
        return $details->map(fn($d) => [
            'product'  => $d->product?->name ?? '-',
            'qty'      => (float) $d->qty,
            'unit'     => $d->unit?->code ?? '-',
            'batch'    => $d->batch_number ?: null,
        ])->values()->toArray();
    }

    private function saveDetails(StockTransfer $transfer, array $details): void
    {
        foreach ($details as $row) {
            $transfer->details()->create([
                'product_id'   => $row['product_id'],
                'batch_number' => !empty($row['batch_number']) ? $row['batch_number'] : null,
                'unit_id'      => $row['unit_id'],
                'qty'          => $row['qty'],
                'unit_cost'    => $row['unit_cost'] ?? 0,
                'description'  => $row['description'] ?? null,
            ]);
        }
    }
}
