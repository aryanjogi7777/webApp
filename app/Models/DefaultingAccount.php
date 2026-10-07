<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DefaultingAccount extends Model
{
    public const STATUSES = [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'done' => 'Done',
        'on_hold' => 'On Hold',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

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
        'status',
        'payment_date',
    ];

    protected function casts(): array
    {
        return [
            'closing_balance' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? self::STATUSES['pending'];
    }

    public function getPendingAmountAttribute(): float
    {
        return max(0, (float) $this->closing_balance - (float) ($this->paid_amount ?? 0));
    }
}
