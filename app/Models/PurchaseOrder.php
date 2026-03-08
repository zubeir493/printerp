<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PurchaseOrder extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'po_number',
        'partner_id',
        'order_date',
        'status',
        'subtotal'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'partner_id' => 'integer',
            'order_date' => 'date',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function paymentAllocations(): MorphMany
    {
        return $this->morphMany(PaymentAllocation::class, 'allocatable');
    }

    public function getPaidAmountAttribute(): float
    {
        return (float) $this->paymentAllocations()->sum('allocated_amount');
    }

    public function getBalanceAttribute(): float
    {
        return (float) (($this->subtotal ?? 0) - $this->paid_amount);
    }


    protected static function booted()
    {
        static::updating(function ($po) {
            // When approved: Create Goods Receipt (Draft)
            if ($po->isDirty('status') && $po->status === 'approved') {
                $receipt = \App\Models\GoodsReceipt::create([
                    'receipt_number' => 'GR-' . time(),
                    'purchase_order_id' => $po->id,
                    'warehouse_id' => null,
                    'receipt_date' => now(),
                    'status' => 'draft',
                ]);

                foreach ($po->purchaseOrderItems as $item) {
                    $receipt->items()->create([
                        'purchase_order_item_id' => $item->id,
                        'quantity_received' => $item->quantity,
                    ]);
                }
            }
        });
    }
}
