<?php

namespace App\QRCode;

use chillerlan\QRCode\QROptions;

/**
 * Extended QROptions with custom shape settings for Body, Eye Frame, and Eye Ball.
 */
class CustomQROptions extends QROptions
{
    /** Body shape: square, dots, diamond, rounded_square */
    protected string $bodyShape = 'square';

    /** Eye frame (outer finder/alignment): square, rounded, circle, dot */
    protected string $eyeFrameShape = 'square';

    /** Eye ball (inner finder dot): square, rounded, circle, dot */
    protected string $eyeBallShape = 'square';
}
