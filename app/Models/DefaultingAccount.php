<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DefaultingAccount extends Model
{
    protected $fillable = [
        'account_id',
        'old_account_id',
        'name',
        'address',
        'phone_number',
        'closing_balance',
        'category',
        'progress',
        'paid_amount',
    ];

    protected function casts(): array
    {
        return [
            'closing_balance' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function getPendingAmountAttribute(): float
    {
        return max(0, (float) $this->closing_balance - (float) ($this->paid_amount ?? 0));
    }
}
