<?php

namespace App\Enums;

enum PlanTier: string
{
    case Free = 'free';
    case Starter = 'starter';
    case Pro = 'pro';
    case Enterprise = 'enterprise';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Free',
            self::Starter => 'Starter',
            self::Pro => 'Pro',
            self::Enterprise => 'Enterprise',
        };
    }

    public function monthlyCredits(): int
    {
        return match ($this) {
            self::Free => 5,
            self::Starter => 50,
            self::Pro => 200,
            self::Enterprise => 0,
        };
    }

    public function maxStaticQrCodes(): ?int
    {
        return match ($this) {
            self::Free => 3,
            self::Starter => 10,
            self::Pro => 50,
            self::Enterprise => null,
        };
    }

    public function maxDynamicQrCodes(): ?int
    {
        return config("qrcode.plans.{$this->value}.max_dynamic_qr");
    }

    public function priceMonthly(): int
    {
        return match ($this) {
            self::Free => 0,
            self::Starter => 500,
            self::Pro => 1500,
            self::Enterprise => 5000,
        };
    }

    public function priceYearly(): int
    {
        return match ($this) {
            self::Free => 0,
            self::Starter => 5000,
            self::Pro => 15000,
            self::Enterprise => 50000,
        };
    }

    public function hasUnlimitedCredits(): bool
    {
        return $this === self::Enterprise;
    }

    /**
     * @return Feature[]
     */
    public function features(): array
    {
        return match ($this) {
            self::Free => [
                Feature::ExportPng,
                Feature::BasicCustomization,
                Feature::BasicAnalytics,
            ],
            self::Starter => [
                Feature::ExportPng,
                Feature::ExportJpg,
                Feature::BasicCustomization,
                Feature::BasicAnalytics,
                Feature::AdvancedAnalytics,
            ],
            self::Pro => [
                Feature::ExportPng,
                Feature::ExportJpg,
                Feature::ExportSvg,
                Feature::ExportEps,
                Feature::BasicCustomization,
                Feature::FullCustomization,
                Feature::BasicAnalytics,
                Feature::AdvancedAnalytics,
                Feature::CustomDomains,
                Feature::ApiAccess,
            ],
            self::Enterprise => Feature::cases(),
        };
    }

    public function hasFeature(Feature|string $feature): bool
    {
        if (is_string($feature)) {
            $feature = Feature::tryFrom($feature);
            if (! $feature) {
                return false;
            }
        }

        return in_array($feature, $this->features());
    }

    /**
     * Human-readable feature summary for pricing cards.
     */
    public function featureSummary(): array
    {
        $downloads = array_filter($this->features(), fn (Feature $f) => in_array($f, [
            Feature::ExportPng, Feature::ExportJpg, Feature::ExportSvg, Feature::ExportEps,
        ]));
        $formats = array_map(fn (Feature $f) => match ($f) {
            Feature::ExportPng => 'PNG',
            Feature::ExportJpg => 'JPG',
            Feature::ExportSvg => 'SVG',
            Feature::ExportEps => 'EPS',
            default => '',
        }, $downloads);

        $lines = [];
        $lines[] = $this->hasFeature(Feature::FullCustomization) ? 'Full customization' : 'Basic customization';
        $lines[] = implode(' + ', $formats) . ' downloads';
        $lines[] = $this->hasFeature(Feature::AdvancedAnalytics) ? 'Advanced analytics' : 'Basic analytics';

        if ($this->hasFeature(Feature::CustomDomains)) $lines[] = 'Custom domains';
        if ($this->hasFeature(Feature::ApiAccess)) $lines[] = 'API access';
        if ($this->hasFeature(Feature::BulkOperations)) $lines[] = 'Bulk operations';
        if ($this->hasFeature(Feature::Teams)) $lines[] = 'Team management';
        if ($this->hasFeature(Feature::PrioritySupport)) $lines[] = 'Priority support';

        return $lines;
    }
}
