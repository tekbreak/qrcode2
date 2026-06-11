<?php

namespace App\Enums;

enum PlanTier: string
{
    case Starter = 'starter';
    case Pro = 'pro';
    case Enterprise = 'enterprise';

    public function label(): string
    {
        return match ($this) {
            self::Starter => 'Starter',
            self::Pro => 'Pro',
            self::Enterprise => 'Enterprise',
        };
    }

    public function maxStaticQrCodes(): ?int
    {
        return match ($this) {
            self::Starter => 5,
            self::Pro => null,
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
            self::Starter => 0,
            self::Pro => 1000,
            self::Enterprise => 3900,
        };
    }

    public function priceYearly(): int
    {
        return match ($this) {
            self::Starter => 0,
            self::Pro => 9900,
            self::Enterprise => 38900,
        };
    }

    public function hasYearlyBilling(): bool
    {
        return $this->priceYearly() > 0;
    }

    /**
     * @return Feature[]
     */
    public function features(): array
    {
        return match ($this) {
            self::Starter => [
                Feature::ExportPng,
                Feature::BasicCustomization,
                Feature::BasicAnalytics,
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
                Feature::ApiAccess,
                Feature::BulkOperations,
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

        if ($this->hasFeature(Feature::CustomDomains)) {
            $lines[] = 'Custom domains';
        }
        if ($this->hasFeature(Feature::ApiAccess)) {
            $lines[] = 'API access';
        }
        if ($this->hasFeature(Feature::BulkOperations)) {
            $lines[] = 'Bulk operations';
        }
        if ($this->hasFeature(Feature::Teams)) {
            $lines[] = 'Team management';
        }
        if ($this->hasFeature(Feature::PrioritySupport)) {
            $lines[] = 'Priority support';
        }
        if ($this === self::Enterprise) {
            $lines[] = 'Unlimited dynamic QR edits';
        }

        return $lines;
    }
}
