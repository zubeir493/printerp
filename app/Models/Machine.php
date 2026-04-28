<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Machine extends Model
{
    protected $fillable = ['name', 'code', 'baseline_rounds_per_week'];

    public function productionPlanItems(): HasMany
    {
        return $this->hasMany(ProductionPlanItem::class);
    }
}
