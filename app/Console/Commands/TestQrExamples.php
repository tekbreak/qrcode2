<?php

namespace App\Console\Commands;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Console\Command;

class TestQrExamples extends Command
{
    protected $signature = 'qr:test {--dir=examples : Directory containing PNG files to test}';

    protected $description = 'Read/decode every QR PNG in a directory and report pass/fail';

    public function handle(): int
    {
        $dir = base_path($this->option('dir'));

        if (! is_dir($dir)) {
            $this->error("Directory not found: {$dir}");
            return self::FAILURE;
        }

        $files = glob("{$dir}/*.png");
        sort($files);

        if (empty($files)) {
            $this->error("No PNG files found in {$dir}");
            return self::FAILURE;
        }

        $reader = new QRCode(new QROptions([
            'readerUseImagickIfAvailable' => false,
        ]));

        $pass = 0;
        $fail = 0;
        $errors = [];

        $this->info("Testing " . count($files) . " QR codes in /{$this->option('dir')}...\n");

        foreach ($files as $file) {
            $name = basename($file);
            try {
                $result = $reader->readFromFile($file);
                $data = (string) $result;

                if (! empty($data)) {
                    $this->line("  <fg=green>PASS</> {$name}  →  " . mb_strimwidth($data, 0, 60, '…'));
                    $pass++;
                } else {
                    $this->line("  <fg=red>FAIL</> {$name}  →  (empty data)");
                    $fail++;
                    $errors[] = $name;
                }
            } catch (\Throwable $e) {
                $msg = mb_strimwidth($e->getMessage(), 0, 80, '…');
                $this->line("  <fg=red>FAIL</> {$name}  →  {$msg}");
                $fail++;
                $errors[] = $name;
            }
        }

        $this->newLine();
        $this->info("Results: {$pass} passed, {$fail} failed out of " . count($files));

        if (! empty($errors)) {
            $this->newLine();
            $this->warn('Failed files:');
            foreach ($errors as $e) {
                $this->line("  - {$e}");
            }
        }

        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }
}
