<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

use App\Models\Warehouse;
use App\Models\InventoryItem;
use App\Models\InventoryBalance;
use App\Models\StockMovement;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Partner;
use App\Models\JobOrder;
use App\Models\JobOrderTask;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            /*
            |--------------------------------------------------------------------------
            | Warehouses
            |--------------------------------------------------------------------------
            */

            $rawWarehouse = Warehouse::create([
                'name' => 'Raw Material Warehouse',
                'code' => 'RAW',
            ]);

            $finishedWarehouse = Warehouse::create([
                'name' => 'Finished Goods Warehouse',
                'code' => 'FG',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Suppliers
            |--------------------------------------------------------------------------
            */

            $paperSupplier = Partner::create([
                'name' => 'Addis Paper Trading',
                'is_supplier' => true,
            ]);

            $inkSupplier = Partner::create([
                'name' => 'Nile Printing Supplies',
                'is_supplier' => true,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Customers
            |--------------------------------------------------------------------------
            */

            $coffeeClient = Partner::create([
                'name' => 'Hi Print',
                'is_customer' => true,
            ]);

            $soapClient = Partner::create([
                'name' => 'Hijra Bank',
                'is_customer' => true,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Inventory Items (Base Units Only)
            |--------------------------------------------------------------------------
            */

            $offsetPaper = InventoryItem::create([
                'name' => '70gsm White Offset Paper',
                'sku' => 'PAPER-70W',
                'unit' => 'Sheet',
                'conversion_factor' => 500, // Ream → Sheet
                'average_cost' => 0,
            ]);

            $ivoryBoard = InventoryItem::create([
                'name' => '250gsm Ivory Board',
                'sku' => 'BOARD-250I',
                'unit' => 'Sheet',
                'conversion_factor' => 250, // Bundle → Sheet
                'average_cost' => 0,
            ]);

            $ink = InventoryItem::create([
                'name' => 'CMYK Ink Set',
                'sku' => 'INK-CMYK',
                'unit' => 'Kg',
                'conversion_factor' => 1,
                'average_cost' => 0,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Purchase Order
            |--------------------------------------------------------------------------
            */

            $po = PurchaseOrder::create([
                'po_number' => 'PO-2024-001',
                'order_date' => now(),
                'partner_id' => $paperSupplier->id,
                'status' => 'approved',
            ]);

            $poItemPaper = PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'inventory_item_id' => $offsetPaper->id,
                'quantity' => 200, // 200 reams
                'unit_price' => 1600, // per ream
                'total' => 200 * 1600,
            ]);

            $poItemBoard = PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'inventory_item_id' => $ivoryBoard->id,
                'quantity' => 100, // bundles
                'unit_price' => 2200,
                'total' => 100 * 2200,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Goods Receipt (POSTED)
            |--------------------------------------------------------------------------
            */

            $gr = GoodsReceipt::create([
                'receipt_number' => 'GR-2024-001',
                'receipt_date' => now(),
                'purchase_order_id' => $po->id,
                'warehouse_id' => $rawWarehouse->id,
                'status' => 'posted',
                'posted_at' => now(),
            ]);

            GoodsReceiptItem::create([
                'goods_receipt_id' => $gr->id,
                'purchase_order_item_id' => $poItemPaper->id,
                'quantity_received' => 200,
            ]);

            GoodsReceiptItem::create([
                'goods_receipt_id' => $gr->id,
                'purchase_order_item_id' => $poItemBoard->id,
                'quantity_received' => 100,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Manually Trigger Inventory Service
            |--------------------------------------------------------------------------
            | If your boot logic is correct this may not be needed.
            | But if seeding bypasses events, call service directly.
            |--------------------------------------------------------------------------
            */

            app(\App\Services\InventoryService::class)
                ->receiveStock($offsetPaper->id, $rawWarehouse->id, 200 * 500, 1600 / 500, 'goods_receipt', $gr->id);

            app(\App\Services\InventoryService::class)
                ->receiveStock($ivoryBoard->id, $rawWarehouse->id, 100 * 250, 2200 / 250, 'goods_receipt', $gr->id);

            /*
            |--------------------------------------------------------------------------
            | Job Order
            |--------------------------------------------------------------------------
            */

            $job = JobOrder::create([
                'partner_id' => $coffeeClient->id,
                'job_order_number' => 'JOB-001',
                'job_type' => 'packages',
                'cost_calc_file' => 'calc.xlsx',
                'services' => [],
                'status' => 'production',
                'submission_date' => now(),
                'total_price' => 45000,
                'advance_amount' => 20000,
            ]);

            $task = JobOrderTask::create([
                'job_order_id' => $job->id,
                'name' => 'Print Coffee Packaging Boxes',
                'quantity' => 10000,
                'unit_cost' => 45000,
                'paper' => [
                    [
                        'inventory_item_id' => $offsetPaper->id,
                        'required_quantity' => 5000,
                        'reserve_quantity' => 200,
                        'base_unit' => 'Sheet',
                    ],
                    [
                        'inventory_item_id' => $ivoryBoard->id,
                        'required_quantity' => 2000,
                        'reserve_quantity' => 100,
                        'base_unit' => 'Sheet',
                    ],
                ],
            ]);

            /*
            |--------------------------------------------------------------------------
            | Trigger Consumption
            |--------------------------------------------------------------------------
            */

            app(\App\Services\InventoryService::class)
                ->consumeStock($offsetPaper->id, $rawWarehouse->id, 5200, 'job_order', $job->id);

            app(\App\Services\InventoryService::class)
                ->consumeStock($ivoryBoard->id, $rawWarehouse->id, 2100, 'job_order', $job->id);
        });
    }
}
