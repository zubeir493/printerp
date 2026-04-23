<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Observers\GoodsReceiptObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy(GoodsReceiptObserver::class)]
class GoodsReceipt extends Model
{
    protected $fillable = [
        'receipt_number',
        'purchase_order_id',
        'warehouse_id',
        'receipt_date',
        'status',
        'posted_at',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items()
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }
}
