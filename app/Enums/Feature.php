<?php

namespace App\Enums;

enum Feature: string
{
    // Downloads
    case ExportPng = 'export_png';
    case ExportJpg = 'export_jpg';
    case ExportSvg = 'export_svg';
    case ExportEps = 'export_eps';

    // Analytics
    case BasicAnalytics = 'basic_analytics';
    case AdvancedAnalytics = 'advanced_analytics';

    // Customization
    case BasicCustomization = 'basic_customization';
    case FullCustomization = 'full_customization';

    // Platform
    case CustomDomains = 'custom_domains';
    case ApiAccess = 'api_access';
    case BulkOperations = 'bulk_operations';
    case Teams = 'teams';
    case PrioritySupport = 'priority_support';

    public function label(): string
    {
        return match ($this) {
            self::ExportPng => 'PNG download',
            self::ExportJpg => 'JPG download',
            self::ExportSvg => 'SVG download',
            self::ExportEps => 'EPS download',
            self::BasicAnalytics => 'Basic analytics',
            self::AdvancedAnalytics => 'Advanced analytics',
            self::BasicCustomization => 'Basic customization',
            self::FullCustomization => 'Full customization',
            self::CustomDomains => 'Custom domains',
            self::ApiAccess => 'API access',
            self::BulkOperations => 'Bulk operations',
            self::Teams => 'Team management',
            self::PrioritySupport => 'Priority support',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ExportPng, self::ExportJpg => 'fa-solid fa-image',
            self::ExportSvg => 'fa-solid fa-bezier-curve',
            self::ExportEps => 'fa-solid fa-vector-square',
            self::BasicAnalytics => 'fa-solid fa-chart-simple',
            self::AdvancedAnalytics => 'fa-solid fa-chart-line',
            self::BasicCustomization => 'fa-solid fa-palette',
            self::FullCustomization => 'fa-solid fa-wand-magic-sparkles',
            self::CustomDomains => 'fa-solid fa-globe',
            self::ApiAccess => 'fa-solid fa-code',
            self::BulkOperations => 'fa-solid fa-layer-group',
            self::Teams => 'fa-solid fa-users',
            self::PrioritySupport => 'fa-solid fa-headset',
        };
    }

    /**
     * Features grouped by category for display purposes.
     */
    public static function grouped(): array
    {
        return [
            'Downloads' => [self::ExportPng, self::ExportJpg, self::ExportSvg, self::ExportEps],
            'Analytics' => [self::BasicAnalytics, self::AdvancedAnalytics],
            'Customization' => [self::BasicCustomization, self::FullCustomization],
            'Platform' => [self::CustomDomains, self::ApiAccess, self::BulkOperations, self::Teams, self::PrioritySupport],
        ];
    }
}
