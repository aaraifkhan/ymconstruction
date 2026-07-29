<?php

namespace App\Enums;

enum HrDataMigrationStatus: string
{
    case Draft = 'draft';
    case Validated = 'validated';
    case Failed = 'failed';
    case Imported = 'imported';
    case RolledBack = 'rolled_back';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
