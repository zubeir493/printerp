<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\SalesOrder;
use App\Models\JobOrder;
use App\Models\PurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class InvoiceGeneratorService
{
    private array $taxConfig;
    private array $companyInfo;

    public function __construct()
    {
        $this->taxConfig = $this->loadTaxConfiguration();
        $this->companyInfo = $this->loadCompanyInformation();
    }

    /**
     * Generate invoice from SalesOrder
     */
    public function generateFromSalesOrder(SalesOrder $order, array $options = []): array
    {
        $invoiceNumber = $this->generateInvoiceNumber('SALES');
        $taxCalculations = $this->calculateTaxes($order->salesOrderItems);
        
        $invoiceData = [
            'invoice_number' => $invoiceNumber,
            'invoice_date' => Carbon::now()->format('Y-m-d'),
            'due_date' => Carbon::now()->addDays(30)->format('Y-m-d'),
            'order' => $order,
            'items' => $order->salesOrderItems,
            'payments' => $order->paymentAllocations,
            'company_info' => $this->companyInfo,
            'tax_calculations' => $taxCalculations,
            'subtotal' => $order->subtotal,
            'tax_amount' => $taxCalculations['total_tax'],
            'total_amount' => $order->total + $taxCalculations['total_tax'],
            'balance_due' => $order->balance,
            'status' => $this->getInvoiceStatus($order),
            'options' => array_merge([
                'show_tax_breakdown' => true,
                'show_payment_status' => true,
                'show_terms' => true,
            ], $options)
        ];

        $pdf = Pdf::loadView('invoices.sales-order', $invoiceData);
        
        $filename = "invoice-{$invoiceNumber}.pdf";
        $path = "invoices/{$filename}";
        
        Storage::disk('public')->put($path, $pdf->output());

        return [
            'filename' => $filename,
            'path' => $path,
            'invoice_data' => $invoiceData,
            'pdf' => $pdf
        ];
    }

    /**
     * Generate receipt from Payment
     */
    public function generateFromPayment(Payment $payment, array $options = []): array
    {
        $receiptNumber = $this->generateInvoiceNumber('RECEIPT');
        
        $receiptData = [
            'receipt_number' => $receiptNumber,
            'receipt_date' => $payment->payment_date->format('Y-m-d'),
            'payment' => $payment,
            'allocations' => $payment->paymentAllocations,
            'company_info' => $this->companyInfo,
            'options' => array_merge([
                'show_payment_method' => true,
                'show_allocated_orders' => true,
            ], $options)
        ];

        $pdf = Pdf::loadView('invoices.payment-receipt', $receiptData);
        
        $filename = "receipt-{$receiptNumber}.pdf";
        $path = "receipts/{$filename}";
        
        Storage::disk('public')->put($path, $pdf->output());

        return [
            'filename' => $filename,
            'path' => $path,
            'receipt_data' => $receiptData,
            'pdf' => $pdf
        ];
    }

    /**
     * Generate invoice from PurchaseOrder
     */
    public function generateFromPurchaseOrder(PurchaseOrder $order, array $options = []): array
    {
        $invoiceNumber = $this->generateInvoiceNumber('PURCHASE');
        $taxCalculations = $this->calculateTaxes($order->purchaseOrderItems);
        
        $invoiceData = [
            'invoice_number' => $invoiceNumber,
            'invoice_date' => Carbon::now()->format('Y-m-d'),
            'due_date' => Carbon::now()->addDays(30)->format('Y-m-d'),
            'order' => $order,
            'items' => $order->purchaseOrderItems,
            'payments' => $order->paymentAllocations,
            'company_info' => $this->companyInfo,
            'tax_calculations' => $taxCalculations,
            'subtotal' => $order->subtotal,
            'tax_amount' => $taxCalculations['total_tax'],
            'total_amount' => $order->subtotal + $taxCalculations['total_tax'],
            'balance_due' => $order->balance,
            'status' => $this->getInvoiceStatus($order),
            'options' => array_merge([
                'show_tax_breakdown' => true,
                'show_payment_status' => true,
                'show_terms' => true,
            ], $options)
        ];

        $pdf = Pdf::loadView('invoices.purchase-order', $invoiceData);
        
        $filename = "purchase-invoice-{$invoiceNumber}.pdf";
        $path = "invoices/{$filename}";
        
        Storage::disk('public')->put($path, $pdf->output());

        return [
            'filename' => $filename,
            'path' => $path,
            'invoice_data' => $invoiceData,
            'pdf' => $pdf
        ];
    }

    /**
     * Generate invoice from JobOrder
     */
    public function generateFromJobOrder(JobOrder $order, array $options = []): array
    {
        $invoiceNumber = $this->generateInvoiceNumber('SERVICE');
        $taxCalculations = $this->calculateServiceTaxes($order);
        
        $invoiceData = [
            'invoice_number' => $invoiceNumber,
            'invoice_date' => Carbon::now()->format('Y-m-d'),
            'due_date' => Carbon::now()->addDays(15)->format('Y-m-d'),
            'order' => $order,
            'items' => $order->jobOrderItems,
            'payments' => $order->paymentAllocations,
            'company_info' => $this->companyInfo,
            'tax_calculations' => $taxCalculations,
            'subtotal' => $order->total,
            'tax_amount' => $taxCalculations['total_tax'],
            'total_amount' => $order->total + $taxCalculations['total_tax'],
            'balance_due' => $order->balance,
            'status' => $this->getInvoiceStatus($order),
            'options' => array_merge([
                'show_service_details' => true,
                'show_tax_breakdown' => true,
            ], $options)
        ];

        $pdf = Pdf::loadView('invoices.job-order', $invoiceData);
        
        $filename = "service-invoice-{$invoiceNumber}.pdf";
        $path = "invoices/{$filename}";
        
        Storage::disk('public')->put($path, $pdf->output());

        return [
            'filename' => $filename,
            'path' => $path,
            'invoice_data' => $invoiceData,
            'pdf' => $pdf
        ];
    }

    /**
     * Generate batch invoice for multiple orders
     */
    public function generateBatchInvoice(array $orders, array $options = []): array
    {
        $invoiceNumber = $this->generateInvoiceNumber('BATCH');
        
        $allItems = collect();
        $totalSubtotal = 0;
        $taxCalculations = ['total_tax' => 0, 'breakdown' => []];
        
        foreach ($orders as $order) {
            $allItems = $allItems->merge($order->salesOrderItems);
            $totalSubtotal += $order->subtotal;
            
            $orderTaxes = $this->calculateTaxes($order->salesOrderItems);
            $taxCalculations['total_tax'] += $orderTaxes['total_tax'];
            
            foreach ($orderTaxes['breakdown'] as $type => $amount) {
                $taxCalculations['breakdown'][$type] = ($taxCalculations['breakdown'][$type] ?? 0) + $amount;
            }
        }

        $invoiceData = [
            'invoice_number' => $invoiceNumber,
            'invoice_date' => Carbon::now()->format('Y-m-d'),
            'due_date' => Carbon::now()->addDays(30)->format('Y-m-d'),
            'orders' => $orders,
            'items' => $allItems,
            'company_info' => $this->companyInfo,
            'tax_calculations' => $taxCalculations,
            'subtotal' => $totalSubtotal,
            'tax_amount' => $taxCalculations['total_tax'],
            'total_amount' => $totalSubtotal + $taxCalculations['total_tax'],
            'options' => array_merge([
                'show_order_breakdown' => true,
                'show_tax_breakdown' => true,
            ], $options)
        ];

        $pdf = Pdf::loadView('invoices.batch', $invoiceData);
        
        $filename = "batch-invoice-{$invoiceNumber}.pdf";
        $path = "invoices/{$filename}";
        
        Storage::disk('public')->put($path, $pdf->output());

        return [
            'filename' => $filename,
            'path' => $path,
            'invoice_data' => $invoiceData,
            'pdf' => $pdf
        ];
    }

    /**
     * Send invoice via email
     */
    public function sendInvoiceEmail(array $invoiceData, string $recipientEmail, array $options = []): bool
    {
        try {
            Mail::to($recipientEmail)
                ->send(new \App\Mail\InvoiceGenerated($invoiceData, $options));
            
            Log::info('Invoice sent successfully', [
                'invoice_number' => $invoiceData['invoice_data']['invoice_number'] ?? 'Unknown',
                'recipient' => $recipientEmail
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send invoice', [
                'invoice_number' => $invoiceData['invoice_data']['invoice_number'] ?? 'Unknown',
                'recipient' => $recipientEmail,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Calculate taxes for items
     */
    private function calculateTaxes($items): array
    {
        $taxBreakdown = [];
        $totalTax = 0;

        if (empty($items)) {
            return [
                'total_tax' => 0,
                'breakdown' => $taxBreakdown
            ];
        }

        foreach ($items as $item) {
            $itemTax = $this->calculateItemTax($item);
            $totalTax += $itemTax['tax_amount'];
            
            foreach ($itemTax['breakdown'] as $type => $amount) {
                $taxBreakdown[$type] = ($taxBreakdown[$type] ?? 0) + $amount;
            }
        }

        return [
            'total_tax' => $totalTax,
            'breakdown' => $taxBreakdown
        ];
    }

    /**
     * Calculate taxes for service items
     */
    private function calculateServiceTaxes($order): array
    {
        // Similar to calculateTaxes but for service-specific tax rules
        return $this->calculateTaxes($order->jobOrderItems);
    }

    /**
     * Calculate tax for individual item
     */
    private function calculateItemTax($item): array
    {
        $taxAmount = 0;
        $breakdown = [];
        $itemTotal = $item->quantity * $item->unit_price;

        foreach ($this->taxConfig as $taxType => $rate) {
            if ($this->isTaxApplicable($item, $taxType)) {
                $tax = $itemTotal * $rate;
                $taxAmount += $tax;
                $breakdown[$taxType] = $tax;
            }
        }

        return [
            'tax_amount' => $taxAmount,
            'breakdown' => $breakdown
        ];
    }

    /**
     * Check if tax is applicable to item
     */
    private function isTaxApplicable($item, string $taxType): bool
    {
        // Implement tax applicability rules
        // For now, apply VAT to all items
        return $taxType === 'VAT';
    }

    /**
     * Generate unique invoice number
     */
    private function generateInvoiceNumber(string $prefix): string
    {
        $year = Carbon::now()->format('Y');
        $sequence = $this->getNextSequence($prefix, $year);
        
        return "{$prefix}-{$year}-" . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get next sequence number for invoice numbering
     */
    private function getNextSequence(string $prefix, string $year): int
    {
        // In a real implementation, this would use a database sequence or cache
        static $counters = [];
        
        $key = "{$prefix}-{$year}";
        $counters[$key] = ($counters[$key] ?? 0) + 1;
        
        return $counters[$key];
    }

    /**
     * Get invoice status based on order/payment status
     */
    private function getInvoiceStatus($order): string
    {
        if ($order->balance <= 0) {
            return 'PAID';
        } elseif ($order->paid_amount > 0) {
            return 'PARTIAL';
        } else {
            return 'UNPAID';
        }
    }

    /**
     * Load tax configuration
     */
    private function loadTaxConfiguration(): array
    {
        $taxes = config('invoice.taxes', []);
        $taxConfig = [];
        
        foreach ($taxes as $key => $tax) {
            $taxConfig[strtoupper($tax['name'])] = $tax['rate'];
        }
        
        return $taxConfig;
    }

    /**
     * Load company information
     */
    private function loadCompanyInformation(): array
    {
        return config('invoice.company', [
            'name' => config('app.name', 'Your Company'),
            'address' => '123 Business Street, City, Country',
            'phone' => '+1 234 567 8900',
            'email' => 'billing@yourcompany.com',
            'tax_id' => 'TAX-123456789',
            'website' => 'www.yourcompany.com',
        ]);
    }

    /**
     * Get stored invoice path
     */
    public function getInvoicePath(string $filename): string
    {
        $disk = config('invoice.storage.disk', 'public');
        $path = config('invoice.storage.path', 'invoices');
        return Storage::disk($disk)->url("{$path}/{$filename}");
    }

    /**
     * Delete stored invoice
     */
    public function deleteInvoice(string $filename): bool
    {
        return Storage::disk('public')->delete("invoices/{$filename}");
    }
}
