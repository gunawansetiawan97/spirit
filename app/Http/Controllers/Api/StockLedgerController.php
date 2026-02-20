<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StockLedgerService;
use Illuminate\Http\Request;

class StockLedgerController extends Controller
{
    /**
     * Get available batches for a product in a warehouse (for "Keluar" browse).
     */
    public function batches(Request $request)
    {
        $request->validate([
            'product_id'   => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
        ]);

        $batches = StockLedgerService::getAvailableBatches(
            $request->product_id,
            $request->warehouse_id
        );

        return response()->json([
            'status' => 'success',
            'data'   => $batches,
        ]);
    }
}
