<?php

namespace App\Observers;

use App\Enums\PaymentTransactionType;
use App\Models\JournalEntry;
use App\Models\Partner;
use App\Models\Payment;
use App\Services\Accounting\CreatePaymentJournalEntry;

class PaymentObserver
{
    public function creating(Payment $payment): void
    {
        $transactionType = $payment->transaction_type
            ?? $this->legacyTransactionType($payment->payment_type, $payment->direction);

        $payment->transaction_type = $transactionType;
        $payment->direction = PaymentTransactionType::tryFrom($transactionType)?->direction()
            ?? PaymentTransactionType::from($this->legacyTransactionType($payment->payment_type, $payment->direction))->direction();

        $resolvedType = PaymentTransactionType::tryFrom($payment->transaction_type);

        if ($resolvedType && ! $resolvedType->requiresPartner() && ! $payment->partner_id) {
            $payment->partner_id = $this->resolveInternalPartnerId();
        }

        if (! $payment->payment_type) {
            $payment->payment_type = 'standard';
        }
    }

    public function created(Payment $payment)
    {
        app(CreatePaymentJournalEntry::class)->handle($payment);
    }

    public function updating(Payment $payment)
    {
        $hasJournalEntries = JournalEntry::where('source_type', Payment::class)
            ->where('source_id', $payment->id)
            ->exists();

        if ($hasJournalEntries) {
            throw new \RuntimeException('Cannot edit payment after it has been posted to the accounting ledger.');
        }
    }

    protected function legacyTransactionType(?string $paymentType, ?string $direction): string
    {
        return match ($paymentType) {
            'expense' => PaymentTransactionType::DIRECT_EXPENSE->value,
            'petty_cash' => $direction === 'inbound'
                ? PaymentTransactionType::PETTY_CASH_FUNDING->value
                : PaymentTransactionType::PETTY_CASH_EXPENSE->value,
            default => $direction === 'outbound'
                ? PaymentTransactionType::SUPPLIER_PAYMENT->value
                : PaymentTransactionType::CUSTOMER_RECEIPT->value,
        };
    }

    protected function resolveInternalPartnerId(): int
    {
        return Partner::firstOrCreate([
            'name' => 'Internal Payment',
        ], [
            'is_customer' => false,
            'is_supplier' => false,
        ])->id;
    }
}
