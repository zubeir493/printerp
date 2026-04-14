<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Machine extends Model
{
    protected $fillable = ['name', 'code'];

    public function productionPlanItems(): HasMany
    {
        return $this->hasMany(ProductionPlanItem::class);
    }
}
