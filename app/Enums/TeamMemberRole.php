<?php

namespace App\Enums;

enum TeamMemberRole: string
{
    case Lead = 'lead';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Lead => 'Team Lead',
            self::Member => 'Team Member',
        };
    }
}
