<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Size;

class JobOrderTask extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'job_order_id',
        'name',
        'quantity',
        'task_cost',
        'paper',
        'status',
        'size',
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
            'job_order_id' => 'integer',
            'task_cost' => 'decimal:2',
            'paper' => 'array',
        ];
    }

    public function jobOrder(): BelongsTo
    {
        return $this->belongsTo(JobOrder::class);
    }

    public function dispatchItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DispatchItem::class);
    }

    public function sizeItem(): BelongsTo
    {
        return $this->belongsTo(Size::class, 'size');
    }
    public function getProducedQuantityAttribute(): int|float
    {
        return (float) \App\Models\StockMovement::where('reference_type', static::class)
            ->where('reference_id', $this->id)
            ->where('type', 'production_output')
            ->sum('quantity');
    }

    public function getRemainingQuantityAttribute(): int|float
    {
        return $this->produced_quantity - $this->dispatchItems()->sum('quantity');
    }

    public function materialRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MaterialRequest::class);
    }

    public function artworks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Artwork::class);
    }
}
