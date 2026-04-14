<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JobOrder extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'job_order_number',
        'partner_id',
        'job_type',
        'production_mode',
        'services',
        'submission_date',
        'remarks',
        'advance_paid',
        'cost_calc_file',
        'advance_amount',
        'total_price',
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
            'partner_id' => 'integer',
            'submission_date' => 'date',
            'advance_amount' => 'decimal:2',
            'total_price' => 'decimal:2',
            'services' => 'json',
            'advance_paid' => 'boolean',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function jobOrderTasks(): HasMany
    {
        return $this->hasMany(JobOrderTask::class);
    }

    public function dispatches(): HasMany
    {
        return $this->hasMany(Dispatch::class);
    }

    public function salesInvoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class);
    }

    public function artworks(): HasMany
    {
        return $this->hasMany(Artwork::class);
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(JobOrderOutput::class);
    }

    public function materialMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
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
        return (float) ($this->total_price - $this->paid_amount);
    }

    public function scopePendingPayment($query)
    {
        return $query->whereRaw('total_price > (SELECT COALESCE(SUM(allocated_amount), 0) FROM payment_allocations WHERE allocatable_id = job_orders.id AND allocatable_type = ?)', [self::class]);
    }

    public function scopeFullyPaid($query)
    {
        return $query->whereRaw('total_price <= (SELECT COALESCE(SUM(allocated_amount), 0) FROM payment_allocations WHERE allocatable_id = job_orders.id AND allocatable_type = ?)', [self::class]);
    }

    public function recalculateTotal(): void
    {
        $this->updateQuietly([
            'total_price' => (float) $this->jobOrderTasks()->sum('unit_cost'),
        ]);
    }

    public function canStartProduction(): bool
    {
        if ($this->artworks()->count() === 0) {
            return false;
        }

        return !$this->artworks()
            ->where('is_approved', false)
            ->exists();
    }

    public function issuedQuantityFor($itemId): float
    {
        $sum = (float) StockMovement::where(function ($query) {
            $query->where('type', 'consumption')
                ->orWhere('type', 'material_return');
        })
            ->where('reference_id', $this->id)
            ->where('inventory_item_id', $itemId)
            ->sum('quantity');

        return round(abs($sum), 4);
    }

    public function issuedBalanceByWarehouse(): \Illuminate\Support\Collection
    {
        return StockMovement::where(function ($query) {
                $query->where('type', 'consumption')
                    ->orWhere('type', 'material_return');
            })
            ->where('reference_id', $this->id)
            ->select('warehouse_id', 'inventory_item_id')
            ->selectRaw('SUM(quantity) as net_quantity')
            ->groupBy('warehouse_id', 'inventory_item_id')
            ->get()
            ->groupBy('warehouse_id');
    }

    public function overConsumedQuantity($itemId): float
    {
        $required = collect();

        foreach ($this->jobOrderTasks as $task) {
            foreach ($task->paper ?? [] as $material) {
                if ($material['inventory_item_id'] == $itemId) {
                    $required->push(($material['required_quantity'] ?? 0) + ($material['reserve_quantity'] ?? 0));
                }
            }
        }

        $issued = $this->issuedQuantityFor($itemId);

        return max(0, $issued - $required->sum());
    }

    public function materialsCompletionPercentage(): float
    {
        $totalRequired = 0;
        $totalIssued = 0;

        foreach ($this->jobOrderTasks as $task) {
            foreach ($task->paper ?? [] as $material) {
                $req = ($material['required_quantity'] ?? 0) + ($material['reserve_quantity'] ?? 0);
                $totalRequired += $req;
                $totalIssued += min(
                    $this->issuedQuantityFor($material['inventory_item_id']),
                    $req
                );
            }
        }

        if ($totalRequired == 0) return 0;

        return ($totalIssued / $totalRequired) * 100;
    }

    public function getMaterialsSummaryAttribute(): array
    {
        $summary = [];
        
        if (!$this->relationLoaded('jobOrderTasks')) {
            $this->load('jobOrderTasks');
        }

        $uniqueMaterials = $this->jobOrderTasks->flatMap->paper->pluck('inventory_item_id')->unique()->filter();

        foreach ($uniqueMaterials as $itemId) {
            $item = \App\Models\InventoryItem::find($itemId);
            if (!$item) continue;

            $required = (float) $this->jobOrderTasks->flatMap->paper
                ->where('inventory_item_id', $itemId)
                ->sum(fn($p) => ($p['required_quantity'] ?? 0) + ($p['reserve_quantity'] ?? 0));

            $issued = $this->issuedQuantityFor($itemId);
            $required = (float) $this->jobOrderTasks->flatMap->paper
                ->where('inventory_item_id', $itemId)
                ->sum(fn($p) => ($p['required_quantity'] ?? 0) + ($p['reserve_quantity'] ?? 0));

            $overconsumed = max(0, $issued - $required);
            $remaining = max(0, $required - $issued);
            $completion = $required > 0 ? min(100, ($issued / $required) * 100) : 0;

            $summary[] = [
                'material_name' => $item->name,
                'required' => $required,
                'issued' => $issued,
                'remaining' => $remaining,
                'overconsumed' => $overconsumed,
                'completion' => round($completion, 2),
            ];
        }

        return $summary;
    }

    protected static function booted()
    {
        static::creating(function ($jobOrder) {
            if (!$jobOrder->job_order_number) {
                $lastJobOrder = static::orderBy('id', 'desc')->first();
                $lastNumber = 0;
                if ($lastJobOrder && preg_match('/JO-(\d+)/', $lastJobOrder->job_order_number, $matches)) {
                    $lastNumber = (int) $matches[1];
                }
                $jobOrder->job_order_number = 'JO-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            }
        });

        static::updating(function ($jobOrder) {
            if (
                $jobOrder->isDirty('status') &&
                $jobOrder->status === 'production'
            ) {
                if (!$jobOrder->canStartProduction()) {
                    throw new \Exception(
                        'All artworks must be uploaded and approved before production.'
                    );
                }

                $jobOrder->production_started_at = now();
            }

            if (
                $jobOrder->isDirty('status') &&
                $jobOrder->status === 'completed' &&
                $jobOrder->production_mode === 'make_to_stock'
            ) {
                foreach ($jobOrder->outputs as $output) {
                    // Prevent duplicate movements
                    $exists = StockMovement::where('reference_type', 'JobOrderOutput')
                        ->where('reference_id', $output->id)
                        ->exists();
                        
                    if (!$exists) {
                        StockMovement::create([
                            'inventory_item_id' => $output->inventory_item_id,
                            'warehouse_id' => $output->warehouse_id,
                            'type' => 'production_output',
                            'reference_type' => 'JobOrderOutput',
                            'reference_id' => $output->id,
                            'quantity' => abs($output->quantity),
                            'movement_date' => now(),
                        ]);
                    }
                }
            }
        });

        static::updated(function ($jobOrder) {
            if ($jobOrder->wasChanged('status') && $jobOrder->status === 'cancelled') {
                $jobOrder->jobOrderTasks()->update(['status' => 'cancelled']);
            }
        });
    }
}
