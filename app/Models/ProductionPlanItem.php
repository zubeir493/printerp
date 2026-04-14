<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionPlanItem extends Model
{
    protected $fillable = [
        'production_plan_id',
        'machine_id',
        'job_order_task_id',
        'planned_quantity',
        'planned_plates',
        'planned_rounds',
    ];

    public function productionPlan(): BelongsTo
    {
        return $this->belongsTo(ProductionPlan::class);
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function jobOrderTask(): BelongsTo
    {
        return $this->belongsTo(JobOrderTask::class);
    }

    public function reportItems(): HasMany
    {
        return $this->hasMany(ProductionReportItem::class);
    }
}
