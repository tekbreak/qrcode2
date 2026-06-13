<?php

namespace App\Console\Commands;

use App\Enums\QrCodeType;
use App\Models\QrCode;
use App\Models\QrDesign;
use App\Services\Generators\ChillerlanGdEngine;
use Illuminate\Console\Command;

class GenerateQrShapePreviews extends Command
{
    protected $signature = 'qr:shape-previews {--size=72 : Thumbnail size in pixels} {--render-size=320 : Full QR render size before cropping}';

    protected $description = 'Generate QR shape thumbnail previews (top-left quadrant crop) into public/qr-shape-previews/';

    public function handle(ChillerlanGdEngine $engine): int
    {
        $size = (int) $this->option('size');
        $renderSize = (int) $this->option('render-size');
        $dir = public_path('qr-shape-previews');

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $content = 'https://example.com/preview';
        $total = 0;

        foreach (array_keys(config('qr_shapes.body', [])) as $style) {
            $this->writePreview($engine, $content, $size, $renderSize, $dir, "body_{$style}", $style, 'square', 'square');
            $total++;
        }

        foreach (array_keys(config('qr_shapes.eye_frame', [])) as $style) {
            $this->writePreview($engine, $content, $size, $renderSize, $dir, "frame_{$style}", 'square', $style, 'square');
            $total++;
        }

        foreach (array_keys(config('qr_shapes.eye_ball', [])) as $style) {
            $this->writePreview($engine, $content, $size, $renderSize, $dir, "ball_{$style}", 'square', 'square', $style);
            $total++;
        }

        $this->info("Generated {$total} shape previews in public/qr-shape-previews/");

        return self::SUCCESS;
    }

    protected function writePreview(
        ChillerlanGdEngine $engine,
        string $content,
        int $size,
        int $renderSize,
        string $dir,
        string $filename,
        string $body,
        string $frame,
        string $ball,
    ): void {
        $qrCode = new QrCode([
            'type' => QrCodeType::Url,
            'content_data' => ['url' => $content],
            'is_dynamic' => false,
        ]);

        $qrCode->setRelation('design', new QrDesign([
            'fg_color' => '#000000',
            'bg_color' => '#FFFFFF',
            'dot_style' => $body,
            'eye_frame_style' => $frame,
            'eye_ball_style' => $ball,
        ]));

        $png = $engine->generatePng($qrCode, $renderSize);
        $gd = imagecreatefromstring($png);

        if (! $gd) {
            file_put_contents("{$dir}/{$filename}.png", $png);

            return;
        }

        $srcW = imagesx($gd);
        $srcH = imagesy($gd);

        // Top-left quadrant: 50% × 50% = 25% of total QR area — shows finder + body modules clearly.
        $cropW = (int) floor($srcW / 2);
        $cropH = (int) floor($srcH / 2);

        $thumb = imagecreatetruecolor($size, $size);
        imagecopyresampled($thumb, $gd, 0, 0, 0, 0, $size, $size, $cropW, $cropH);
        imagedestroy($gd);

        imagepng($thumb, "{$dir}/{$filename}.png");
        imagedestroy($thumb);

        $this->line("  -> {$filename}.png");
    }
}
