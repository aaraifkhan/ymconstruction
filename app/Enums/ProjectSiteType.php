<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ProjectSiteType: string implements HasLabel
{
    case Site = 'site';
    case Store = 'store';
    case SiteAndStore = 'site_and_store';

    public function getLabel(): string
    {
        return str($this->value)->headline()->toString();
    }
}
