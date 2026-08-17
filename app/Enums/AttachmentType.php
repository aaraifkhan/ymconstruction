<?php

namespace App\Enums;

enum AttachmentType: string
{
    case FileUpload = 'file_upload';
    case GoogleDrive = 'google_drive';
    case Figma = 'figma';
    case Canva = 'canva';
    case LoomVideo = 'loom_video';
    case LiveUrl = 'live_url';
    case SocialPostUrl = 'social_post_url';
    case CodeRepo = 'code_repo';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::FileUpload => 'File Upload',
            self::GoogleDrive => 'Google Drive Link',
            self::Figma => 'Figma Link',
            self::Canva => 'Canva Link',
            self::LoomVideo => 'Loom / Video Link',
            self::LiveUrl => 'Live Website URL',
            self::SocialPostUrl => 'Social Post URL',
            self::CodeRepo => 'GitHub / Code Repo',
            self::Other => 'Other Link',
        };
    }
}
