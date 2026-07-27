<?php

namespace App\Enums;

enum EmploymentCategory: string
{
    case Director = 'director';
    case AdministrativeStaff = 'administrative_staff';
    case ProjectStaff = 'project_staff';

    public function label(): string
    {
        return match ($this) {
            self::Director => 'Director',
            self::AdministrativeStaff => 'Administrative Staff',
            self::ProjectStaff => 'Project Staff',
        };
    }
}
