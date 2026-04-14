<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionReport extends Model
{
    protected $fillable = ['production_plan_id', 'status'];

    public function productionPlan(): BelongsTo
    {
        return $this->belongsTo(ProductionPlan::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductionReportItem::class);
    }
}
