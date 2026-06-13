<?php

namespace App\Services;

use App\Contracts\QrGeneratorInterface;
use App\Models\QrCode;
use App\Services\Generators\ChillerlanGdEngine;
use App\Services\Generators\ChillerlanSvgEngine;

class QrCodeGeneratorService implements QrGeneratorInterface
{
    public function __construct(
        protected ChillerlanGdEngine $gdEngine,
        protected ChillerlanSvgEngine $svgEngine,
    ) {}

    public function generatePng(QrCode $qrCode, int $size = 400): string
    {
        return $this->engine()->generatePng($qrCode, $size);
    }

    public function generateSvg(QrCode $qrCode): string
    {
        return $this->engine()->generateSvg($qrCode);
    }

    public function generateJpg(QrCode $qrCode, int $size = 1000): string
    {
        $image = imagecreatefromstring($this->generatePng($qrCode, $size));

        if ($image === false) {
            throw new \RuntimeException('Failed to generate JPG.');
        }

        ob_start();
        imagejpeg($image, null, 90);
        $jpg = ob_get_clean();
        imagedestroy($image);

        return $jpg;
    }

    public function generateEps(QrCode $qrCode, int $size = 1000): string
    {
        $image = imagecreatefromstring($this->generatePng($qrCode, $size));

        if ($image === false) {
            throw new \RuntimeException('Failed to generate EPS.');
        }

        $eps = $this->gdImageToEps($image);
        imagedestroy($image);

        return $eps;
    }

    public function generateBase64Preview(QrCode $qrCode, int $size = 300): string
    {
        return $this->engine()->generateBase64Preview($qrCode, $size);
    }

    protected function engine(): QrGeneratorInterface
    {
        return match (config('qrcode.generator_engine', 'v1')) {
            'v2' => $this->svgEngine,
            default => $this->gdEngine,
        };
    }

    protected function gdImageToEps(\GdImage $image): string
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $rowBytes = $width * 3;

        $header = implode("\n", [
            '%!PS-Adobe-3.0 EPSF-3.0',
            "%%BoundingBox: 0 0 {$width} {$height}",
            '%%EndComments',
            'gsave',
            "{$width} {$height} scale",
            "{$width} {$height} 8",
            "[{$width} 0 0 -{$height} 0 {$height}]",
            "{currentfile {$rowBytes} string readhexstring pop}",
            'false 3 colorimage',
        ]);

        $hexLines = [];

        for ($y = 0; $y < $height; $y++) {
            $row = '';

            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($image, $x, $y);
                $row .= sprintf(
                    '%02X%02X%02X',
                    ($rgb >> 16) & 0xFF,
                    ($rgb >> 8) & 0xFF,
                    $rgb & 0xFF,
                );
            }

            foreach (str_split($row, 128) as $line) {
                $hexLines[] = $line;
            }
        }

        return $header . "\n" . implode("\n", $hexLines) . "\ngrestore\n%%EOF\n";
    }
}
