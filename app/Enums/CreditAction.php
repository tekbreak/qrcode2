<?php

namespace App\Enums;

enum CreditAction: string
{
    case MaintainDynamicQr = 'maintain_dynamic_qr';
    case EditDynamicQr = 'edit_dynamic_qr';
    case PremiumCustomization = 'premium_customization';
    case SvgDownload = 'svg_download';
    case EpsDownload = 'eps_download';
    case BulkGeneration = 'bulk_generation';
    case ApiCall = 'api_call';
    case AnalyticsExport = 'analytics_export';
    case ScanThreshold = 'scan_threshold';
    case MonthlyReset = 'monthly_reset';
    case CreditPurchase = 'credit_purchase';
    case AdminAdjustment = 'admin_adjustment';

    public function cost(): int
    {
        return match ($this) {
            self::MaintainDynamicQr => config('qrcode.credits.dynamic_qr_maintenance', 5),
            self::EditDynamicQr => config('qrcode.credits.edit_dynamic_qr', 5),
            self::PremiumCustomization => config('qrcode.credits.premium_customization', 2),
            self::SvgDownload => config('qrcode.credits.svg_download', 1),
            self::EpsDownload => config('qrcode.credits.eps_download', 3),
            self::BulkGeneration => config('qrcode.credits.bulk_generation', 3),
            self::ApiCall => config('qrcode.credits.api_call', 5),
            self::AnalyticsExport => config('qrcode.credits.analytics_export', 5),
            self::ScanThreshold => 1,
            self::MonthlyReset, self::CreditPurchase, self::AdminAdjustment => 0,
        };
    }

    public function isDebit(): bool
    {
        return ! in_array($this, [
            self::MonthlyReset,
            self::CreditPurchase,
            self::AdminAdjustment,
        ]);
    }

    public function label(): string
    {
        return match ($this) {
            self::MaintainDynamicQr => 'Dynamic QR monthly maintenance',
            self::EditDynamicQr => 'Edit dynamic QR code',
            self::PremiumCustomization => 'Premium customization',
            self::SvgDownload => 'SVG download',
            self::EpsDownload => 'EPS download',
            self::BulkGeneration => 'Bulk generation',
            self::ApiCall => 'API call',
            self::AnalyticsExport => 'Analytics export',
            self::ScanThreshold => 'Scan usage (per 1,000 scans)',
            self::MonthlyReset => 'Monthly credit reset',
            self::CreditPurchase => 'Credit pack purchase',
            self::AdminAdjustment => 'Admin adjustment',
        };
    }
}
