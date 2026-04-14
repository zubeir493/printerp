<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'unit_cost',
        'paper',
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
            'job_order_id' => 'integer',
            'unit_cost' => 'decimal:2',
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

    public function getRemainingQuantityAttribute(): int|float
    {
        return $this->quantity - $this->dispatchItems()->sum('quantity');
    }
}
