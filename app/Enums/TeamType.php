<?php

namespace App\Enums;

enum TeamType: string
{
    case SocialMedia = 'social_media';
    case GraphicDesign = 'graphic_design';
    case VideoProduction = 'video_production';
    case WebDevelopment = 'web_development';
    case Sales = 'sales';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::SocialMedia => 'Social Media',
            self::GraphicDesign => 'Graphic Design',
            self::VideoProduction => 'Video Production',
            self::WebDevelopment => 'Website Development',
            self::Sales => 'Sales & CRM',
            self::Other => 'Other / General',
        };
    }
}
