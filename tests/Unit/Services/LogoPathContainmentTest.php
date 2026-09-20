<?php

namespace Tests\Unit\Services;

use App\Services\Generators\Concerns\InteractsWithQrDesign;
use Tests\TestCase;

class LogoPathContainmentTest extends TestCase
{
    private object $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new class
        {
            use InteractsWithQrDesign {
                resolveLogoPath as public;
            }
        };
    }

    public function test_absolute_paths_outside_the_allowed_roots_are_refused(): void
    {
        foreach (['/etc/passwd', '/etc/hosts', base_path('.env'), base_path('composer.json')] as $path) {
            $this->assertNull(
                $this->subject->resolveLogoPath($path),
                "expected {$path} to be refused"
            );
        }
    }

    public function test_traversal_out_of_the_public_disk_is_refused(): void
    {
        foreach ([
            '../../../../etc/passwd',
            'logos/../../../../etc/passwd',
            'icons/../../../.env',
            'icons/qr-center-icons/../../../../.env',
        ] as $path) {
            $this->assertNull(
                $this->subject->resolveLogoPath($path),
                "expected {$path} to be refused"
            );
        }
    }

    public function test_empty_and_null_byte_paths_are_refused(): void
    {
        $this->assertNull($this->subject->resolveLogoPath(''));
        $this->assertNull($this->subject->resolveLogoPath("logos/a.png\0.svg"));
    }

    public function test_a_bundled_icon_still_resolves(): void
    {
        $icons = glob(public_path('icons/qr-center-icons/*.svg')) ?: [];

        if ($icons === []) {
            $this->markTestSkipped('No bundled icons available in this checkout.');
        }

        $name = pathinfo($icons[0], PATHINFO_FILENAME);
        $resolved = $this->subject->resolveLogoPath("icons/{$name}.svg");

        $this->assertNotNull($resolved);
        $this->assertSame(realpath($icons[0]), $resolved);
    }

    public function test_a_stored_logo_still_resolves(): void
    {
        $dir = storage_path('app/public/logos');
        @mkdir($dir, 0755, true);
        $file = $dir.'/containment-test.png';
        file_put_contents($file, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));

        try {
            $this->assertSame(realpath($file), $this->subject->resolveLogoPath('logos/containment-test.png'));
        } finally {
            @unlink($file);
        }
    }
}
