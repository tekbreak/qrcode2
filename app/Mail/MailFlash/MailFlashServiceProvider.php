<?php

declare(strict_types=1);

namespace App\Mail\MailFlash;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

final class MailFlashServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Mail::extend('mailflash', function (array $config = []) {
            return new MailFlashTransport(
                (string) config('services.mailflash.key', ''),
                (string) config('services.mailflash.url', 'https://mailflash.es'),
            );
        });
    }
}
