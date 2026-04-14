<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionPlan extends Model
{
    protected $fillable = ['week_start', 'week_end', 'status'];

    protected $casts = [
        'week_start' => 'date',
        'week_end' => 'date',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ProductionPlanItem::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ProductionReport::class);
    }
}
