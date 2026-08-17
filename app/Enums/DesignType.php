<?php

namespace App\Enums;

enum DesignType: string
{
    case SocialMediaGraphic = 'social_media_graphic';
    case BannerOrBillboard = 'banner_billboard';
    case FlyerOrBrochure = 'flyer_brochure';
    case BrandIdentityOrLogo = 'brand_identity_logo';
    case UiUxScreen = 'ui_ux_screen';
    case Packaging = 'packaging';
    case PitchDeckOrPresentation = 'pitch_deck_presentation';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::SocialMediaGraphic => 'Social Media Graphic',
            self::BannerOrBillboard => 'Banner / Billboard',
            self::FlyerOrBrochure => 'Flyer / Brochure',
            self::BrandIdentityOrLogo => 'Brand Identity / Logo',
            self::UiUxScreen => 'UI/UX Screen',
            self::Packaging => 'Packaging Design',
            self::PitchDeckOrPresentation => 'Pitch Deck / Presentation',
            self::Other => 'Other Design',
        };
    }
}
