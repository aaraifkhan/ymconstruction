<?php

namespace App\Enums;

enum DocumentClassification: string
{
    case Internal = 'internal';
    case Confidential = 'confidential';
    case Restricted = 'restricted';

    public function label(): string
    {
        return match ($this) {
            self::Internal => 'Internal',
            self::Confidential => 'Confidential',
            self::Restricted => 'Restricted',
        };
    }

    public function requiresSensitivePermission(): bool
    {
        return $this !== self::Internal;
    }
}
