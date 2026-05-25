<?php

namespace App\Console\Commands;

use App\Enums\QrCodeType;
use App\Models\QrCode;
use App\Models\QrDesign;
use App\QRCode\CustomQROptions;
use App\QRCode\Output\CustomQRGdImage;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Common\Version;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode as QRCodeLib;
use Illuminate\Console\Command;

class GenerateQrExamples extends Command
{
    protected $signature = 'qr:examples {--size=400 : Image size in pixels}';

    protected $description = 'Generate example QR codes for every shape combination into /examples';

    public function handle(): int
    {
        $size = (int) $this->option('size');
        $dir = base_path('examples');

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $bodies = array_keys(config('qr_shapes.body'));
        $frames = array_keys(config('qr_shapes.eye_frame'));
        $balls = array_keys(config('qr_shapes.eye_ball'));

        $content = 'https://example.com/qr-test';
        $scale = max(10, (int) ceil(($size * 2) / 45));

        // 1) Body shapes (eye frame & ball = square)
        $this->info('Generating body shape examples...');
        foreach ($bodies as $body) {
            $file = "{$dir}/body_{$body}.png";
            $this->generateQr($content, $size, $scale, $body, 'square', 'square', $file);
            $this->line("  -> body_{$body}.png");
        }

        // 2) Eye frame shapes (body = square, ball = square)
        $this->info('Generating eye frame shape examples...');
        foreach ($frames as $frame) {
            $file = "{$dir}/frame_{$frame}.png";
            $this->generateQr($content, $size, $scale, 'square', $frame, 'square', $file);
            $this->line("  -> frame_{$frame}.png");
        }

        // 3) Eye ball shapes (body = square, frame = square)
        $this->info('Generating eye ball shape examples...');
        foreach ($balls as $ball) {
            $file = "{$dir}/ball_{$ball}.png";
            $this->generateQr($content, $size, $scale, 'square', 'square', $ball, $file);
            $this->line("  -> ball_{$ball}.png");
        }

        // 4) Popular combos
        $this->info('Generating combination examples...');
        $combos = [
            ['dots', 'rounded', 'rounded'],
            ['dots', 'circle', 'circle'],
            ['dots', 'dot', 'dot'],
            ['rounded_square', 'rounded', 'rounded'],
            ['extra_rounded', 'rounded', 'squircle'],
            ['diamond', 'square', 'square'],
            ['dots_small', 'dotted', 'dot'],
            ['vertical_bars', 'square', 'bars_vertical'],
            ['horizontal_bars', 'square', 'bars_horizontal'],
            ['plus', 'rounded_double', 'diamond'],
            ['grid', 'dotted', 'diamond'],
            ['rounded_connected', 'rounded', 'rounded'],
            ['rounded_connected', 'cushion', 'leaf'],
            ['extra_rounded_connected', 'circle', 'circle'],
            ['classy', 'square', 'square'],
            ['classy_rounded', 'rounded', 'rounded'],
            ['star', 'square', 'star'],
            ['octagon', 'square', 'diamond'],
            ['leaf', 'rounded', 'leaf'],
        ];

        foreach ($combos as $combo) {
            [$body, $frame, $ball] = $combo;
            $name = "combo_{$body}_{$frame}_{$ball}";
            $file = "{$dir}/{$name}.png";
            $this->generateQr($content, $size, $scale, $body, $frame, $ball, $file);
            $this->line("  -> {$name}.png");
        }

        $total = count($bodies) + count($frames) + count($balls) + count($combos);
        $this->newLine();
        $this->info("Done! Generated {$total} examples in /examples");

        return self::SUCCESS;
    }

    private function generateQr(
        string $content,
        int $size,
        int $scale,
        string $bodyShape,
        string $eyeFrameShape,
        string $eyeBallShape,
        string $outputFile
    ): void {
        $options = new CustomQROptions([
            'version' => Version::AUTO,
            'outputType' => QROutputInterface::CUSTOM,
            'outputInterface' => CustomQRGdImage::class,
            'eccLevel' => EccLevel::H,
            'scale' => $scale,
            'imageBase64' => false,
            'bgColor' => [255, 255, 255],
            'imageTransparent' => false,
            'keepASCIINewline' => true,
            'quietzoneSize' => 2,
            'bodyShape' => $bodyShape,
            'eyeFrameShape' => $eyeFrameShape,
            'eyeBallShape' => $eyeBallShape,
        ]);

        $imageData = (new QRCodeLib($options))->render($content);

        $gd = imagecreatefromstring($imageData);
        $srcW = imagesx($gd);
        $srcH = imagesy($gd);

        if ($srcW !== $size || $srcH !== $size) {
            $resized = imagecreatetruecolor($size, $size);
            imagecopyresampled($resized, $gd, 0, 0, 0, 0, $size, $size, $srcW, $srcH);
            imagedestroy($gd);
        } else {
            $resized = $gd;
        }

        imagepng($resized, $outputFile);
        imagedestroy($resized);
    }
}
