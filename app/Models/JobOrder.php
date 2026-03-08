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
        $movements = StockMovement::where(function ($query) {
            $query->where('type', 'material_issue')
                ->orWhere('type', 'material_return');
        })
            ->whereIn('reference_type', ['material_issue', 'material_return'])
            ->where('reference_id', $this->id)
            ->where('inventory_item_id', $itemId)
            ->get();

        $net = 0;

        foreach ($movements as $movement) {
            $net += $movement->quantity;
        }

        return abs($net);
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
        $uniqueMaterials = $this->jobOrderTasks->flatMap->paper->pluck('inventory_item_id')->unique();

        foreach ($uniqueMaterials as $itemId) {
            $item = \App\Models\InventoryItem::find($itemId);
            if (!$item) continue;

            $required = $this->jobOrderTasks->flatMap->paper
                ->where('inventory_item_id', $itemId)
                ->sum(fn($p) => ($p['required_quantity'] ?? 0) + ($p['reserve_quantity'] ?? 0));

            $issued = $this->issuedQuantityFor($itemId);
            $overconsumed = $this->overConsumedQuantity($itemId);
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
                    StockMovement::create([
                        'inventory_item_id' => $output->inventory_item_id,
                        'warehouse_id' => $output->warehouse_id,
                        'type' => 'production',
                        'reference_type' => 'JobOrderOutput',
                        'reference_id' => $output->id,
                        'quantity' => $output->quantity,
                        'movement_date' => now(),
                    ]);
                }
            }
        });
    }
}
