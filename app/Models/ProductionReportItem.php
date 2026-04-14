<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionReportItem extends Model
{
    protected $fillable = [
        'production_report_id',
        'production_plan_item_id',
        'date',
        'actual_quantity',
        'plates_used',
        'rounds',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function productionReport(): BelongsTo
    {
        return $this->belongsTo(ProductionReport::class);
    }

    public function productionPlanItem(): BelongsTo
    {
        return $this->belongsTo(ProductionPlanItem::class);
    }
}
