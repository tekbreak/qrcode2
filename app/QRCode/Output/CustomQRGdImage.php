<?php

namespace App\QRCode\Output;

use App\QRCode\CustomQROptions;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QRGdImagePNG;
use function intdiv;

class CustomQRGdImage extends QRGdImagePNG
{
    protected bool $drawCircularModules = false;

    protected array $keepAsSquare = [];

    private array $drawnFinderDots = [];

    private array $drawnFinderFrames = [];

    private const FINDER_TYPES = [
        QRMatrix::M_FINDER_DARK,
        QRMatrix::M_FINDER,
        QRMatrix::M_FINDER_DOT,
    ];

    private const ALIGNMENT_TYPES = [
        QRMatrix::M_ALIGNMENT_DARK,
        QRMatrix::M_ALIGNMENT,
    ];

    protected function module(int $x, int $y, int $M_TYPE): void
    {
        if (! $this->drawLightModules && ! $this->matrix->isDark($M_TYPE)) {
            return;
        }

        $color = $this->getModuleValue($M_TYPE);
        $isDark = $this->matrix->isDark($M_TYPE);

        $x1 = $x * $this->scale;
        $y1 = $y * $this->scale;
        $x2 = ($x + 1) * $this->scale;
        $y2 = ($y + 1) * $this->scale;
        $cx = $x1 + intdiv($this->scale, 2);
        $cy = $y1 + intdiv($this->scale, 2);

        $bodyShape = $this->options instanceof CustomQROptions ? $this->options->bodyShape : 'square';
        $eyeFrameShape = $this->options instanceof CustomQROptions ? $this->options->eyeFrameShape : 'square';
        $eyeBallShape = $this->options instanceof CustomQROptions ? $this->options->eyeBallShape : 'square';

        if ($M_TYPE === QRMatrix::M_FINDER_DOT) {
            $this->handleFinderDot($x, $y, $color, $eyeBallShape);
            return;
        }

        if (in_array($M_TYPE, self::FINDER_TYPES, true)) {
            if ($eyeFrameShape !== 'square' && $this->handleFinderFrame($x, $y, $isDark, $color, $eyeFrameShape)) {
                return;
            }
            $shape = $isDark ? $eyeFrameShape : 'square';
            $this->drawModule($x1, $y1, $x2, $y2, $cx, $cy, $color, $shape, $x, $y);
            return;
        }

        if (in_array($M_TYPE, self::ALIGNMENT_TYPES, true)) {
            $shape = $isDark ? $eyeFrameShape : 'square';
            $this->drawModule($x1, $y1, $x2, $y2, $cx, $cy, $color, $shape, $x, $y);
            return;
        }

        if (in_array($M_TYPE, [QRMatrix::M_SEPARATOR, QRMatrix::M_QUIETZONE], true)) {
            $this->fillRect($x1, $y1, $x2, $y2, $color);
            return;
        }

        $shape = $isDark ? $bodyShape : 'square';
        $this->drawModule($x1, $y1, $x2, $y2, $cx, $cy, $color, $shape, $x, $y);
    }

    private function handleFinderDot(int $x, int $y, int $color, string $shape): void
    {
        $topX = $x;
        $topY = $y;
        while ($topX > 0 && $this->matrix->checkType($topX - 1, $topY, QRMatrix::M_FINDER_DOT)) {
            $topX--;
        }
        while ($topY > 0 && $this->matrix->checkType($topX, $topY - 1, QRMatrix::M_FINDER_DOT)) {
            $topY--;
        }

        $key = "{$topX},{$topY}";
        if (isset($this->drawnFinderDots[$key])) {
            return;
        }
        $this->drawnFinderDots[$key] = true;

        $s = $this->scale;
        $bx1 = $topX * $s;
        $by1 = $topY * $s;
        $bx2 = ($topX + 3) * $s;
        $by2 = ($topY + 3) * $s;
        $bcx = $bx1 + intdiv($bx2 - $bx1, 2);
        $bcy = $by1 + intdiv($by2 - $by1, 2);
        $bs = 3 * $s;

        switch ($shape) {
            case 'circle':
            case 'dot':
            case 'dots':
                imagefilledellipse($this->image, $bcx, $bcy, $bs, $bs, $color);
                break;
            case 'rounded':
            case 'rounded_square':
                $this->drawRoundedRect($bx1, $by1, $bx2, $by2, $color, (int) ($bs * 0.15));
                break;
            case 'extra_rounded':
            case 'squircle':
                $this->drawRoundedRect($bx1, $by1, $bx2, $by2, $color, (int) ($bs * 0.25));
                break;
            case 'diamond':
                imagefilledpolygon($this->image, [$bcx, $by1, $bx2, $bcy, $bcx, $by2, $bx1, $bcy], $color);
                break;
            case 'leaf':
                $r = (int) ($bs * 0.25);
                $this->drawCornerRoundedRect($bx1, $by1, $bx2, $by2, $color, $r, true, false, true, false);
                break;
            case 'bars_vertical':
                $bw = max(2, intdiv($bs, 4));
                $gap = intdiv($bs - 3 * $bw, 2);
                $this->fillRect($bx1 + $gap, $by1, $bx1 + $gap + $bw, $by2, $color);
                $this->fillRect($bcx - intdiv($bw, 2), $by1, $bcx + intdiv($bw, 2), $by2, $color);
                $this->fillRect($bx2 - $gap - $bw, $by1, $bx2 - $gap, $by2, $color);
                break;
            case 'bars_horizontal':
                $bh = max(2, intdiv($bs, 4));
                $gap = intdiv($bs - 3 * $bh, 2);
                $this->fillRect($bx1, $by1 + $gap, $bx2, $by1 + $gap + $bh, $color);
                $this->fillRect($bx1, $bcy - intdiv($bh, 2), $bx2, $bcy + intdiv($bh, 2), $color);
                $this->fillRect($bx1, $by2 - $gap - $bh, $bx2, $by2 - $gap, $color);
                break;
            case 'star':
                $this->fillRect($bx1, $by1 + intdiv($bs, 4), $bx2, $by2 - intdiv($bs, 4), $color);
                $this->fillRect($bx1 + intdiv($bs, 4), $by1, $bx2 - intdiv($bs, 4), $by2, $color);
                break;
            case 'octagon':
                $q = intdiv($bs, 3);
                imagefilledpolygon($this->image, [
                    $bx1 + $q, $by1,
                    $bx2 - $q, $by1,
                    $bx2, $by1 + $q,
                    $bx2, $by2 - $q,
                    $bx2 - $q, $by2,
                    $bx1 + $q, $by2,
                    $bx1, $by2 - $q,
                    $bx1, $by1 + $q,
                ], $color);
                break;
            case 'cushion':
                $this->drawRoundedRect($bx1, $by1, $bx2, $by2, $color, (int) ($bs * 0.20));
                break;
            case 'square':
            default:
                $this->fillRect($bx1, $by1, $bx2, $by2, $color);
                break;
        }
    }

    private function getFinderPosition(int $x, int $y): ?array
    {
        $q = $this->options->quietzoneSize;
        $inner = $this->moduleCount - 2 * $q;

        $positions = [
            [$q, $q],
            [$q + $inner - 7, $q],
            [$q, $q + $inner - 7],
        ];

        foreach ($positions as $pos) {
            if ($x >= $pos[0] && $x < $pos[0] + 7 && $y >= $pos[1] && $y < $pos[1] + 7) {
                return $pos;
            }
        }

        return null;
    }

    /**
     * Draw the entire finder outer ring as a single composite shape.
     * Produces smooth, professional frames matching QRCodeMonkey quality.
     */
    private function handleFinderFrame(int $x, int $y, bool $isDark, int $darkColor, string $shape): bool
    {
        $pos = $this->getFinderPosition($x, $y);
        if (! $pos) {
            return false;
        }

        $key = "{$pos[0]},{$pos[1]}";
        if (isset($this->drawnFinderFrames[$key])) {
            return true;
        }

        if (! $isDark) {
            return false;
        }

        $this->drawnFinderFrames[$key] = true;

        $s = $this->scale;
        $bg = $this->options->bgColor;
        $bgColor = imagecolorallocate($this->image, $bg[0], $bg[1], $bg[2]);

        $ox1 = $pos[0] * $s;
        $oy1 = $pos[1] * $s;
        $outerSize = 7 * $s;
        $ox2 = $ox1 + $outerSize;
        $oy2 = $oy1 + $outerSize;

        $ix1 = ($pos[0] + 1) * $s;
        $iy1 = ($pos[1] + 1) * $s;
        $innerSize = 5 * $s;
        $ix2 = $ix1 + $innerSize;
        $iy2 = $iy1 + $innerSize;

        $this->drawFinderShape($ox1, $oy1, $ox2, $oy2, $outerSize, $darkColor, $shape);
        $this->drawFinderShape($ix1, $iy1, $ix2, $iy2, $innerSize, $bgColor, $shape);

        return true;
    }

    private function drawFinderShape(int $x1, int $y1, int $x2, int $y2, int $size, int $color, string $shape): void
    {
        $cx = $x1 + intdiv($size, 2);
        $cy = $y1 + intdiv($size, 2);

        switch ($shape) {
            case 'circle':
            case 'dot':
                imagefilledellipse($this->image, $cx, $cy, $size, $size, $color);
                break;

            case 'rounded':
                $this->drawRoundedRect($x1, $y1, $x2, $y2, $color, (int) ($size * 0.14));
                break;

            case 'cushion':
                $this->drawRoundedRect($x1, $y1, $x2, $y2, $color, (int) ($size * 0.22));
                break;

            case 'rounded_double':
                $this->drawRoundedRect($x1, $y1, $x2, $y2, $color, (int) ($size * 0.18));
                break;

            case 'leaf':
                $r = (int) ($size * 0.25);
                $this->drawCornerRoundedRect($x1, $y1, $x2, $y2, $color, $r, true, false, true, false);
                break;

            case 'octagon':
                $q = intdiv($size, 4);
                imagefilledpolygon($this->image, [
                    $x1 + $q, $y1,
                    $x2 - $q, $y1,
                    $x2, $y1 + $q,
                    $x2, $y2 - $q,
                    $x2 - $q, $y2,
                    $x1 + $q, $y2,
                    $x1, $y2 - $q,
                    $x1, $y1 + $q,
                ], $color);
                break;

            case 'dotted':
                $this->drawRoundedRect($x1, $y1, $x2, $y2, $color, (int) ($size * 0.08));
                break;

            case 'rounded_single':
                $this->drawRoundedRect($x1, $y1, $x2, $y2, $color, (int) ($size * 0.10));
                break;

            case 'square':
            default:
                $this->fillRect($x1, $y1, $x2, $y2, $color);
                break;
        }
    }

    /**
     * Check if a neighboring module at offset is dark.
     */
    private function neighborDark(int $x, int $y, int $dx, int $dy): bool
    {
        $nx = $x + $dx;
        $ny = $y + $dy;
        if ($nx < 0 || $ny < 0 || $nx >= $this->moduleCount || $ny >= $this->moduleCount) {
            return false;
        }
        return $this->matrix->isDark($this->matrix->get($nx, $ny));
    }

    private function drawModule(int $x1, int $y1, int $x2, int $y2, int $cx, int $cy, int $color, string $shape, int $mx = 0, int $my = 0): void
    {
        $s = $this->scale;

        switch ($shape) {
            // --- Connected / neighbor-aware shapes ---
            case 'rounded_connected':
                $this->drawConnectedRounded($x1, $y1, $x2, $y2, $cx, $cy, $color, $mx, $my, (int) ($s * 0.45));
                break;

            case 'extra_rounded_connected':
                $this->drawConnectedRounded($x1, $y1, $x2, $y2, $cx, $cy, $color, $mx, $my, intdiv($s, 2));
                break;

            case 'classy':
                $this->drawClassy($x1, $y1, $x2, $y2, $cx, $cy, $color, $mx, $my, (int) ($s * 0.45), false);
                break;

            case 'classy_rounded':
                $this->drawClassy($x1, $y1, $x2, $y2, $cx, $cy, $color, $mx, $my, intdiv($s, 2), true);
                break;

            // --- Simple per-module shapes ---
            case 'dots':
            case 'circle':
            case 'dot':
                imagefilledellipse($this->image, $cx, $cy, $s, $s, $color);
                break;

            case 'dots_small':
                $sd = max(4, (int) ($s * 0.7));
                imagefilledellipse($this->image, $cx, $cy, $sd, $sd, $color);
                break;

            case 'diamond':
                imagefilledpolygon($this->image, [$cx, $y1, $x2, $cy, $cx, $y2, $x1, $cy], $color);
                break;

            case 'rounded':
            case 'rounded_square':
                $this->drawRoundedRect($x1, $y1, $x2, $y2, $color, (int) ($s * 0.25));
                break;

            case 'extra_rounded':
            case 'squircle':
                $this->drawRoundedRect($x1, $y1, $x2, $y2, $color, (int) ($s * 0.40));
                break;

            case 'rounded_double':
            case 'dotted':
                $this->drawRoundedRect($x1, $y1, $x2, $y2, $color, (int) ($s * 0.20));
                break;

            case 'rounded_single':
                $this->drawRoundedRect($x1, $y1, $x2, $y2, $color, (int) ($s * 0.15));
                break;

            case 'cushion':
                $this->drawRoundedRect($x1, $y1, $x2, $y2, $color, (int) ($s * 0.30));
                break;

            case 'star':
                $q = intdiv($s, 4);
                $this->fillRect($x1, $y1 + $q, $x2, $y2 - $q, $color);
                $this->fillRect($x1 + $q, $y1, $x2 - $q, $y2, $color);
                break;

            case 'octagon':
                $q = intdiv($s, 3);
                imagefilledpolygon($this->image, [
                    $x1 + $q, $y1,
                    $x2 - $q, $y1,
                    $x2, $y1 + $q,
                    $x2, $y2 - $q,
                    $x2 - $q, $y2,
                    $x1 + $q, $y2,
                    $x1, $y2 - $q,
                    $x1, $y1 + $q,
                ], $color);
                break;

            case 'leaf':
                $r = (int) ($s * 0.4);
                $this->drawCornerRoundedRect($x1, $y1, $x2, $y2, $color, $r, true, false, true, false);
                break;

            case 'vertical_bars':
                $inset = max(1, (int) ($s * 0.15));
                $this->fillRect($x1 + $inset, $y1, $x2 - $inset, $y2, $color);
                break;

            case 'horizontal_bars':
                $inset = max(1, (int) ($s * 0.15));
                $this->fillRect($x1, $y1 + $inset, $x2, $y2 - $inset, $color);
                break;

            case 'plus':
                $bw = max(2, intdiv($s, 3));
                $bh = max(2, intdiv($s, 3));
                $this->fillRect($cx - intdiv($bw, 2), $y1, $cx + intdiv($bw, 2), $y2, $color);
                $this->fillRect($x1, $cy - intdiv($bh, 2), $x2, $cy + intdiv($bh, 2), $color);
                break;

            case 'grid':
                $sw = intdiv($s, 3);
                $r = max(2, intdiv($sw, 2));
                for ($gy = 0; $gy < 3; $gy++) {
                    for ($gx = 0; $gx < 3; $gx++) {
                        imagefilledellipse($this->image, $x1 + $gx * $sw + intdiv($sw, 2), $y1 + $gy * $sw + intdiv($sw, 2), $r * 2, $r * 2, $color);
                    }
                }
                break;

            case 'bars_vertical':
                $bw = max(2, intdiv($s, 4));
                $gap = intdiv($s - 3 * $bw, 2);
                $this->fillRect($x1 + $gap, $y1, $x1 + $gap + $bw, $y2, $color);
                $this->fillRect($cx - intdiv($bw, 2), $y1, $cx + intdiv($bw, 2), $y2, $color);
                $this->fillRect($x2 - $gap - $bw, $y1, $x2 - $gap, $y2, $color);
                break;

            case 'bars_horizontal':
                $bh = max(2, intdiv($s, 4));
                $gap = intdiv($s - 3 * $bh, 2);
                $this->fillRect($x1, $y1 + $gap, $x2, $y1 + $gap + $bh, $color);
                $this->fillRect($x1, $cy - intdiv($bh, 2), $x2, $cy + intdiv($bh, 2), $color);
                $this->fillRect($x1, $y2 - $gap - $bh, $x2, $y2 - $gap, $color);
                break;

            case 'square':
            default:
                $this->fillRect($x1, $y1, $x2, $y2, $color);
                break;
        }
    }

    /**
     * Connected rounded: checks 4 neighbors and rounds only exposed corners.
     * Matches qr-code-styling's "rounded" and "extra-rounded" dot types.
     */
    private function drawConnectedRounded(int $x1, int $y1, int $x2, int $y2, int $cx, int $cy, int $color, int $mx, int $my, int $radius): void
    {
        $top = $this->neighborDark($mx, $my, 0, -1);
        $right = $this->neighborDark($mx, $my, 1, 0);
        $bottom = $this->neighborDark($mx, $my, 0, 1);
        $left = $this->neighborDark($mx, $my, -1, 0);
        $count = (int) $top + (int) $right + (int) $bottom + (int) $left;

        if ($count === 0) {
            imagefilledellipse($this->image, $cx, $cy, $x2 - $x1, $y2 - $y1, $color);
            return;
        }

        if ($count > 2 || ($left && $right) || ($top && $bottom)) {
            $this->fillRect($x1, $y1, $x2, $y2, $color);
            return;
        }

        // Round corners that are "exposed" (neither adjacent side has a neighbor)
        $tl = ! $top && ! $left;
        $tr = ! $top && ! $right;
        $br = ! $bottom && ! $right;
        $bl = ! $bottom && ! $left;

        $this->drawCornerRoundedRect($x1, $y1, $x2, $y2, $color, $radius, $tl, $tr, $br, $bl);
    }

    /**
     * Classy: rounds only the top-left and bottom-right exposed corners.
     * Matches qr-code-styling's "classy" and "classy-rounded" dot types.
     */
    private function drawClassy(int $x1, int $y1, int $x2, int $y2, int $cx, int $cy, int $color, int $mx, int $my, int $radius, bool $extraRound): void
    {
        $top = $this->neighborDark($mx, $my, 0, -1);
        $right = $this->neighborDark($mx, $my, 1, 0);
        $bottom = $this->neighborDark($mx, $my, 0, 1);
        $left = $this->neighborDark($mx, $my, -1, 0);
        $count = (int) $top + (int) $right + (int) $bottom + (int) $left;

        if ($count === 0) {
            // Isolated: round TL and BR
            $this->drawCornerRoundedRect($x1, $y1, $x2, $y2, $color, $radius, true, false, true, false);
            return;
        }

        $tl = ! $top && ! $left;
        $br = ! $bottom && ! $right;

        if (! $tl && ! $br) {
            $this->fillRect($x1, $y1, $x2, $y2, $color);
            return;
        }

        $r = $extraRound ? $radius : (int) ($radius * 0.7);
        $this->drawCornerRoundedRect($x1, $y1, $x2, $y2, $color, $r, $tl, false, $br, false);
    }

    /**
     * Draw a rectangle with independently rounded corners.
     */
    private function drawCornerRoundedRect(int $x1, int $y1, int $x2, int $y2, int $color, int $radius, bool $tl, bool $tr, bool $br, bool $bl): void
    {
        $w = $x2 - $x1;
        $h = $y2 - $y1;
        $radius = min($radius, intdiv($w, 2), intdiv($h, 2));

        if ($radius <= 0) {
            $this->fillRect($x1, $y1, $x2, $y2, $color);
            return;
        }

        $d = $radius * 2;

        // Center cross
        $this->fillRect($x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        $this->fillRect($x1, $y1 + $radius, $x2, $y2 - $radius, $color);

        // Each corner: circle if rounded, square if not
        if ($tl) {
            imagefilledellipse($this->image, $x1 + $radius, $y1 + $radius, $d, $d, $color);
        } else {
            $this->fillRect($x1, $y1, $x1 + $radius, $y1 + $radius, $color);
        }

        if ($tr) {
            imagefilledellipse($this->image, $x2 - $radius, $y1 + $radius, $d, $d, $color);
        } else {
            $this->fillRect($x2 - $radius, $y1, $x2, $y1 + $radius, $color);
        }

        if ($br) {
            imagefilledellipse($this->image, $x2 - $radius, $y2 - $radius, $d, $d, $color);
        } else {
            $this->fillRect($x2 - $radius, $y2 - $radius, $x2, $y2, $color);
        }

        if ($bl) {
            imagefilledellipse($this->image, $x1 + $radius, $y2 - $radius, $d, $d, $color);
        } else {
            $this->fillRect($x1, $y2 - $radius, $x1 + $radius, $y2, $color);
        }
    }

    private function fillRect(int $x1, int $y1, int $x2, int $y2, int $color): void
    {
        imagefilledrectangle($this->image, $x1, $y1, $x2, $y2, $color);
    }

    protected function drawRoundedRect(int $x1, int $y1, int $x2, int $y2, int $color, int $radius): void
    {
        $this->drawCornerRoundedRect($x1, $y1, $x2, $y2, $color, $radius, true, true, true, true);
    }
}
