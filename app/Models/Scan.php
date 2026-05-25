<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Scan extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'short_link_id',
        'ip_hash',
        'country',
        'city',
        'device_type',
        'os',
        'browser',
        'referrer',
        'is_unique',
        'scanned_at',
    ];

    protected function casts(): array
    {
        return [
            'is_unique' => 'boolean',
            'scanned_at' => 'datetime',
        ];
    }

    public function shortLink(): BelongsTo
    {
        return $this->belongsTo(ShortLink::class);
    }
}
