<?php

namespace App\Enums;

enum AccountingProfile: string
{
    case Generic = 'generic';
    case Construction = 'construction';
    case ItServices = 'it_services';
    case MedicalBilling = 'medical_billing';
    case Trading = 'trading';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
