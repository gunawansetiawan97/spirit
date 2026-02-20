<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            [
                'code' => 'dashboard',
                'name' => 'Dashboard',
                'icon' => 'HomeIcon',
                'route' => '/dashboard',
                'type' => 'menu',
                'sort_order' => 1,
            ],
            [
                'code' => 'master',
                'name' => 'Master Data',
                'icon' => 'DatabaseIcon',
                'route' => null,
                'type' => 'menu',
                'sort_order' => 2,
                'children' => [
                    ['code' => 'master.branch', 'name' => 'Cabang', 'route' => '/master/branch', 'sort_order' => 1],
                    ['code' => 'master.role', 'name' => 'Role', 'route' => '/master/role', 'sort_order' => 2],
                    ['code' => 'master.user', 'name' => 'User', 'route' => '/master/user', 'sort_order' => 3],
                    ['code' => 'master.permission', 'name' => 'Hak Akses', 'route' => '/master/permission', 'sort_order' => 4],
                    ['code' => 'master.number_sequence', 'name' => 'Penomoran', 'route' => '/master/number-sequence', 'sort_order' => 5],
                    [
                        'code' => 'master.accounting',
                        'name' => 'Akuntansi',
                        'route' => null,
                        'sort_order' => 6,
                        'children' => [
                            ['code' => 'master.accounting.account_type', 'name' => 'Tipe Akun', 'route' => '/master/account-type', 'sort_order' => 1],
                            ['code' => 'master.accounting.account_group', 'name' => 'Group Akun', 'route' => '/master/account-group', 'sort_order' => 2],
                            ['code' => 'master.accounting.coa', 'name' => 'Chart of Account', 'route' => '/master/coa', 'sort_order' => 3],
                            ['code' => 'master.accounting.coa_mapping', 'name' => 'COA Mapping', 'route' => '/master/coa-mapping', 'sort_order' => 4],
                        ]
                    ],
                    [
                        'code' => 'master.product',
                        'name' => 'Produk',
                        'route' => null,
                        'sort_order' => 7,
                        'children' => [
                            ['code' => 'master.product.unit', 'name' => 'Unit', 'route' => '/master/unit', 'sort_order' => 1],
                            ['code' => 'master.product.product_category', 'name' => 'Kategori Produk', 'route' => '/master/product-category', 'sort_order' => 2],
                            ['code' => 'master.product.product_brand', 'name' => 'Merk Produk', 'route' => '/master/product-brand', 'sort_order' => 3],
                            ['code' => 'master.product.product', 'name' => 'Produk', 'route' => '/master/product', 'sort_order' => 4],
                        ]
                    ],
                    [
                        'code' => 'master.inventory',
                        'name' => 'Inventori',
                        'route' => null,
                        'sort_order' => 8,
                        'children' => [
                            ['code' => 'master.inventory.warehouse', 'name' => 'Gudang', 'route' => '/master/warehouse', 'sort_order' => 1],
                            ['code' => 'master.inventory.adjustment_type', 'name' => 'Tipe Penyesuaian', 'route' => '/master/adjustment-type', 'sort_order' => 2],
                        ]
                    ],
                ],
            ],
            [
                'code' => 'transaction',
                'name' => 'Transaksi',
                'icon' => 'ClipboardIcon',
                'route' => null,
                'type' => 'menu',
                'sort_order' => 3,
                'children' => [
                    ['code' => 'transaction.stock_adjustment', 'name' => 'Penyesuaian Stok', 'route' => '/transaction/stock-adjustment', 'sort_order' => 1],
                    ['code' => 'transaction.stock_transfer', 'name' => 'Transfer Stok', 'route' => '/transaction/stock-transfer', 'sort_order' => 2],
                ],
            ],
            [
                'code' => 'purchasing',
                'name' => 'Pembelian',
                'icon' => 'ShoppingCartIcon',
                'route' => null,
                'type' => 'menu',
                'sort_order' => 3,
                'children' => [
                    ['code' => 'purchasing.supplier', 'name' => 'Supplier', 'route' => '/purchasing/supplier', 'sort_order' => 1],
                    ['code' => 'purchasing.po', 'name' => 'Purchase Order', 'route' => '/purchasing/po', 'sort_order' => 2],
                    ['code' => 'purchasing.receive', 'name' => 'Penerimaan Barang', 'route' => '/purchasing/receive', 'sort_order' => 3],
                    ['code' => 'purchasing.invoice', 'name' => 'Invoice Pembelian', 'route' => '/purchasing/invoice', 'sort_order' => 4],
                ],
            ],
            [
                'code' => 'sales',
                'name' => 'Penjualan',
                'icon' => 'CurrencyDollarIcon',
                'route' => null,
                'type' => 'menu',
                'sort_order' => 4,
                'children' => [
                    ['code' => 'sales.customer', 'name' => 'Customer', 'route' => '/sales/customer', 'sort_order' => 1],
                    ['code' => 'sales.so', 'name' => 'Sales Order', 'route' => '/sales/so', 'sort_order' => 2],
                    ['code' => 'sales.delivery', 'name' => 'Pengiriman Barang', 'route' => '/sales/delivery', 'sort_order' => 3],
                    ['code' => 'sales.invoice', 'name' => 'Invoice Penjualan', 'route' => '/sales/invoice', 'sort_order' => 4],
                ],
            ],
            [
                'code' => 'inventory',
                'name' => 'Warehouse',
                'icon' => 'CubeIcon',
                'route' => null,
                'type' => 'menu',
                'sort_order' => 5,
                'children' => [
                    ['code' => 'inventory.item', 'name' => 'Item', 'route' => '/inventory/item', 'sort_order' => 1],
                    ['code' => 'inventory.category', 'name' => 'Kategori', 'route' => '/inventory/category', 'sort_order' => 2],
                    ['code' => 'inventory.warehouse', 'name' => 'Gudang', 'route' => '/inventory/warehouse', 'sort_order' => 3],
                    ['code' => 'inventory.stock', 'name' => 'Stok', 'route' => '/inventory/stock', 'sort_order' => 4],
                    ['code' => 'inventory.transfer', 'name' => 'Transfer Stok', 'route' => '/inventory/transfer', 'sort_order' => 5],
                    ['code' => 'inventory.adjustment', 'name' => 'Penyesuaian Stok', 'route' => '/inventory/adjustment', 'sort_order' => 6],
                ],
            ],
            [
                'code' => 'accounting',
                'name' => 'Accounting',
                'icon' => 'CalculatorIcon',
                'route' => null,
                'type' => 'menu',
                'sort_order' => 6,
                'children' => [
                    ['code' => 'accounting.coa', 'name' => 'Chart of Account', 'route' => '/accounting/coa', 'sort_order' => 1],
                    ['code' => 'accounting.journal', 'name' => 'Jurnal', 'route' => '/accounting/journal', 'sort_order' => 2],
                    ['code' => 'accounting.ap', 'name' => 'Hutang', 'route' => '/accounting/ap', 'sort_order' => 3],
                    ['code' => 'accounting.ar', 'name' => 'Piutang', 'route' => '/accounting/ar', 'sort_order' => 4],
                ],
            ],
            [
                'code' => 'report',
                'name' => 'Report',
                'icon' => 'ChartBarIcon',
                'route' => null,
                'type' => 'menu',
                'sort_order' => 7,
                'children' => [
                    ['code' => 'report.sales', 'name' => 'Laporan Penjualan', 'route' => '/report/sales', 'sort_order' => 1],
                    ['code' => 'report.purchasing', 'name' => 'Laporan Pembelian', 'route' => '/report/purchasing', 'sort_order' => 2],
                    ['code' => 'report.inventory', 'name' => 'Laporan Stok', 'route' => '/report/inventory', 'sort_order' => 3],
                    ['code' => 'report.financial', 'name' => 'Laporan Keuangan', 'route' => '/report/financial', 'sort_order' => 4],
                ],
            ],
        ];

        foreach ($menus as $menu) {
            $this->createPermission($menu);
        }
    }

    private function createPermission(array $data, ?int $parentId = null): void
    {
        $children = $data['children'] ?? [];
        unset($data['children']);

        $data['parent_id'] = $parentId;
        $data['type'] = $data['type'] ?? 'submenu';

        $permission = Permission::updateOrCreate(
            ['code' => $data['code']],
            $data
        );

        foreach ($children as $child) {
            $this->createPermission($child, $permission->id);
        }
    }
}
