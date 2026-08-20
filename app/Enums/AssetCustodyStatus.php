<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum AssetCustodyStatus: string implements HasColor, HasIcon, HasLabel
{
    case InPool = 'in_pool';
    case AssignedToCompany = 'assigned_to_company';
    case AssignedToEmployee = 'assigned_to_employee';
    case UnderMaintenance = 'under_maintenance';
    case Disposed = 'disposed';

    public function getLabel(): string
    {
        return match ($this) {
            self::InPool => 'In Group Pool / Storage',
            self::AssignedToCompany => 'Assigned to Company (General Use)',
            self::AssignedToEmployee => 'Assigned to Employee (Custody)',
            self::UnderMaintenance => 'Under Maintenance / Repair',
            self::Disposed => 'Disposed / Written Off',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::InPool => 'gray',
            self::AssignedToCompany => 'info',
            self::AssignedToEmployee => 'success',
            self::UnderMaintenance => 'warning',
            self::Disposed => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::InPool => 'heroicon-o-archive-box',
            self::AssignedToCompany => 'heroicon-o-building-office-2',
            self::AssignedToEmployee => 'heroicon-o-user-circle',
            self::UnderMaintenance => 'heroicon-o-wrench-screwdriver',
            self::Disposed => 'heroicon-o-trash',
        };
    }
}
