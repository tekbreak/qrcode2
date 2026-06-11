<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaidAction extends Model
{
    protected $fillable = [
        'user_id',
        'qr_code_id',
        'action_type',
        'status',
        'stripe_checkout_session_id',
        'pending_data',
        'amount_cents',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'pending_data' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
