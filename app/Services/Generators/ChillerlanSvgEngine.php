<?php

namespace App\Services\Generators;

use App\Contracts\QrGeneratorInterface;
use App\Models\QrCode;
use App\Models\QrDesign;
use App\QRCode\Output\SvgQROutput;
use App\QRCode\SvgQROptions;
use App\Services\Generators\Concerns\InteractsWithQrDesign;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Common\Version;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode as QRCodeLib;

class ChillerlanSvgEngine implements QrGeneratorInterface
{
    use InteractsWithQrDesign;

    public function generatePng(QrCode $qrCode, int $size = 400): string
    {
        $svg = $this->generateSvg($qrCode);
        $gd = $this->rasterizeSvgToGd($svg, $size);

        if (! $gd) {
            return app(ChillerlanGdEngine::class)->generatePng($qrCode, $size);
        }

        $design = $qrCode->design ?? new QrDesign();

        return $this->finalizePngFromGd($gd, $design, $size);
    }

    public function generateSvg(QrCode $qrCode): string
    {
        $design = $qrCode->design ?? new QrDesign();
        $content = $qrCode->getEncodedContent();
        $options = $this->buildSvgOptions($design);

        return (new QRCodeLib($options))->render($content);
    }

    public function generateBase64Preview(QrCode $qrCode, int $size = 300): string
    {
        return 'data:image/png;base64,' . base64_encode($this->generatePng($qrCode, $size));
    }

    protected function buildSvgOptions(QrDesign $design): SvgQROptions
    {
        $shapes = $this->designShapes($design);
        $fgColor = $design->fg_color ?: '#000000';
        $bgColor = $design->bg_color ?: '#FFFFFF';
        $gradient = $design->gradient;
        $useGradient = $gradient && isset($gradient['color1'], $gradient['color2']);

        $bodyMapping = $this->mapBodyShapeToChillerlan($shapes['body']);

        $keepAsSquare = $this->keepAsSquareForEyes($shapes['body'], $shapes['eyeFrame'], $shapes['eyeBall']);

        $options = [
            'version' => Version::AUTO,
            'outputType' => QROutputInterface::CUSTOM,
            'outputInterface' => SvgQROutput::class,
            'eccLevel' => EccLevel::H,
            'scale' => 10,
            'imageBase64' => false,
            'quietzoneSize' => 2,
            'drawLightModules' => true,
            'connectPaths' => $bodyMapping['connectPaths'],
            'drawCircularModules' => $bodyMapping['drawCircularModules'],
            'circleRadius' => $bodyMapping['circleRadius'],
            'keepAsSquare' => $keepAsSquare,
            'svgUseFillAttributes' => false,
            'svgUseCssColors' => true,
            'bodyShape' => $shapes['body'],
            'eyeFrameShape' => $shapes['eyeFrame'],
            'eyeBallShape' => $shapes['eyeBall'],
            'useGradient' => (bool) $useGradient,
            'gradientColor1' => $useGradient ? $gradient['color1'] : $fgColor,
            'gradientColor2' => $useGradient ? $gradient['color2'] : $fgColor,
            'gradientType' => $useGradient ? ($gradient['type'] ?? 'linear') : 'linear',
            'markupDark' => $useGradient ? 'url(#qrGrad)' : $fgColor,
            'markupLight' => $bgColor,
            'svgDefs' => $this->buildSvgDefs($fgColor, $bgColor, $useGradient ? $gradient : null),
        ];

        return new SvgQROptions($options);
    }

    /**
     * @return array{connectPaths: bool, drawCircularModules: bool, circleRadius: float}
     */
    protected function mapBodyShapeToChillerlan(string $bodyShape): array
    {
        return match ($bodyShape) {
            'dots', 'dots_small' => [
                'connectPaths' => false,
                'drawCircularModules' => true,
                'circleRadius' => $bodyShape === 'dots_small' ? 0.35 : 0.45,
            ],
            'rounded_connected', 'extra_rounded_connected', 'classy', 'classy_rounded' => [
                'connectPaths' => true,
                'drawCircularModules' => true,
                'circleRadius' => 0.45,
            ],
            'rounded_square', 'extra_rounded' => [
                'connectPaths' => true,
                'drawCircularModules' => false,
                'circleRadius' => 0.45,
            ],
            default => [
                'connectPaths' => false,
                'drawCircularModules' => false,
                'circleRadius' => 0.45,
            ],
        };
    }

    /**
     * @return int[]
     */
    protected function keepAsSquareForEyes(string $bodyShape, string $eyeFrameShape, string $eyeBallShape): array
    {
        $circularBody = in_array($bodyShape, [
            'dots', 'dots_small', 'rounded_connected', 'extra_rounded_connected',
            'classy', 'classy_rounded',
        ], true);

        if (! $circularBody) {
            return [];
        }

        if ($eyeFrameShape === 'square' && $eyeBallShape === 'square') {
            return [
                QRMatrix::M_FINDER_DARK, QRMatrix::M_FINDER, QRMatrix::M_FINDER_DOT,
                QRMatrix::M_ALIGNMENT_DARK, QRMatrix::M_ALIGNMENT,
            ];
        }

        if (in_array($eyeFrameShape, ['rounded', 'rounded_square', 'cushion'], true)) {
            return [
                QRMatrix::M_FINDER_DARK, QRMatrix::M_FINDER,
                QRMatrix::M_ALIGNMENT_DARK, QRMatrix::M_ALIGNMENT,
            ];
        }

        return [QRMatrix::M_FINDER_DOT];
    }

    protected function buildSvgDefs(string $fgColor, string $bgColor, ?array $gradient): string
    {
        $defs = '';

        if ($gradient) {
            $color1 = $gradient['color1'];
            $color2 = $gradient['color2'];
            $type = $gradient['type'] ?? 'linear';

            if ($type === 'radial') {
                $defs .= <<<SVG
  <radialGradient id="qrGrad" cx="50%" cy="50%" r="70%">
    <stop offset="0%" stop-color="{$color1}"/>
    <stop offset="100%" stop-color="{$color2}"/>
  </radialGradient>
SVG;
            } else {
                $defs .= <<<SVG
  <linearGradient id="qrGrad" x1="0%" y1="0%" x2="100%" y2="100%">
    <stop offset="0%" stop-color="{$color1}"/>
    <stop offset="100%" stop-color="{$color2}"/>
  </linearGradient>
SVG;
            }
        }

        $darkFill = $gradient ? 'url(#qrGrad)' : $fgColor;

        $defs .= <<<SVG

  <style><![CDATA[
    .dark { fill: {$darkFill}; }
    .light { fill: {$bgColor}; }
  ]]></style>
SVG;

        return $defs;
    }
}
