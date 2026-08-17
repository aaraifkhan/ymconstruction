<?php

namespace App\Enums;

enum SocialContentType: string
{
    case ReelOrShort = 'reel_short';
    case Carousel = 'carousel';
    case StaticPost = 'static_post';
    case Story = 'story';
    case LongVideo = 'long_video';
    case ArticleOrThread = 'article_thread';

    public function label(): string
    {
        return match ($this) {
            self::ReelOrShort => 'Reel / Short Video',
            self::Carousel => 'Carousel Graphic',
            self::StaticPost => 'Single Static Image',
            self::Story => 'Story Post',
            self::LongVideo => 'Long Form Video',
            self::ArticleOrThread => 'Article / Thread',
        };
    }
}
