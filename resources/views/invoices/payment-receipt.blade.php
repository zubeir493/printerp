<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $receipt_data['receipt_number'] }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            color: #555;
            background: #F8F8F8;
            margin: 0;
            padding: 0;
        }
        .receipt-box {
            max-width: 800px;
            margin: 40px auto;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            font-size: 16px;
            line-height: 24px;
            background: #fff;
        }
        .receipt-box table {
            width: 100%;
            line-height: inherit;
            text-align: left;
            border-collapse: collapse;
        }
        .receipt-box table td {
            padding: 5px;
            vertical-align: top;
            border-bottom: 1px solid #eee;
        }
        .receipt-box table tr.top table td {
            padding-bottom: 20px;
        }
        .receipt-box table tr.top table td.title {
            font-size: 45px;
            line-height: 45px;
            color: #333;
        }
        .receipt-box table tr.information table td {
            padding-bottom: 40px;
        }
        .receipt-box table tr.heading td {
            background: #eee;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
        }
        .receipt-box table tr.details td {
            padding-bottom: 20px;
        }
        .receipt-box table tr.item td {
            border-bottom: 1px solid #eee;
        }
        .receipt-box table tr.item.last td {
            border-bottom: none;
        }
        .receipt-box table tr.total td:nth-child(2) {
            border-top: 2px solid #eee;
            font-weight: bold;
        }
        .receipt-box .payment-method {
            background: #e7f3ff;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .receipt-box .paid-stamp {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            font-weight: bold;
            text-align: center;
            font-size: 18px;
            border: 2px solid #155724;
            transform: rotate(-5deg);
            width: 150px;
            margin: 20px auto;
        }
        @media only screen and (max-width: 600px) {
            .receipt-box {
                width: 100%;
                margin: 0;
                padding: 20px;
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-box">
        <table>
            <tr class="top">
                <td colspan="2">
                    <table>
                        <tr>
                            <td class="title">
                                <img src="https://via.placeholder.com/100x50" style="width:100%; max-width:100px;">
                            </td>
                            <td>
                                <strong>Receipt #:</strong> {{ $receipt_data['receipt_number'] }}<br>
                                <strong>Receipt Date:</strong> {{ $receipt_data['receipt_date'] }}<br>
                                <strong>Payment ID:</strong> {{ $receipt_data['payment']->payment_number }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            
            <tr class="information">
                <td colspan="2">
                    <table>
                        <tr>
                            <td>
                                <strong>{{ $receipt_data['company_info']['name'] }}</strong><br>
                                {{ $receipt_data['company_info']['address'] }}<br>
                                {{ $receipt_data['company_info']['phone'] }}<br>
                                {{ $receipt_data['company_info']['email'] }}
                            </td>
                            <td>
                                <strong>Received From:</strong><br>
                                {{ $receipt_data['payment']->partner->name }}<br>
                                {{ $receipt_data['payment']->partner->address ?? '' }}<br>
                                {{ $receipt_data['payment']->partner->phone ?? '' }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            
            <tr class="heading">
                <td>Payment Details</td>
                <td>Amount</td>
            </tr>
            
            <tr class="item">
                <td>
                    Payment Amount<br>
                    @if($receipt_data['options']['show_payment_method'])
                    Payment Method: {{ ucfirst($receipt_data['payment']->method) }}
                    @if($receipt_data['payment']->bank_id)
                    - {{ $receipt_data['payment']->bank->name }}
                    @endif
                    <br>
                    @endif
                    @if($receipt_data['payment']->reference)
                    Reference: {{ $receipt_data['payment']->reference }}
                    @endif
                </td>
                <td>{{ number_format($receipt_data['payment']->amount, 2) }}</td>
            </tr>
            
            @if($receipt_data['options']['show_allocated_orders'] && $receipt_data['allocations']->count() > 0)
            <tr class="heading">
                <td colspan="2">Allocated To Orders</td>
            </tr>
            
            @foreach($receipt_data['allocations'] as $allocation)
            <tr class="item {{ $loop->last ? 'last' : '' }}">
                <td>
                    @if($allocation->allocatable_type === 'App\\Models\\SalesOrder')
                        Sales Order: {{ $allocation->allocatable->order_number }}
                    @elseif($allocation->allocatable_type === 'App\\Models\\JobOrder')
                        Job Order: {{ $allocation->allocatable->order_number }}
                    @endif
                </td>
                <td>{{ number_format($allocation->allocated_amount, 2) }}</td>
            </tr>
            @endforeach
            @endif
            
            <tr>
                <td colspan="2">
                    <table style="width: 300px; float: right;">
                        <tr class="total">
                            <td><strong>Total Received:</strong></td>
                            <td><strong>{{ number_format($receipt_data['payment']->amount, 2) }}</strong></td>
                        </tr>
                    </table>
                </td>
            </tr>
            
            <tr>
                <td colspan="2">
                    <div class="paid-stamp">
                        ✓ PAID
                    </div>
                </td>
            </tr>
            
            <tr>
                <td colspan="2">
                    <strong>Thank you for your payment!</strong><br>
                    This receipt serves as confirmation of payment received. Please retain for your records.
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
