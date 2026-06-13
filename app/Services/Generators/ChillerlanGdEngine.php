<?php

namespace App\Services\Generators;

use App\Contracts\QrGeneratorInterface;
use App\Models\QrCode;
use App\Models\QrDesign;
use App\QRCode\CustomQROptions;
use App\QRCode\Output\CustomQRGdImage;
use App\Services\Generators\Concerns\InteractsWithQrDesign;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Common\Version;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode as QRCodeLib;
use chillerlan\QRCode\QROptions;

class ChillerlanGdEngine implements QrGeneratorInterface
{
    use InteractsWithQrDesign;

    public function generatePng(QrCode $qrCode, int $size = 400): string
    {
        $design = $qrCode->design ?? new QrDesign();
        $content = $qrCode->getEncodedContent();
        $shapes = $this->designShapes($design);

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
            'bodyShape' => $shapes['body'],
            'eyeFrameShape' => $shapes['eyeFrame'],
            'eyeBallShape' => $shapes['eyeBall'],
        ]);

        $imageData = (new QRCodeLib($options))->render($content);

        $gd = imagecreatefromstring($imageData);
        $srcW = imagesx($gd);
        $srcH = imagesy($gd);

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

        return $this->finalizePngFromGd($resized, $design, $size);
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
        return 'data:image/png;base64,' . base64_encode($this->generatePng($qrCode, $size));
    }
}
