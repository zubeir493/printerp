<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'payment_number',
        'partner_id',
        'payment_date',
        'amount',
        'direction',
        'method',
        'reference',
        'transaction_type',
        'payment_type',
        'account_id',
        'expense_account_id',
        'petty_cash_account_id',
        'voided_at',
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
            'partner_id' => 'integer',
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'account_id' => 'integer',
            'expense_account_id' => 'integer',
            'petty_cash_account_id' => 'integer',
            'voided_at' => 'datetime',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }


    protected static function booted()
    {
        //
    }
}
