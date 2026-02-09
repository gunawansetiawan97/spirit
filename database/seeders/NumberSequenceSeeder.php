<?php

namespace Database\Seeders;

use App\Models\NumberSequence;
use Illuminate\Database\Seeder;

class NumberSequenceSeeder extends Seeder
{
    public function run(): void
    {
        $sequences = [
            // ===== MASTER DATA (no reset, global) =====
            [
                'code' => 'branch',
                'name' => 'Penomoran Cabang',
                'prefix' => 'CBG',
                'separator' => '-',
                'format' => '{prefix}-{seq}',
                'reset_type' => 'none',
                'scope_type' => 'global',
                'sequence_length' => 4,
            ],
            [
                'code' => 'product',
                'name' => 'Penomoran Produk',
                'prefix' => 'BRG',
                'separator' => '-',
                'format' => '{prefix}-{seq}',
                'reset_type' => 'none',
                'scope_type' => 'global',
                'sequence_length' => 5,
            ],
            [
                'code' => 'product-category',
                'name' => 'Penomoran Kategori Produk',
                'prefix' => 'KAT',
                'separator' => '-',
                'format' => '{prefix}-{seq}',
                'reset_type' => 'none',
                'scope_type' => 'global',
                'sequence_length' => 4,
            ],
            [
                'code' => 'product-brand',
                'name' => 'Penomoran Merk Produk',
                'prefix' => 'MRK',
                'separator' => '-',
                'format' => '{prefix}-{seq}',
                'reset_type' => 'none',
                'scope_type' => 'global',
                'sequence_length' => 4,
            ],
            [
                'code' => 'supplier',
                'name' => 'Penomoran Supplier',
                'prefix' => 'SUP',
                'separator' => '-',
                'format' => '{prefix}-{seq}',
                'reset_type' => 'none',
                'scope_type' => 'global',
                'sequence_length' => 5,
            ],
            [
                'code' => 'customer',
                'name' => 'Penomoran Customer',
                'prefix' => 'CUS',
                'separator' => '-',
                'format' => '{prefix}-{seq}',
                'reset_type' => 'none',
                'scope_type' => 'global',
                'sequence_length' => 5,
            ],

            // ===== TRANSAKSI PEMBELIAN (monthly, per branch) =====
            [
                'code' => 'purchase-order',
                'name' => 'Penomoran Purchase Order',
                'prefix' => 'PO',
                'separator' => '/',
                'format' => '{prefix}{sep}{branch}{sep}{YYYY}{sep}{MM}{sep}{seq}',
                'reset_type' => 'monthly',
                'scope_type' => 'branch',
                'sequence_length' => 4,
            ],
            [
                'code' => 'purchase-receive',
                'name' => 'Penomoran Penerimaan Barang',
                'prefix' => 'GRN',
                'separator' => '/',
                'format' => '{prefix}{sep}{branch}{sep}{YYYY}{sep}{MM}{sep}{seq}',
                'reset_type' => 'monthly',
                'scope_type' => 'branch',
                'sequence_length' => 4,
            ],
            [
                'code' => 'purchase-invoice',
                'name' => 'Penomoran Invoice Pembelian',
                'prefix' => 'APV',
                'separator' => '/',
                'format' => '{prefix}{sep}{branch}{sep}{YYYY}{sep}{MM}{sep}{seq}',
                'reset_type' => 'monthly',
                'scope_type' => 'branch',
                'sequence_length' => 4,
            ],

            // ===== TRANSAKSI PENJUALAN (monthly, per branch) =====
            [
                'code' => 'sales-order',
                'name' => 'Penomoran Sales Order',
                'prefix' => 'SO',
                'separator' => '/',
                'format' => '{prefix}{sep}{branch}{sep}{YYYY}{sep}{MM}{sep}{seq}',
                'reset_type' => 'monthly',
                'scope_type' => 'branch',
                'sequence_length' => 4,
            ],
            [
                'code' => 'sales-delivery',
                'name' => 'Penomoran Pengiriman Barang',
                'prefix' => 'DO',
                'separator' => '/',
                'format' => '{prefix}{sep}{branch}{sep}{YYYY}{sep}{MM}{sep}{seq}',
                'reset_type' => 'monthly',
                'scope_type' => 'branch',
                'sequence_length' => 4,
            ],
            [
                'code' => 'sales-invoice',
                'name' => 'Penomoran Invoice Penjualan',
                'prefix' => 'INV',
                'separator' => '/',
                'format' => '{prefix}{sep}{branch}{sep}{YYYY}{sep}{MM}{sep}{seq}',
                'reset_type' => 'monthly',
                'scope_type' => 'branch',
                'sequence_length' => 4,
            ],

            // ===== INVENTORY (monthly, per branch) =====
            [
                'code' => 'stock-transfer',
                'name' => 'Penomoran Transfer Stok',
                'prefix' => 'TRF',
                'separator' => '/',
                'format' => '{prefix}{sep}{branch}{sep}{YYYY}{sep}{MM}{sep}{seq}',
                'reset_type' => 'monthly',
                'scope_type' => 'branch',
                'sequence_length' => 4,
            ],
            [
                'code' => 'stock-adjustment',
                'name' => 'Penomoran Penyesuaian Stok',
                'prefix' => 'ADJ',
                'separator' => '/',
                'format' => '{prefix}{sep}{branch}{sep}{YYYY}{sep}{MM}{sep}{seq}',
                'reset_type' => 'monthly',
                'scope_type' => 'branch',
                'sequence_length' => 4,
            ],

            // ===== ACCOUNTING (monthly, per branch) =====
            [
                'code' => 'journal-entry',
                'name' => 'Penomoran Jurnal',
                'prefix' => 'JRN',
                'separator' => '/',
                'format' => '{prefix}{sep}{branch}{sep}{YYYY}{sep}{MM}{sep}{seq}',
                'reset_type' => 'monthly',
                'scope_type' => 'branch',
                'sequence_length' => 4,
            ],

            // ===== TRANSAKSI YEARLY (yearly, per branch) =====
            [
                'code' => 'payment-out',
                'name' => 'Penomoran Pembayaran Keluar',
                'prefix' => 'PAY',
                'separator' => '/',
                'format' => '{prefix}{sep}{branch}{sep}{YYYY}{sep}{seq}',
                'reset_type' => 'yearly',
                'scope_type' => 'branch',
                'sequence_length' => 5,
            ],
            [
                'code' => 'payment-in',
                'name' => 'Penomoran Pembayaran Masuk',
                'prefix' => 'RCV',
                'separator' => '/',
                'format' => '{prefix}{sep}{branch}{sep}{YYYY}{sep}{seq}',
                'reset_type' => 'yearly',
                'scope_type' => 'branch',
                'sequence_length' => 5,
            ],

            // ===== TRANSAKSI DAILY (daily, per branch) =====
            [
                'code' => 'cash-receipt',
                'name' => 'Penomoran Kwitansi',
                'prefix' => 'KWT',
                'separator' => '/',
                'format' => '{prefix}{sep}{branch}{sep}{YYYY}{sep}{MM}{sep}{DD}{sep}{seq}',
                'reset_type' => 'daily',
                'scope_type' => 'branch',
                'sequence_length' => 3,
            ],

            // ===== GLOBAL MONTHLY (monthly, global) =====
            [
                'code' => 'coa',
                'name' => 'Penomoran Chart of Account',
                'prefix' => 'COA',
                'separator' => '.',
                'format' => '{prefix}{sep}{YYYY}{sep}{seq}',
                'reset_type' => 'yearly',
                'scope_type' => 'global',
                'sequence_length' => 4,
            ],
        ];

        foreach ($sequences as $data) {
            NumberSequence::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
        }
    }
}
