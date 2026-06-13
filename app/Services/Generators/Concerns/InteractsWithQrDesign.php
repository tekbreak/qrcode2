<?php

namespace App\Services\Generators\Concerns;

use App\Models\QrDesign;
use Choowx\RasterizeSvg\Svg;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;

trait InteractsWithQrDesign
{
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
                if (! isset($colorCache[$key])) {
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

    protected function embedLogoGd(\GdImage $gd, string $logoPath, int $qrSize, ?QrDesign $design = null): void
    {
        $logoGd = $this->loadLogoAsGd($logoPath);
        if (! $logoGd) {
            return;
        }

        $srcW = imagesx($logoGd);
        $srcH = imagesy($logoGd);
        if ($srcW <= 0 || $srcH <= 0) {
            imagedestroy($logoGd);

            return;
        }

        if ($design?->logo_match_fg_color && str_starts_with($design->logo_path ?? '', 'icons/')) {
            $this->tintLogoGd($logoGd, $design);
        }

        $maxLogoSize = (int) ($qrSize * 0.22);
        $scale = min($maxLogoSize / $srcW, $maxLogoSize / $srcH);
        $drawW = max(1, (int) round($srcW * $scale));
        $drawH = max(1, (int) round($srcH * $scale));

        $padding = 8;
        $bgW = $drawW + $padding;
        $bgH = $drawH + $padding;
        $bgX = (int) (($qrSize - $bgW) / 2);
        $bgY = (int) (($qrSize - $bgH) / 2);

        $white = imagecolorallocate($gd, 255, 255, 255);
        imagefilledrectangle($gd, $bgX, $bgY, $bgX + $bgW, $bgY + $bgH, $white);

        $x = (int) (($qrSize - $drawW) / 2);
        $y = (int) (($qrSize - $drawH) / 2);
        imagecopyresampled($gd, $logoGd, $x, $y, 0, 0, $drawW, $drawH, $srcW, $srcH);
        imagedestroy($logoGd);
    }

    protected function tintLogoGd(\GdImage $logoGd, QrDesign $design): void
    {
        $gradient = $design->gradient;
        $useGradient = $gradient && isset($gradient['color1'], $gradient['color2']);
        $color1 = $this->hexToRgb($useGradient ? $gradient['color1'] : ($design->fg_color ?: '#000000'));
        $color2 = $this->hexToRgb($useGradient ? $gradient['color2'] : ($design->fg_color ?: '#000000'));
        $gradientType = $useGradient ? ($gradient['type'] ?? 'linear') : 'solid';

        $width = imagesx($logoGd);
        $height = imagesy($logoGd);
        $tinted = imagecreatetruecolor($width, $height);
        imagealphablending($tinted, false);
        imagesavealpha($tinted, true);

        $transparent = imagecolorallocatealpha($tinted, 0, 0, 0, 127);
        imagefill($tinted, 0, 0, $transparent);

        $centerX = $width / 2;
        $centerY = $height / 2;
        $maxDist = $gradientType === 'radial'
            ? sqrt($centerX * $centerX + $centerY * $centerY)
            : ($width + $height);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgba = imagecolorat($logoGd, $x, $y);
                $alpha = ($rgba >> 24) & 0x7F;
                if ($alpha >= 127) {
                    continue;
                }

                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;
                $luma = ($r + $g + $b) / 3;

                if ($luma > 250) {
                    continue;
                }

                if ($useGradient) {
                    if ($gradientType === 'radial') {
                        $ratio = min(1.0, sqrt(($x - $centerX) ** 2 + ($y - $centerY) ** 2) / max(1, $maxDist));
                    } else {
                        $ratio = ($x + $y) / max(1, $maxDist);
                    }

                    $fr = (int) ($color1[0] + ($color2[0] - $color1[0]) * $ratio);
                    $fg = (int) ($color1[1] + ($color2[1] - $color1[1]) * $ratio);
                    $fb = (int) ($color1[2] + ($color2[2] - $color1[2]) * $ratio);
                } else {
                    [$fr, $fg, $fb] = $color1;
                }

                $inkAlpha = (int) round(127 * (1 - min(1.0, (255 - $luma) / 255)));
                $newColor = imagecolorallocatealpha($tinted, $fr, $fg, $fb, $inkAlpha);
                imagesetpixel($tinted, $x, $y, $newColor);
            }
        }

        imagecopy($logoGd, $tinted, 0, 0, 0, 0, $width, $height);
        imagedestroy($tinted);
    }

    protected function loadLogoAsGd(string $logoPath): ?\GdImage
    {
        $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
        if ($ext === 'svg') {
            return $this->loadSvgAsGd($logoPath);
        }

        $logoInfo = @getimagesize($logoPath);
        if (! $logoInfo) {
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
        if (! file_exists($svgPath)) {
            return null;
        }

        $cachePath = $this->svgLogoCachePath($svgPath);
        if ($cachePath && file_exists($cachePath)) {
            $cached = @imagecreatefrompng($cachePath);
            if ($cached) {
                return $cached;
            }
        }

        $pngBlob = $this->rasterizeSvgFileToPngBlob($svgPath);
        if (! $pngBlob) {
            return null;
        }

        $gd = @imagecreatefromstring($pngBlob);
        if (! $gd) {
            return null;
        }

        if ($cachePath) {
            @mkdir(dirname($cachePath), 0755, true);
            file_put_contents($cachePath, $pngBlob);
        }

        return $gd;
    }

    protected function svgLogoCachePath(string $svgPath): ?string
    {
        $real = realpath($svgPath);
        if (! $real) {
            return null;
        }

        return storage_path('app/cache/qr-icons/' . md5($real . (string) filemtime($real)) . '.png');
    }

    protected function rasterizeSvgFileToPngBlob(string $svgPath): ?string
    {
        if (extension_loaded('imagick')) {
            try {
                $manager = new ImageManager(new ImagickDriver());
                $pngBlob = $manager->read($svgPath)->toPng();
                if ($this->isValidPngBlob($pngBlob)) {
                    return $pngBlob;
                }
            } catch (\Throwable $e) {
                logger()->warning('Imagick SVG logo conversion failed: ' . $e->getMessage());
            }
        }

        $svgContent = @file_get_contents($svgPath);
        if ($svgContent) {
            try {
                $pngBlob = Svg::make($svgContent)->toPng();
                if ($this->isValidPngBlob($pngBlob)) {
                    return $pngBlob;
                }
            } catch (\Throwable $e) {
                logger()->warning('Choowx SVG logo conversion failed: ' . $e->getMessage());
            }
        }

        return $this->rasterizeSvgViaImageMagickCli($svgPath);
    }

    protected function isValidPngBlob(?string $blob): bool
    {
        return is_string($blob) && strlen($blob) > 8 && str_starts_with($blob, "\x89PNG\r\n\x1a\n");
    }

    protected function rasterizeSvgViaImageMagickCli(string $svgPath): ?string
    {
        $magick = trim((string) shell_exec('command -v magick 2>/dev/null'));
        if ($magick === '') {
            $magick = trim((string) shell_exec('command -v convert 2>/dev/null'));
        }
        if ($magick === '') {
            return null;
        }

        $tmpOut = tempnam(sys_get_temp_dir(), 'qr_logo_');
        if ($tmpOut === false) {
            return null;
        }
        $tmpOut .= '.png';

        $cmd = sprintf(
            '%s -background none -density 200 %s %s 2>/dev/null',
            escapeshellarg($magick),
            escapeshellarg($svgPath),
            escapeshellarg($tmpOut),
        );

        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0 || ! is_file($tmpOut) || filesize($tmpOut) === 0) {
            @unlink($tmpOut);

            return null;
        }

        $blob = file_get_contents($tmpOut);
        @unlink($tmpOut);

        return $this->isValidPngBlob($blob) ? $blob : null;
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

    protected function rasterizeSvgToGd(string $svg, int $size): ?\GdImage
    {
        try {
            $pngBlob = Svg::make($svg)->toPng();
            $gd = @imagecreatefromstring($pngBlob);

            if (! $gd) {
                return null;
            }

            $srcW = imagesx($gd);
            $srcH = imagesy($gd);

            if ($srcW === $size && $srcH === $size) {
                return $gd;
            }

            $resized = imagecreatetruecolor($size, $size);
            imagecopyresampled($resized, $gd, 0, 0, 0, 0, $size, $size, $srcW, $srcH);
            imagedestroy($gd);

            return $resized;
        } catch (\Throwable $e) {
            logger()->warning('SVG rasterization failed: ' . $e->getMessage());

            return null;
        }
    }

    protected function gdToPng(\GdImage $gd): string
    {
        ob_start();
        imagepng($gd);
        $output = ob_get_clean();
        imagedestroy($gd);

        return $output;
    }

    protected function finalizePngFromGd(\GdImage $gd, QrDesign $design, int $size): string
    {
        $fgColor = $design->fg_color ?: '#000000';
        $bgColor = $design->bg_color ?: '#FFFFFF';
        $gradient = $design->gradient;

        if ($design->logo_path) {
            $logoFile = $this->resolveLogoPath($design->logo_path);
            if ($logoFile && file_exists($logoFile)) {
                $this->embedLogoGd($gd, $logoFile, $size, $design);
            }
        }

        $frameStyle = $design->frame_style;
        if ($frameStyle) {
            $gd = $this->applyFrameGd($gd, $size, $frameStyle, $design->frame_text ?? 'Scan me!', $fgColor, $bgColor);
        }

        return $this->gdToPng($gd);
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

    protected function designShapes(QrDesign $design): array
    {
        return [
            'body' => $this->normalizeBodyShape($design->dot_style ?? 'square'),
            'eyeFrame' => $this->normalizeEyeFrameShape($design->eye_frame_style ?? $design->eye_style ?? 'square'),
            'eyeBall' => $this->normalizeEyeBallShape($design->eye_ball_style ?? 'square'),
        ];
    }
}
