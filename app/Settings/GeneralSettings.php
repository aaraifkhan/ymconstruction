<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $brand_name;
    public ?string $brand_logo;
    public string $primary_color;

    // Company Info
    public string $company_name;
    public string $company_email;
    public string $company_phone;
    public string $company_address;

    // Localization
    public string $timezone;
    public string $locale;

    // SEO
    public string $seo_title;
    public string $seo_description;
    public string $seo_keywords;

    public static function group(): string
    {
        return 'general';
    }
}
