<?php

namespace App\QRCode\Output;

use App\QRCode\SvgQROptions;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QRMarkupSVG;
use function array_any;

class SvgQROutput extends QRMarkupSVG
{
    private const FINDER_TYPES = [
        QRMatrix::M_FINDER_DARK,
        QRMatrix::M_FINDER,
        QRMatrix::M_FINDER_DOT,
    ];

    private const ALIGNMENT_TYPES = [
        QRMatrix::M_ALIGNMENT_DARK,
        QRMatrix::M_ALIGNMENT,
    ];

    protected function moduleTransform(int $x, int $y, int $M_TYPE, int $M_TYPE_LAYER): ?string
    {
        if (! $this->drawLightModules && ! (($M_TYPE & QRMatrix::IS_DARK) === QRMatrix::IS_DARK)) {
            return null;
        }

        $options = $this->options instanceof SvgQROptions ? $this->options : null;
        $bodyShape = $options?->bodyShape ?? 'square';
        $eyeFrameShape = $options?->eyeFrameShape ?? 'square';
        $eyeBallShape = $options?->eyeBallShape ?? 'square';

        if ($M_TYPE === QRMatrix::M_FINDER_DOT) {
            return $this->shapePath($x, $y, $eyeBallShape, true);
        }

        if (in_array($M_TYPE, self::FINDER_TYPES, true) || in_array($M_TYPE, self::ALIGNMENT_TYPES, true)) {
            $isDark = $this->matrix->isDark($M_TYPE);
            $shape = $isDark ? $eyeFrameShape : 'square';

            return $this->shapePath($x, $y, $shape, $isDark);
        }

        if (in_array($M_TYPE, [QRMatrix::M_SEPARATOR, QRMatrix::M_QUIETZONE], true)) {
            return "M{$x} {$y} h1 v1 h-1Z";
        }

        $isDark = $this->matrix->isDark($M_TYPE);
        $shape = $isDark ? $bodyShape : 'square';

        if ($this->shouldUseCircularModules($shape) && ! array_any($this->keepAsSquare, fn ($type) => ($M_TYPE & $type) === $type)) {
            return $this->circlePath($x, $y, $this->radiusForShape($shape));
        }

        if ($this->shouldUseRoundedRect($shape)) {
            return $this->roundedRectPath($x, $y, $this->cornerRadiusForShape($shape));
        }

        if ($shape === 'diamond' && $isDark) {
            return $this->diamondPath($x, $y);
        }

        return "M{$x} {$y} h1 v1 h-1Z";
    }

    protected function circlePath(int $x, int $y, float $radius): string
    {
        $d = $radius * 2;
        $ix = ($x + 0.5 - $radius);
        $iy = ($y + 0.5);

        return "M{$ix} {$iy} a{$radius} {$radius} 0 1 0 {$d} 0 a{$radius} {$radius} 0 1 0 -{$d} 0Z";
    }

    protected function roundedRectPath(int $x, int $y, float $radius): string
    {
        $r = min($radius, 0.45);
        $x1 = $x;
        $y1 = $y;
        $x2 = $x + 1;
        $y2 = $y + 1;

        return sprintf(
            'M%.3f %.3f h%.3f a%.3f %.3f 0 0 1 %.3f %.3f v%.3f a%.3f %.3f 0 0 1 -%.3f %.3f h-%.3f a%.3f %.3f 0 0 1 -%.3f -%.3f v-%.3f a%.3f %.3f 0 0 1 %.3f -%.3fZ',
            $x1 + $r, $y1,
            1 - (2 * $r),
            $r, $r, $r, $r,
            1 - (2 * $r),
            $r, $r, $r, $r,
            1 - (2 * $r),
            $r, $r, $r, $r,
            1 - (2 * $r),
            $r, $r, $r, $r,
        );
    }

    protected function diamondPath(int $x, int $y): string
    {
        $cx = $x + 0.5;
        $cy = $y + 0.5;

        return "M{$cx} {$y} L" . ($x + 1) . " {$cy} L{$cx} " . ($y + 1) . " L{$x} {$cy}Z";
    }

    protected function shapePath(int $x, int $y, string $shape, bool $isDark): string
    {
        if (! $isDark) {
            return "M{$x} {$y} h1 v1 h-1Z";
        }

        if (in_array($shape, ['circle', 'dot', 'dots', 'dots_small'], true)) {
            return $this->circlePath($x, $y, $shape === 'dots_small' ? 0.35 : 0.45);
        }

        if (in_array($shape, ['rounded', 'rounded_square', 'squircle', 'cushion', 'rounded_single'], true)) {
            return $this->roundedRectPath($x, $y, 0.2);
        }

        if (in_array($shape, ['extra_rounded', 'extra_rounded_connected', 'classy_rounded'], true)) {
            return $this->roundedRectPath($x, $y, 0.35);
        }

        if ($shape === 'diamond') {
            return $this->diamondPath($x, $y);
        }

        if (in_array($shape, ['leaf', 'star', 'octagon', 'plus', 'grid'], true)) {
            return $this->circlePath($x, $y, 0.4);
        }

        return "M{$x} {$y} h1 v1 h-1Z";
    }

    protected function shouldUseCircularModules(string $shape): bool
    {
        return in_array($shape, [
            'dots', 'dots_small', 'rounded_connected', 'extra_rounded_connected',
            'classy', 'classy_rounded',
        ], true);
    }

    protected function shouldUseRoundedRect(string $shape): bool
    {
        return in_array($shape, [
            'rounded_square', 'extra_rounded', 'leaf', 'octagon',
        ], true);
    }

    protected function radiusForShape(string $shape): float
    {
        return match ($shape) {
            'dots_small' => 0.35,
            'classy', 'classy_rounded' => 0.42,
            default => 0.45,
        };
    }

    protected function cornerRadiusForShape(string $shape): float
    {
        return match ($shape) {
            'extra_rounded' => 0.35,
            default => 0.2,
        };
    }
}
