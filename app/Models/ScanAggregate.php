<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanAggregate extends Model
{
    protected $fillable = [
        'short_link_id',
        'date',
        'hour',
        'total_scans',
        'unique_scans',
        'countries',
        'devices',
        'browsers',
        'referrers',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'countries' => 'array',
            'devices' => 'array',
            'browsers' => 'array',
            'referrers' => 'array',
        ];
    }

    public function shortLink(): BelongsTo
    {
        return $this->belongsTo(ShortLink::class);
    }
}
