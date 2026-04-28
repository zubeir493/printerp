<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoiceData['invoice_number'] }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            color: #555;
            background: #F8F8F8;
            margin: 0;
            padding: 0;
        }
        .invoice-box {
            max-width: 800px;
            margin: 40px auto;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            font-size: 16px;
            line-height: 24px;
            background: #fff;
        }
        .invoice-box table {
            width: 100%;
            line-height: inherit;
            text-align: left;
            border-collapse: collapse;
        }
        .invoice-box table td {
            padding: 5px;
            vertical-align: top;
            border-bottom: 1px solid #eee;
        }
        .invoice-box table tr.top table td {
            padding-bottom: 20px;
        }
        .invoice-box table tr.top table td.title {
            font-size: 45px;
            line-height: 45px;
            color: #333;
        }
        .invoice-box table tr.information table td {
            padding-bottom: 40px;
        }
        .invoice-box table tr.heading td {
            background: #eee;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
        }
        .invoice-box table tr.details td {
            padding-bottom: 20px;
        }
        .invoice-box table tr.item td {
            border-bottom: 1px solid #eee;
        }
        .invoice-box table tr.total td:nth-child(2) {
            text-align: right;
        }
        .invoice-box table tr.total td:last-child {
            text-align: right;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .mt-20 {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="invoice-box">
        <table>
            <tr class="top">
                <td colspan="4">
                    <table>
                        <tr>
                            <td class="title">
                                <h1>INVOICE</h1>
                            </td>
                            <td class="text-right">
                                Invoice #: {{ $invoiceData['invoice_number'] }}<br>
                                Date: {{ $invoiceData['invoice_date'] }}<br>
                                Due: {{ $invoiceData['due_date'] }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            
            <tr class="information">
                <td colspan="4">
                    <table>
                        <tr>
                            <td>
                                <strong>From:</strong><br>
                                {{ $invoiceData['company_info']['name'] ?? 'Printos Company' }}<br>
                                {{ $invoiceData['company_info']['address'] ?? '123 Business Street, Addis Ababa, Ethiopia' }}<br>
                                {{ $invoiceData['company_info']['phone'] ?? '+251 911 000 000' }}<br>
                                {{ $invoiceData['company_info']['email'] ?? 'info@printos.com' }}
                            </td>
                            <td class="text-right">
                                <strong>To:</strong><br>
                                {{ $invoiceData['customer_info']['name'] ?? 'Valued Customer' }}<br>
                                {{ $invoiceData['customer_info']['address'] ?? '' }}<br>
                                {{ $invoiceData['customer_info']['phone'] ?? '' }}<br>
                                {{ $invoiceData['customer_info']['email'] ?? '' }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            
            <tr class="heading">
                <td>Job Order #</td>
                <td>Service</td>
                <td class="text-right">Quantity</td>
                <td class="text-right">Price</td>
                <td class="text-right">Total</td>
            </tr>
            
            @foreach($invoiceData['items'] as $item)
            <tr class="item">
                <td>{{ $item['job_order_number'] ?? $invoiceData['order']->job_order_number ?? '' }}</td>
                <td>{{ $item['service_name'] ?? $item['task_name'] ?? 'Service' }}</td>
                <td class="text-right">{{ $item['quantity'] ?? 1 }}</td>
                <td class="text-right">{{ number_format($item['unit_price'] ?? $item['cost'] ?? 0, 2) }} Birr</td>
                <td class="text-right">{{ number_format($item['total'] ?? $item['cost'] ?? 0, 2) }} Birr</td>
            </tr>
            @endforeach
            
            <tr class="total">
                <td colspan="4">Subtotal:</td>
                <td class="text-right">{{ number_format($invoiceData['subtotal'], 2) }} Birr</td>
            </tr>
            
            @if($invoiceData['tax_amount'] > 0)
            <tr class="total">
                <td colspan="4">Tax ({{ $invoiceData['tax_calculations']['tax_rate'] ?? 15 }}%):</td>
                <td class="text-right">{{ number_format($invoiceData['tax_amount'], 2) }} Birr</td>
            </tr>
            @endif
            
            <tr class="total">
                <td colspan="4"><strong>Total:</strong></td>
                <td class="text-right"><strong>{{ number_format($invoiceData['total_amount'], 2) }} Birr</strong></td>
            </tr>
            
            @if($invoiceData['balance_due'] > 0)
            <tr class="total">
                <td colspan="4">Paid:</td>
                <td class="text-right">-{{ number_format($invoiceData['order']->paid_amount ?? 0, 2) }} Birr</td>
            </tr>
            
            <tr class="total">
                <td colspan="4"><strong>Balance Due:</strong></td>
                <td class="text-right"><strong>{{ number_format($invoiceData['balance_due'], 2) }} Birr</strong></td>
            </tr>
            @endif
        </table>
        
        @if(isset($invoiceData['notes']))
        <div class="mt-20">
            <h3>Notes</h3>
            <p>{{ $invoiceData['notes'] }}</p>
        </div>
        @endif
        
        <div class="mt-20 text-center">
            <p><strong>Thank you for your business!</strong></p>
        </div>
    </div>
</body>
</html>
