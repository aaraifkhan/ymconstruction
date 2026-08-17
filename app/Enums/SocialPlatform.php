<?php

namespace App\Enums;

enum SocialPlatform: string
{
    case Facebook = 'facebook';
    case Instagram = 'instagram';
    case LinkedIn = 'linkedin';
    case Twitter = 'x_twitter';
    case TikTok = 'tiktok';
    case YouTube = 'youtube';
    case Pinterest = 'pinterest';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Facebook => 'Facebook',
            self::Instagram => 'Instagram',
            self::LinkedIn => 'LinkedIn',
            self::Twitter => 'X (Twitter)',
            self::TikTok => 'TikTok',
            self::YouTube => 'YouTube',
            self::Pinterest => 'Pinterest',
            self::Other => 'Other',
        };
    }
}
