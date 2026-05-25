<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrDesign extends Model
{
    protected $fillable = [
        'qr_code_id',
        'fg_color',
        'bg_color',
        'gradient',
        'dot_style',
        'eye_style',
        'eye_frame_style',
        'eye_ball_style',
        'frame_style',
        'frame_text',
        'logo_path',
        'template_id',
    ];

    protected function casts(): array
    {
        return [
            'gradient' => 'array',
        ];
    }

    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class);
    }
}
