<?php

namespace App\Enums;

enum VideoType: string
{
    case ReelOrShort = 'reel_short';
    case CorporatePromo = 'corporate_promo';
    case ProductExplainer = 'product_explainer';
    case DocumentaryOrCaseStudy = 'documentary_case_study';
    case CommercialAd = 'commercial_ad';
    case YouTubeLongForm = 'youtube_long_form';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ReelOrShort => 'Reel / TikTok / Shorts',
            self::CorporatePromo => 'Corporate Promo',
            self::ProductExplainer => 'Product Explainer',
            self::DocumentaryOrCaseStudy => 'Documentary / Case Study',
            self::CommercialAd => 'Commercial Ad',
            self::YouTubeLongForm => 'YouTube Long Form',
            self::Other => 'Other Video',
        };
    }
}
