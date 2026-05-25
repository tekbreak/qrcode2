<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class ShortLink extends Model
{
    use HasFactory;
    protected $fillable = [
        'qr_code_id',
        'domain',
        'slug',
        'destination_url',
        'rules',
        'password_hash',
        'expires_at',
        'max_scans',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rules' => 'array',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'max_scans' => 'integer',
        ];
    }

    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class);
    }

    public function scans(): HasMany
    {
        return $this->hasMany(Scan::class);
    }

    public function scanAggregates(): HasMany
    {
        return $this->hasMany(ScanAggregate::class);
    }

    public static function generateSlug(int $length = null): string
    {
        $length ??= config('qrcode.slug_length', 7);

        do {
            $slug = Str::random($length);
        } while (static::where('slug', $slug)->exists());

        return $slug;
    }

    public function getFullUrl(): string
    {
        $domain = $this->domain ?: config('app.proxy_domain');
        $scheme = config('app.proxy_scheme', 'https');

        return "{$scheme}://{$domain}/{$this->slug}";
    }

    public function isExpired(): bool
    {
        if ($this->expires_at && $this->expires_at->isPast()) {
            return true;
        }

        if ($this->max_scans && $this->qrCode && $this->qrCode->total_scans >= $this->max_scans) {
            return true;
        }

        return false;
    }
}
