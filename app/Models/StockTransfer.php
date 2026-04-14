<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransfer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'transfer_number',
        'from_warehouse_id',
        'to_warehouse_id',
        'transfer_date',
        'status',
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
            'from_warehouse_id' => 'integer',
            'to_warehouse_id' => 'integer',
            'transfer_date' => 'date',
        ];
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    protected static function booted()
    {
        static::updated(function ($transfer) {
            if ($transfer->wasChanged('status') && $transfer->status === 'completed') {
                $transfer->post();
            }
        });
    }

    public function post()
    {
        \Illuminate\Support\Facades\DB::transaction(function () {
            foreach ($this->items as $item) {
                $balance = \App\Models\InventoryBalance::where([
                    'inventory_item_id' => $item->inventory_item_id,
                    'warehouse_id' => $this->from_warehouse_id,
                ])->first();

                $available = $balance ? (float) $balance->quantity_on_hand : 0.0;
                $requested = (float) $item->quantity;

                if ($available < $requested) {
                    throw new \Exception(sprintf(
                        'Cannot complete transfer for item %s: only %s available in warehouse %s, requested %s.',
                        $item->inventoryItem?->name ?? $item->inventory_item_id,
                        number_format($available, 2),
                        $this->from_warehouse_id,
                        number_format($requested, 2)
                    ));
                }
            }

            foreach ($this->items as $item) {
                // Outward Movement
                StockMovement::create([
                    'inventory_item_id' => $item->inventory_item_id,
                    'warehouse_id' => $this->from_warehouse_id,
                    'type' => 'transfer_out',
                    'quantity' => -abs($item->quantity),
                    'reference_type' => self::class,
                    'reference_id' => $this->id,
                    'movement_date' => $this->transfer_date ?? now(),
                ]);

                // Inward Movement
                StockMovement::create([
                    'inventory_item_id' => $item->inventory_item_id,
                    'warehouse_id' => $this->to_warehouse_id,
                    'type' => 'transfer_in',
                    'quantity' => abs($item->quantity),
                    'reference_type' => self::class,
                    'reference_id' => $this->id,
                    'movement_date' => $this->transfer_date ?? now(),
                ]);
            }
        });
    }
}
