<?php

namespace App\Enums;

enum QrCodeType: string
{
    // Dynamic types
    case Url = 'url';
    case AppStore = 'app_store';
    case Social = 'social';
    case Pdf = 'pdf';
    case Menu = 'menu';

    // Static types
    case Text = 'text';
    case VCard = 'vcard';
    case Wifi = 'wifi';
    case Email = 'email';
    case Phone = 'phone';
    case Sms = 'sms';
    case Geo = 'geo';
    case Event = 'event';
    case Crypto = 'crypto';

    public function label(): string
    {
        return match ($this) {
            self::Url => 'Website URL',
            self::Text => 'Plain Text',
            self::VCard => 'Contact Card',
            self::Wifi => 'WiFi Network',
            self::Email => 'Email Address',
            self::Phone => 'Phone Number',
            self::Sms => 'SMS Message',
            self::Geo => 'Location',
            self::Event => 'Calendar Event',
            self::Crypto => 'Cryptocurrency',
            self::AppStore => 'App Store',
            self::Social => 'Social Media',
            self::Pdf => 'PDF / File',
            self::Menu => 'Restaurant Menu',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Url => 'fa-solid fa-globe',
            self::Text => 'fa-solid fa-font',
            self::VCard => 'fa-solid fa-address-card',
            self::Wifi => 'fa-solid fa-wifi',
            self::Email => 'fa-solid fa-envelope',
            self::Phone => 'fa-solid fa-phone',
            self::Sms => 'fa-solid fa-comment-sms',
            self::Geo => 'fa-solid fa-location-dot',
            self::Event => 'fa-solid fa-calendar-days',
            self::Crypto => 'fa-brands fa-bitcoin',
            self::AppStore => 'fa-solid fa-mobile-screen-button',
            self::Social => 'fa-solid fa-share-nodes',
            self::Pdf => 'fa-solid fa-file-pdf',
            self::Menu => 'fa-solid fa-utensils',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Url => 'blue',
            self::Text => 'slate',
            self::VCard => 'violet',
            self::Wifi => 'cyan',
            self::Email => 'amber',
            self::Phone => 'green',
            self::Sms => 'lime',
            self::Geo => 'red',
            self::Event => 'orange',
            self::Crypto => 'yellow',
            self::AppStore => 'indigo',
            self::Social => 'pink',
            self::Pdf => 'rose',
            self::Menu => 'teal',
        };
    }

    public function isDynamic(): bool
    {
        return in_array($this, [
            self::Url,
            self::Social,
            self::AppStore,
            self::Pdf,
            self::Menu,
        ]);
    }

    /** MVP types available in Phase 1 */
    public static function mvpTypes(): array
    {
        return [
            self::Url,
            self::Text,
            self::VCard,
            self::Wifi,
            self::Email,
            self::Phone,
            self::Sms,
        ];
    }

    /** All available types including Phase 2 */
    public static function allTypes(): array
    {
        return self::cases();
    }
}
