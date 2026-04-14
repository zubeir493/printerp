<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

use App\Models\Warehouse;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Partner;
use App\Models\JobOrder;
use App\Models\JobOrderTask;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Machine;
use App\Models\ProductionPlan;
use App\Models\ProductionPlanItem;
use App\Models\ProductionReport;
use App\Models\ProductionReportItem;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $rawWarehouse = Warehouse::create([
                'name' => 'Raw Material Warehouse',
                'code' => 'RAW',
            ]);

            $finishedWarehouse = Warehouse::create([
                'name' => 'Finished Goods Warehouse',
                'code' => 'FG',
            ]);

            $paperSupplier = Partner::create([
                'name' => 'Addis Paper Trading',
                'is_supplier' => true,
            ]);

            $inkSupplier = Partner::create([
                'name' => 'Nile Printing Supplies',
                'is_supplier' => true,
            ]);

            $coffeeClient = Partner::create([
                'name' => 'Hi Print',
                'is_customer' => true,
            ]);

            $soapClient = Partner::create([
                'name' => 'Hijra Bank',
                'is_customer' => true,
            ]);

            $offsetPaper = InventoryItem::create([
                'name' => '70gsm White Offset Paper',
                'sku' => 'PAPER-70W',
                'unit' => 'Sheet',
                'purchase_unit' => 'Ream',
                'conversion_factor' => 500,
                'type' => 'raw_material',
                'is_sellable' => false,
                'price' => 3.20,
                'average_cost' => 3.20,
            ]);

            $ivoryBoard = InventoryItem::create([
                'name' => '250gsm Ivory Board',
                'sku' => 'BOARD-250I',
                'unit' => 'Sheet',
                'purchase_unit' => 'Bundle',
                'conversion_factor' => 250,
                'type' => 'raw_material',
                'is_sellable' => false,
                'price' => 7.50,
                'average_cost' => 7.50,
            ]);

            $ink = InventoryItem::create([
                'name' => 'CMYK Ink Set',
                'sku' => 'INK-CMYK',
                'unit' => 'Kg',
                'purchase_unit' => 'Kg',
                'conversion_factor' => 1,
                'type' => 'raw_material',
                'is_sellable' => false,
                'price' => 95.00,
                'average_cost' => 95.00,
            ]);

            $adhesive = InventoryItem::create([
                'name' => 'Packaging Adhesive',
                'sku' => 'ADH-1L',
                'unit' => 'Litre',
                'purchase_unit' => 'Litre',
                'conversion_factor' => 1,
                'type' => 'raw_material',
                'is_sellable' => false,
                'price' => 15.00,
                'average_cost' => 15.00,
            ]);

            $finishedBox = InventoryItem::create([
                'name' => 'Coffee Packaging Box',
                'sku' => 'BOX-COF',
                'unit' => 'Piece',
                'purchase_unit' => 'Piece',
                'conversion_factor' => 1,
                'type' => 'finished_good',
                'is_sellable' => true,
                'price' => 18.00,
                'average_cost' => 0,
            ]);

            $poPaper = PurchaseOrder::create([
                'po_number' => 'PO-2026-001',
                'order_date' => now()->subDays(10),
                'partner_id' => $paperSupplier->id,
                'status' => 'approved',
            ]);

            $poPaperItem = PurchaseOrderItem::create([
                'purchase_order_id' => $poPaper->id,
                'inventory_item_id' => $offsetPaper->id,
                'quantity' => 120,
                'unit_price' => 1500,
                'total' => 120 * 1500,
            ]);

            $poBoardItem = PurchaseOrderItem::create([
                'purchase_order_id' => $poPaper->id,
                'inventory_item_id' => $ivoryBoard->id,
                'quantity' => 80,
                'unit_price' => 1800,
                'total' => 80 * 1800,
            ]);

            $poInk = PurchaseOrder::create([
                'po_number' => 'PO-2026-002',
                'order_date' => now()->subDays(8),
                'partner_id' => $inkSupplier->id,
                'status' => 'approved',
            ]);

            $poInkItem = PurchaseOrderItem::create([
                'purchase_order_id' => $poInk->id,
                'inventory_item_id' => $ink->id,
                'quantity' => 50,
                'unit_price' => 92.00,
                'total' => 50 * 92.00,
            ]);

            $goodsReceipt1 = GoodsReceipt::create([
                'receipt_number' => 'GR-2026-001',
                'receipt_date' => now()->subDays(7),
                'purchase_order_id' => $poPaper->id,
                'warehouse_id' => $rawWarehouse->id,
                'status' => 'draft',
                'posted_at' => null,
            ]);

            GoodsReceiptItem::create([
                'goods_receipt_id' => $goodsReceipt1->id,
                'purchase_order_item_id' => $poPaperItem->id,
                'quantity_received' => 120,
            ]);

            GoodsReceiptItem::create([
                'goods_receipt_id' => $goodsReceipt1->id,
                'purchase_order_item_id' => $poBoardItem->id,
                'quantity_received' => 80,
            ]);

            $goodsReceipt1->update(['status' => 'posted']);

            $goodsReceipt2 = GoodsReceipt::create([
                'receipt_number' => 'GR-2026-002',
                'receipt_date' => now()->subDays(6),
                'purchase_order_id' => $poInk->id,
                'warehouse_id' => $rawWarehouse->id,
                'status' => 'draft',
                'posted_at' => null,
            ]);

            GoodsReceiptItem::create([
                'goods_receipt_id' => $goodsReceipt2->id,
                'purchase_order_item_id' => $poInkItem->id,
                'quantity_received' => 50,
            ]);

            $goodsReceipt2->update(['status' => 'posted']);

            $poPaper->update(['status' => 'received']);
            $poInk->update(['status' => 'received']);

            $job = JobOrder::create([
                'partner_id' => $coffeeClient->id,
                'job_order_number' => 'JO-2026-001',
                'job_type' => 'packages',
                'cost_calc_file' => 'calc-coffee.xlsx',
                'services' => [],
                'status' => 'production',
                'submission_date' => now()->subDays(3),
                'total_price' => 180000,
                'advance_amount' => 50000,
            ]);

            $task = JobOrderTask::create([
                'job_order_id' => $job->id,
                'name' => 'Print Coffee Packaging Boxes',
                'quantity' => 10000,
                'unit_cost' => 18.00,
                'paper' => [
                    [
                        'inventory_item_id' => $offsetPaper->id,
                        'required_quantity' => 7000,
                        'reserve_quantity' => 300,
                        'base_unit' => 'Sheet',
                    ],
                    [
                        'inventory_item_id' => $ivoryBoard->id,
                        'required_quantity' => 2500,
                        'reserve_quantity' => 150,
                        'base_unit' => 'Sheet',
                    ],
                ],
            ]);

            app(\App\Services\InventoryService::class)
                ->consumeStock($offsetPaper->id, $rawWarehouse->id, 7200, 'job_order', $job->id);

            app(\App\Services\InventoryService::class)
                ->consumeStock($ivoryBoard->id, $rawWarehouse->id, 2600, 'job_order', $job->id);

            $adjustment = StockAdjustment::create([
                'warehouse_id' => $rawWarehouse->id,
                'adjustment_date' => now()->subDays(2),
                'status' => 'draft',
                'reason' => 'Cycle count correction',
            ]);

            StockAdjustmentItem::create([
                'stock_adjustment_id' => $adjustment->id,
                'inventory_item_id' => $offsetPaper->id,
                'system_quantity' => 120 * 500,
                'adjustment_quantity' => -500,
                'new_quantity' => (120 * 500) - 500,
                'difference' => -500,
            ]);

            StockAdjustmentItem::create([
                'stock_adjustment_id' => $adjustment->id,
                'inventory_item_id' => $ink->id,
                'system_quantity' => 50,
                'adjustment_quantity' => 10,
                'new_quantity' => 60,
                'difference' => 10,
            ]);

            $adjustment->update(['status' => 'posted']);

            $transfer = StockTransfer::create([
                'transfer_number' => 'ST-2026-001',
                'from_warehouse_id' => $rawWarehouse->id,
                'to_warehouse_id' => $finishedWarehouse->id,
                'transfer_date' => now()->subDay(),
                'status' => 'draft',
            ]);

            StockTransferItem::create([
                'stock_transfer_id' => $transfer->id,
                'inventory_item_id' => $ivoryBoard->id,
                'quantity' => 400,
            ]);

            $transfer->update(['status' => 'completed']);

            $machine = Machine::create([
                'name' => 'Offset Press 1',
                'code' => 'PRESS-1',
            ]);

            $plan = ProductionPlan::create([
                'week_start' => now()->startOfWeek(),
                'week_end' => now()->endOfWeek(),
                'status' => 'approved',
            ]);

            $planItem = ProductionPlanItem::create([
                'production_plan_id' => $plan->id,
                'machine_id' => $machine->id,
                'job_order_task_id' => $task->id,
                'planned_quantity' => 10000,
                'planned_plates' => 2,
                'planned_rounds' => 1,
            ]);

            $report = ProductionReport::create([
                'production_plan_id' => $plan->id,
                'status' => 'completed',
            ]);

            ProductionReportItem::create([
                'production_report_id' => $report->id,
                'production_plan_item_id' => $planItem->id,
                'date' => now(),
                'actual_quantity' => 9800,
                'plates_used' => 2,
                'rounds' => 1,
            ]);
        });
    }
}
