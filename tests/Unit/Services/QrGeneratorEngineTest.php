<?php

namespace Tests\Unit\Services;

use App\Enums\QrCodeType;
use App\Models\QrCode;
use App\Models\QrDesign;
use App\Services\Generators\ChillerlanGdEngine;
use App\Services\Generators\ChillerlanSvgEngine;
use App\Services\QrCodeGeneratorService;
use ReflectionClass;
use Tests\TestCase;

class QrGeneratorEngineTest extends TestCase
{
    public function test_v2_engine_generates_png_and_svg(): void
    {
        config(['qrcode.generator_engine' => 'v2']);

        $qrCode = new QrCode([
            'type' => QrCodeType::Url,
            'content_data' => ['url' => 'https://example.com/v2-test'],
            'is_dynamic' => false,
        ]);

        $qrCode->setRelation('design', new QrDesign([
            'fg_color' => '#111111',
            'bg_color' => '#ffffff',
            'dot_style' => 'dots',
            'eye_frame_style' => 'rounded',
            'eye_ball_style' => 'circle',
            'gradient' => ['color1' => '#111111', 'color2' => '#4444aa', 'type' => 'linear'],
        ]));

        $service = app(QrCodeGeneratorService::class);

        $png = $service->generatePng($qrCode, 200);
        $this->assertNotEmpty($png);
        $this->assertStringStartsWith("\x89PNG", $png);

        $svg = $service->generateSvg($qrCode);
        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('qrGrad', $svg);
    }

    public function test_v1_engine_is_default(): void
    {
        config(['qrcode.generator_engine' => 'v1']);

        $qrCode = new QrCode([
            'type' => QrCodeType::Url,
            'content_data' => ['url' => 'https://example.com/v1-test'],
            'is_dynamic' => false,
        ]);

        $qrCode->setRelation('design', new QrDesign([
            'dot_style' => 'square',
        ]));

        $png = app(QrCodeGeneratorService::class)->generatePng($qrCode, 120);
        $this->assertStringStartsWith("\x89PNG", $png);
    }

    public function test_svg_center_icon_is_embedded_in_qr_png(): void
    {
        $iconPath = public_path('icons/qr-center-icons/comment-sms.svg');
        if (! file_exists($iconPath)) {
            $this->markTestSkipped('comment-sms.svg icon is not available.');
        }

        config(['qrcode.generator_engine' => 'v2']);

        $base = new QrCode([
            'type' => QrCodeType::Url,
            'content_data' => ['url' => 'https://example.com/icon-test'],
            'is_dynamic' => false,
        ]);
        $base->setRelation('design', new QrDesign(['dot_style' => 'square']));

        $withIcon = clone $base;
        $withIcon->setRelation('design', new QrDesign([
            'dot_style' => 'square',
            'logo_path' => 'icons/qr-center-icons/comment-sms.svg',
        ]));

        $service = app(QrCodeGeneratorService::class);
        $pngWithout = $service->generatePng($base, 200);
        $pngWith = $service->generatePng($withIcon, 200);

        $this->assertStringStartsWith("\x89PNG", $pngWith);
        $this->assertNotSame($pngWithout, $pngWith);
    }

    public function test_embedded_icon_preserves_aspect_ratio(): void
    {
        $iconPath = public_path('icons/qr-center-icons/bitcoin-sign.svg');
        if (! file_exists($iconPath)) {
            $this->markTestSkipped('bitcoin-sign.svg icon is not available.');
        }

        $engine = app(ChillerlanGdEngine::class);
        $ref = new ReflectionClass($engine);
        $embedLogo = $ref->getMethod('embedLogoGd');
        $embedLogo->setAccessible(true);

        $size = 500;
        $canvas = imagecreatetruecolor($size, $size);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);

        $embedLogo->invoke($engine, $canvas, $iconPath, $size);

        $bounds = $this->darkInkBounds($canvas, $size);
        imagedestroy($canvas);

        $this->assertGreaterThan($bounds['width'], $bounds['height'], 'Tall icons should not be stretched wider than they are high.');
    }

    public function test_icon_can_be_tinted_to_match_foreground_color(): void
    {
        $iconPath = public_path('icons/qr-center-icons/bitcoin-sign.svg');
        if (! file_exists($iconPath)) {
            $this->markTestSkipped('bitcoin-sign.svg icon is not available.');
        }

        $engine = app(ChillerlanGdEngine::class);
        $ref = new ReflectionClass($engine);
        $embedLogo = $ref->getMethod('embedLogoGd');
        $embedLogo->setAccessible(true);

        $design = new QrDesign([
            'fg_color' => '#c2410c',
            'logo_path' => 'icons/qr-center-icons/bitcoin-sign.svg',
            'logo_match_fg_color' => true,
        ]);

        $size = 500;
        $canvas = imagecreatetruecolor($size, $size);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);

        $embedLogo->invoke($engine, $canvas, $iconPath, $size, $design);

        $sample = imagecolorat($canvas, (int) ($size / 2), (int) ($size / 2));
        $r = ($sample >> 16) & 0xFF;
        $g = ($sample >> 8) & 0xFF;
        $b = $sample & 0xFF;

        imagedestroy($canvas);

        $this->assertGreaterThan(80, $r, 'Tinted icon should pick up the warm foreground color.');
        $this->assertLessThan($r, $g + $b);
    }

    /**
     * @return array{width: int, height: int}
     */
    private function darkInkBounds(\GdImage $gd, int $size): array
    {
        $minX = $size;
        $minY = $size;
        $maxX = 0;
        $maxY = 0;

        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $rgb = imagecolorat($gd, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                if ($r > 40 || $g > 40 || $b > 40) {
                    continue;
                }

                $minX = min($minX, $x);
                $minY = min($minY, $y);
                $maxX = max($maxX, $x);
                $maxY = max($maxY, $y);
            }
        }

        return [
            'width' => $maxX - $minX + 1,
            'height' => $maxY - $minY + 1,
        ];
    }

    public function test_svg_engine_falls_back_to_gd_when_rasterization_unavailable(): void
    {
        $engine = app(ChillerlanSvgEngine::class);
        $qrCode = new QrCode([
            'type' => QrCodeType::Url,
            'content_data' => ['url' => 'https://example.com/fallback'],
            'is_dynamic' => false,
        ]);
        $qrCode->setRelation('design', new QrDesign());

        $png = $engine->generatePng($qrCode, 100);
        $this->assertStringStartsWith("\x89PNG", $png);
    }
}
