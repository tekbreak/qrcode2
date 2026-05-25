<?php

namespace Tests\Unit\Services;

use App\Services\QrCodeGeneratorService;
use ReflectionMethod;
use Tests\TestCase;

class QrCodeGeneratorServiceTest extends TestCase
{
    private QrCodeGeneratorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(QrCodeGeneratorService::class);
    }

    public function test_hex_to_rgb_converts_six_digit_hex(): void
    {
        $result = $this->invokeProtected('hexToRgb', '#FF8040');

        $this->assertSame([255, 128, 64], $result);
    }

    public function test_normalize_body_shape_falls_back_to_square_for_unknown_style(): void
    {
        $result = $this->invokeProtected('normalizeBodyShape', 'not-a-real-shape');

        $this->assertSame('square', $result);
    }

    public function test_normalize_eye_frame_shape_falls_back_to_square_for_unknown_style(): void
    {
        $result = $this->invokeProtected('normalizeEyeFrameShape', 'invalid');

        $this->assertSame('square', $result);
    }

    public function test_normalize_eye_ball_shape_falls_back_to_square_for_unknown_style(): void
    {
        $result = $this->invokeProtected('normalizeEyeBallShape', 'invalid');

        $this->assertSame('square', $result);
    }

    private function invokeProtected(string $method, mixed ...$args): mixed
    {
        $reflection = new ReflectionMethod(QrCodeGeneratorService::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($this->service, ...$args);
    }
}
