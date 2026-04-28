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
        'due_date',
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
            'due_date' => 'date',
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

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
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


    public function recalculateSubtotal(): void
    {
        $this->updateQuietly([
            'subtotal' => (float) $this->purchaseOrderItems()->sum('total'),
        ]);
    }


    protected static function booted()
    {
        static::updating(function ($po) {
            //
        });

        static::updated(function ($po) {
            if ($po->wasChanged('status') && $po->status === 'cancelled') {
                $po->purchaseOrderItems()->update(['status' => 'cancelled']);
            }
        });
    }
}
