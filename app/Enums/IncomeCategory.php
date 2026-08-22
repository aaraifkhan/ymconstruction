<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum IncomeCategory: string implements HasLabel
{
    case CustomerReceipt = 'customer_receipt';
    case CustomerAdvance = 'customer_advance';
    case ConstructionRevenue = 'construction_revenue';
    case ServiceRevenue = 'service_revenue';
    case DirectorCapital = 'director_capital';
    case DirectorLoan = 'director_loan';
    case ConsultancyIncome = 'consultancy_income';
    case RentalIncome = 'rental_income';
    case OtherIncome = 'other_income';

    public function getLabel(): string
    {
        return match ($this) {
            self::CustomerReceipt => 'Customer Payment / AR Receipt (1130)',
            self::CustomerAdvance => 'Customer Mobilization / Advance (2192)',
            self::ConstructionRevenue => 'Construction Project Revenue (4100)',
            self::ServiceRevenue => 'IT / Medical / Billing Services Revenue (4200 / 4300)',
            self::DirectorCapital => 'Director Capital Contribution / Equity (3100)',
            self::DirectorLoan => 'Director Loan / Funds Injection (2220)',
            self::ConsultancyIncome => 'Consultancy Income (4500)',
            self::RentalIncome => 'Rental Income (4600)',
            self::OtherIncome => 'Other / Miscellaneous Income (4700)',
        };
    }

    public function defaultAccountCode(): string
    {
        return match ($this) {
            self::CustomerReceipt => '1130',
            self::CustomerAdvance => '2192',
            self::ConstructionRevenue => '4100',
            self::ServiceRevenue => '4200',
            self::DirectorCapital => '3100',
            self::DirectorLoan => '2220',
            self::ConsultancyIncome => '4500',
            self::RentalIncome => '4600',
            self::OtherIncome => '4700',
        };
    }
}
