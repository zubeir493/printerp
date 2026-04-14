<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_order_receipt_posts_purchase_journal_entry()
    {
        $supplier = Partner::create([
            'name' => 'Supplier Accounting',
            'is_supplier' => true,
        ]);

        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-ACC-001',
            'partner_id' => $supplier->id,
            'order_date' => now(),
            'status' => 'draft',
            'subtotal' => 10000.00,
        ]);

        $inventoryItem = \App\Models\InventoryItem::create([
            'name' => 'Office Paper',
            'sku' => 'PAPER-01',
            'unit' => 'Ream',
            'purchase_unit' => 'Ream',
            'conversion_factor' => 1,
            'type' => 'raw_material',
            'is_sellable' => false,
            'price' => 5.00,
            'average_cost' => 5.00,
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->id,
            'inventory_item_id' => $inventoryItem->id,
            'quantity' => 1,
            'unit_price' => 10000.00,
            'total' => 10000.00,
        ]);

        $purchaseOrder->update(['status' => 'received']);

        $this->assertDatabaseHas('journal_entries', [
            'source_type' => PurchaseOrder::class,
            'source_id' => $purchaseOrder->id,
            'status' => 'posted',
            'total_debit' => 10000.00,
            'total_credit' => 10000.00,
        ]);

        $journalEntry = JournalEntry::where('source_type', PurchaseOrder::class)
            ->where('source_id', $purchaseOrder->id)
            ->first();

        $this->assertNotNull($journalEntry);
        $this->assertDatabaseHas('journal_items', [
            'journal_entry_id' => $journalEntry->id,
            'debit' => 10000.00,
        ]);
    }

    public function test_inbound_payment_creates_payment_journal_entry()
    {
        $customer = Partner::create([
            'name' => 'Customer Accounting',
            'is_customer' => true,
        ]);

        $payment = Payment::create([
            'payment_number' => 'PAY-001',
            'partner_id' => $customer->id,
            'payment_date' => now(),
            'amount' => 2500.00,
            'direction' => 'inbound',
            'method' => 'bank_transfer',
            'reference' => 'Customer Payment',
        ]);

        $this->assertDatabaseHas('journal_entries', [
            'source_type' => Payment::class,
            'source_id' => $payment->id,
            'status' => 'posted',
            'total_debit' => 2500.00,
            'total_credit' => 2500.00,
        ]);

        $this->assertDatabaseHas('journal_items', [
            'debit' => 2500.00,
        ]);

        $this->assertDatabaseHas('journal_items', [
            'credit' => 2500.00,
        ]);
    }
}
