<?php

namespace App\Filament\Support;

class Calculations
{
    /**
     * Recalculates the total for a repeater based on a quantity and unit price field.
     */
    public static function updateSubtotal($get, $set, $repeaterField, $subtotalField, $qtyField = 'quantity', $priceField = 'unit_price')
    {
        $items = $get($repeaterField) ?? [];
        
        $subtotal = collect($items)->reduce(function ($carry, $item) use ($qtyField, $priceField) {
            $qty = (float) ($item[$qtyField] ?? 0);
            $price = (float) ($item[$priceField] ?? 0);
            return $carry + ($qty * $price);
        }, 0);

        $set($subtotalField, $subtotal);
    }

    /**
     * Recalculates the total for a repeater by summing up a specific field.
     */
    public static function sumRepeater($get, $set, $repeaterField, $targetField, $sumField)
    {
        $items = $get($repeaterField) ?? [];
        $total = collect($items)->sum(fn($item) => (float) ($item[$sumField] ?? 0));
        $set($targetField, $total);
    }
}
