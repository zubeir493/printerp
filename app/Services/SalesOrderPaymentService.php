<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\DB;

class SalesOrderPaymentService
{
    public function createImmediatePaymentForCashSale(SalesOrder $salesOrder): ?Payment
    {
        $salesOrder->loadMissing('paymentAllocations', 'salesOrderItems');

        if (! $salesOrder->isCashSale()) {
            return null;
        }

        $amount = (float) $salesOrder->salesOrderItems->sum('total');

        if ($amount <= 0) {
            $amount = (float) $salesOrder->fresh()->total;
        }

        if ($amount <= 0 || $salesOrder->paymentAllocations()->exists()) {
            return null;
        }

        return DB::transaction(function () use ($salesOrder, $amount) {
            $nextId = (Payment::max('id') ?? 0) + 1;
            $paymentNumber = 'PAY-SO-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);

            $payment = Payment::create([
                'payment_number' => $paymentNumber,
                'partner_id' => $salesOrder->partner_id,
                'amount' => $amount,
                'direction' => 'inbound',
                'transaction_type' => \App\Enums\PaymentTransactionType::CUSTOMER_RECEIPT->value,
                'method' => $salesOrder->payment_method ?: 'cash',
                'reference' => $salesOrder->payment_reference ?: 'Immediate receipt for sale ' . $salesOrder->order_number,
                'payment_date' => $salesOrder->order_date,
            ]);

            $salesOrder->paymentAllocations()->create([
                'payment_id' => $payment->id,
                'allocated_amount' => $amount,
            ]);

            return $payment;
        });
    }
}
