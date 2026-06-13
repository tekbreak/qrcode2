<?php

namespace App\QRCode;

/**
 * Extended options for SVG-based QR rendering with gradients and custom shapes.
 */
class SvgQROptions extends CustomQROptions
{
    protected bool $useGradient = false;

    protected string $gradientColor1 = '#000000';

    protected string $gradientColor2 = '#333333';

    protected string $gradientType = 'linear';
}
