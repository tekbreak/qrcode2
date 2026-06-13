<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'color',
    ];

    public static function colorOptions(): array
    {
        return [
            'blue'   => ['bg' => 'bg-blue-100',   'text' => 'text-blue-700'],
            'green'  => ['bg' => 'bg-green-100',  'text' => 'text-green-700'],
            'purple' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-700'],
            'orange' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-700'],
            'pink'   => ['bg' => 'bg-pink-100',   'text' => 'text-pink-700'],
            'teal'   => ['bg' => 'bg-teal-100',   'text' => 'text-teal-700'],
            'gray'   => ['bg' => 'bg-gray-100',   'text' => 'text-gray-700'],
        ];
    }

    public function badgeClasses(): string
    {
        $colors = self::colorOptions()[$this->color] ?? self::colorOptions()['gray'];

        return $colors['bg'] . ' ' . $colors['text'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function qrCodes(): HasMany
    {
        return $this->hasMany(QrCode::class);
    }
}
