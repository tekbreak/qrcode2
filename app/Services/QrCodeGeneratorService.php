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
}
