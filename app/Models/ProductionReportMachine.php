<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionReportMachine extends Model
{
    protected $fillable = ['production_report_id', 'production_plan_machine_id'];

    public function productionReport(): BelongsTo
    {
        return $this->belongsTo(ProductionReport::class);
    }

    public function productionPlanMachine(): BelongsTo
    {
        return $this->belongsTo(ProductionPlanMachine::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductionReportItem::class);
    }
}
