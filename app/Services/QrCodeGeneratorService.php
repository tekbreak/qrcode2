<?php

namespace App\Services;

use App\Models\QrCode;
use App\Models\QrDesign;
use App\QRCode\CustomQROptions;
use App\QRCode\Output\CustomQRGdImage;
use chillerlan\QRCode\{QRCode as QRCodeLib, QROptions};
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Common\Version;
use chillerlan\QRCode\Output\QROutputInterface;
use Choowx\RasterizeSvg\Svg;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;

class QrCodeGeneratorService
{
    public function generatePng(QrCode $qrCode, int $size = 400): string
    {
        $design = $qrCode->design ?? new QrDesign();
        $content = $qrCode->getEncodedContent();

        // Render at ~2x target size for crisp shapes without excessive downscaling.
        // A QR v5 (41 modules) at size=400: scale=ceil(800/41)=20 → native 820px → resize to 400px.
        $scale = max(10, (int) ceil(($size * 2) / 45));

        $options = new CustomQROptions([
            'version' => Version::AUTO,
            'outputType' => QROutputInterface::CUSTOM,
            'outputInterface' => CustomQRGdImage::class,
            'eccLevel' => EccLevel::H,
            'scale' => $scale,
            'imageBase64' => false,
            'bgColor' => $this->hexToRgb($design->bg_color ?: '#FFFFFF'),
            'imageTransparent' => false,
            'keepASCIINewline' => true,
            'quietzoneSize' => 2,
            'bodyShape' => $this->normalizeBodyShape($design->dot_style ?? 'square'),
            'eyeFrameShape' => $this->normalizeEyeFrameShape($design->eye_frame_style ?? $design->eye_style ?? 'square'),
            'eyeBallShape' => $this->normalizeEyeBallShape($design->eye_ball_style ?? 'square'),
        ]);

        $imageData = (new QRCodeLib($options))->render($content);

        $gd = imagecreatefromstring($imageData);
        $srcW = imagesx($gd);
        $srcH = imagesy($gd);

        // Only resize when needed; avoid upscaling (causes fuzzy/irregular modules)
        if ($srcW === $size && $srcH === $size) {
            $resized = $gd;
        } else {
            $resized = imagecreatetruecolor($size, $size);
            imagecopyresampled($resized, $gd, 0, 0, 0, 0, $size, $size, $srcW, $srcH);
            imagedestroy($gd);
        }

        $fgColor = $design->fg_color ?: '#000000';
        $bgColor = $design->bg_color ?: '#FFFFFF';
        $gradient = $design->gradient;

        if ($gradient && isset($gradient['color1'], $gradient['color2'])) {
            $this->applyGradientGd($resized, $gradient['color1'], $gradient['color2'], $gradient['type'] ?? 'linear', $bgColor);
        } elseif ($fgColor !== '#000000' || $bgColor !== '#FFFFFF') {
            $this->applyFgColorGd($resized, $fgColor, $bgColor);
        }

        if ($design->logo_path) {
            $logoFile = $this->resolveLogoPath($design->logo_path);
            if ($logoFile && file_exists($logoFile)) {
                $this->embedLogoGd($resized, $logoFile, $size);
            }
        }

        $frameStyle = $design->frame_style;
        if ($frameStyle) {
            $resized = $this->applyFrameGd($resized, $size, $frameStyle, $design->frame_text ?? 'Scan me!', $fgColor, $bgColor);
        }

        ob_start();
        imagepng($resized);
        $output = ob_get_clean();
        imagedestroy($resized);

        return $output;
    }

    public function generateSvg(QrCode $qrCode): string
    {
        $design = $qrCode->design ?? new QrDesign();
        $content = $qrCode->getEncodedContent();
        $useCircles = in_array($design->dot_style, ['dots', 'diamond']);
        $eyeStyle = $design->eye_style ?? 'square';

        $keepSquare = match (true) {
            $useCircles && $eyeStyle === 'square' => [
                QRMatrix::M_FINDER_DARK, QRMatrix::M_FINDER, QRMatrix::M_FINDER_DOT,
                QRMatrix::M_ALIGNMENT_DARK, QRMatrix::M_ALIGNMENT,
            ],
            $useCircles && $eyeStyle === 'rounded' => [
                QRMatrix::M_FINDER_DARK, QRMatrix::M_FINDER,
                QRMatrix::M_ALIGNMENT_DARK, QRMatrix::M_ALIGNMENT,
            ],
            default => [],
        };

        $options = new QROptions([
            'version' => Version::AUTO,
            'outputType' => QROutputInterface::MARKUP_SVG,
            'eccLevel' => EccLevel::H,
            'svgUseCssColors' => false,
            'imageBase64' => false,
            'markupDark' => $design->fg_color ?: '#000000',
            'markupLight' => $design->bg_color ?: '#FFFFFF',
            'drawCircularModules' => $useCircles,
            'circleRadius' => 0.45,
            'keepAsSquare' => $keepSquare,
            'quietzoneSize' => 2,
        ]);

        return (new QRCodeLib($options))->render($content);
    }

    public function generateBase64Preview(QrCode $qrCode, int $size = 300): string
    {
        $pngData = $this->generatePng($qrCode, $size);
        return 'data:image/png;base64,' . base64_encode($pngData);
    }

    protected function applyFgColorGd(\GdImage $gd, string $fgHex, string $bgHex): void
    {
        $fg = $this->hexToRgb($fgHex);
        $bg = $this->hexToRgb($bgHex);
        $width = imagesx($gd);
        $height = imagesy($gd);

        $fgAlloc = imagecolorallocate($gd, $fg[0], $fg[1], $fg[2]);
        $bgAlloc = imagecolorallocate($gd, $bg[0], $bg[1], $bg[2]);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($gd, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $brightness = ($r + $g + $b) / 3;
                imagesetpixel($gd, $x, $y, $brightness < 128 ? $fgAlloc : $bgAlloc);
            }
        }
    }

    protected function applyGradientGd(\GdImage $gd, string $color1Hex, string $color2Hex, string $direction, string $bgHex): void
    {
        $c1 = $this->hexToRgb($color1Hex);
        $c2 = $this->hexToRgb($color2Hex);
        $bg = $this->hexToRgb($bgHex);
        $width = imagesx($gd);
        $height = imagesy($gd);

        $bgAlloc = imagecolorallocate($gd, $bg[0], $bg[1], $bg[2]);
        $isRadial = $direction === 'radial';
        $cx = $width / 2;
        $cy = $height / 2;
        $maxDist = $isRadial ? sqrt($cx * $cx + $cy * $cy) : ($width + $height);

        $colorCache = [];

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($gd, $x, $y);
                $brightness = ((($rgb >> 16) & 0xFF) + (($rgb >> 8) & 0xFF) + ($rgb & 0xFF)) / 3;

                if ($brightness >= 128) {
                    imagesetpixel($gd, $x, $y, $bgAlloc);
                    continue;
                }

                if ($isRadial) {
                    $ratio = min(1.0, sqrt(($x - $cx) ** 2 + ($y - $cy) ** 2) / $maxDist);
                } else {
                    $ratio = ($x + $y) / $maxDist;
                }

                $key = (int) ($ratio * 255);
                if (!isset($colorCache[$key])) {
                    $r = (int) ($c1[0] + ($c2[0] - $c1[0]) * ($key / 255));
                    $g = (int) ($c1[1] + ($c2[1] - $c1[1]) * ($key / 255));
                    $b = (int) ($c1[2] + ($c2[2] - $c1[2]) * ($key / 255));
                    $colorCache[$key] = imagecolorallocate($gd, $r, $g, $b);
                }

                imagesetpixel($gd, $x, $y, $colorCache[$key]);
            }
        }
    }

    protected function resolveLogoPath(string $logoPath): ?string
    {
        if (str_starts_with($logoPath, '/')) {
            return $logoPath;
        }
        if (str_starts_with($logoPath, 'icons/')) {
            return public_path($logoPath);
        }
        return storage_path('app/public/' . $logoPath);
    }

    protected function embedLogoGd(\GdImage $gd, string $logoPath, int $qrSize): void
    {
        $logoGd = $this->loadLogoAsGd($logoPath);
        if (!$logoGd) {
            return;
        }

        $logoSize = (int) ($qrSize * 0.22);
        $bgSize = $logoSize + 8;
        $bgX = (int) (($qrSize - $bgSize) / 2);
        $bgY = (int) (($qrSize - $bgSize) / 2);

        $white = imagecolorallocate($gd, 255, 255, 255);
        imagefilledrectangle($gd, $bgX, $bgY, $bgX + $bgSize, $bgY + $bgSize, $white);

        $x = (int) (($qrSize - $logoSize) / 2);
        $y = (int) (($qrSize - $logoSize) / 2);
        imagecopyresampled($gd, $logoGd, $x, $y, 0, 0, $logoSize, $logoSize, imagesx($logoGd), imagesy($logoGd));
        imagedestroy($logoGd);
    }

    protected function loadLogoAsGd(string $logoPath): ?\GdImage
    {
        $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
        if ($ext === 'svg') {
            return $this->loadSvgAsGd($logoPath);
        }

        $logoInfo = @getimagesize($logoPath);
        if (!$logoInfo) {
            return null;
        }

        return match ($logoInfo[2]) {
            IMAGETYPE_PNG => @imagecreatefrompng($logoPath),
            IMAGETYPE_JPEG => @imagecreatefromjpeg($logoPath),
            IMAGETYPE_GIF => @imagecreatefromgif($logoPath),
            IMAGETYPE_WEBP => @imagecreatefromwebp($logoPath),
            default => null,
        };
    }

    protected function loadSvgAsGd(string $svgPath): ?\GdImage
    {
        $svgContent = @file_get_contents($svgPath);
        if (!$svgContent) {
            return null;
        }

        try {
            if (extension_loaded('imagick')) {
                $manager = new ImageManager(new ImagickDriver());
                $image = $manager->read($svgPath);
                $pngBlob = $image->toPng();
            } else {
                $pngBlob = Svg::make($svgContent)->toPng();
            }
            $gd = @imagecreatefromstring($pngBlob);
            return $gd ?: null;
        } catch (\Throwable $e) {
            logger()->warning('SVG logo conversion failed: ' . $e->getMessage());
            return null;
        }
    }

    protected function applyFrameGd(\GdImage $qrGd, int $qrSize, string $frameStyle, string $text, string $fgColor, string $bgColor): \GdImage
    {
        $padding = (int) ($qrSize * 0.06);
        $textHeight = (int) ($qrSize * 0.12);
        $totalWidth = $qrSize + $padding * 2;
        $totalHeight = $qrSize + $textHeight + $padding * 2;

        $canvas = imagecreatetruecolor($totalWidth, $totalHeight);
        $bg = $this->hexToRgb($bgColor);
        $fg = $this->hexToRgb($fgColor);
        $bgAlloc = imagecolorallocate($canvas, $bg[0], $bg[1], $bg[2]);
        $fgAlloc = imagecolorallocate($canvas, $fg[0], $fg[1], $fg[2]);

        imagefill($canvas, 0, 0, $bgAlloc);

        if ($frameStyle === 'simple' || $frameStyle === 'rounded') {
            imagerectangle($canvas, 0, 0, $totalWidth - 1, $totalHeight - 1, $fgAlloc);
            imagerectangle($canvas, 1, 1, $totalWidth - 2, $totalHeight - 2, $fgAlloc);
        } elseif ($frameStyle === 'banner') {
            $bannerY = $qrSize + $padding;
            imagefilledrectangle($canvas, 0, $bannerY, $totalWidth - 1, $totalHeight - 1, $fgAlloc);
        }

        imagecopy($canvas, $qrGd, $padding, $padding, 0, 0, $qrSize, $qrSize);

        if ($text) {
            $textColor = $frameStyle === 'banner' ? $bgAlloc : $fgAlloc;
            $fontSize = max(2, (int) ($qrSize * 0.04));
            $gdFontSize = min(5, max(1, (int) ($fontSize / 3)));
            $textWidth = imagefontwidth($gdFontSize) * strlen($text);
            $textX = (int) (($totalWidth - $textWidth) / 2);
            $textY = $qrSize + $padding + (int) ($textHeight * 0.3);
            imagestring($canvas, $gdFontSize, $textX, $textY, $text, $textColor);
        }

        imagedestroy($qrGd);
        return $canvas;
    }

    protected function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    protected function normalizeBodyShape(string $style): string
    {
        return array_key_exists($style, config('qr_shapes.body', [])) ? $style : 'square';
    }

    protected function normalizeEyeFrameShape(string $style): string
    {
        return array_key_exists($style, config('qr_shapes.eye_frame', [])) ? $style : 'square';
    }

    protected function normalizeEyeBallShape(string $style): string
    {
        return array_key_exists($style, config('qr_shapes.eye_ball', [])) ? $style : 'square';
    }
}
