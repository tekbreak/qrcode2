<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditBalance extends Model
{
    protected $fillable = [
        'user_id',
        'balance',
        'monthly_allowance',
        'resets_at',
    ];

    protected function casts(): array
    {
        return [
            'resets_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
