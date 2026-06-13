<?php

namespace App\Contracts;

use App\Models\QrCode;

interface QrGeneratorInterface
{
    public function generatePng(QrCode $qrCode, int $size = 400): string;

    public function generateSvg(QrCode $qrCode): string;

    public function generateBase64Preview(QrCode $qrCode, int $size = 300): string;
}
