<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ExpenseCategory: string implements HasLabel
{
    // Head Office / Shared Operating Expenses
    case Salaries = 'salaries';
    case StaffEngagement = 'staff_engagement';
    case Fuel = 'fuel';
    case OfficeRent = 'office_rent';
    case Utilities = 'utilities';
    case CleaningExpense = 'cleaning_expense';
    case Internet = 'internet';
    case MobileTelephone = 'mobile_telephone';
    case VehicleRent = 'vehicle_rent';
    case Printing = 'printing';
    case Stationery = 'stationery';
    case RepairsMaintenance = 'repairs_maintenance';
    case VehicleMaintenance = 'vehicle_maintenance';
    case Entertainment = 'entertainment';
    case TravellingConveyance = 'travelling_conveyance';
    case HotelAccommodation = 'hotel_accommodation';
    case LegalProfessional = 'legal_professional';
    case SoftwareSubscription = 'software_subscription';
    case Miscellaneous = 'miscellaneous';

    // Bidding & Tender Expenses
    case TenderFee = 'tender_fee';
    case TenderDocumentation = 'tender_documentation';
    case BidPreparation = 'bid_preparation';
    case SiteVisitBidding = 'site_visit_bidding';
    case BidBondBankCharges = 'bid_bond_bank_charges';
    case EProcurementFee = 'e_procurement_fee';
    case BiddingConsultancy = 'bidding_consultancy';
    case MiscellaneousBidding = 'miscellaneous_bidding';

    // Project / Site Direct Costs
    case Cement = 'cement';
    case Steel = 'steel';
    case Sand = 'sand';
    case Crush = 'crush';
    case Bricks = 'bricks';
    case Electrical = 'electrical';
    case Plumbing = 'plumbing';
    case Paint = 'paint';
    case Tiles = 'tiles';
    case Labor = 'labor';
    case MachineryRental = 'machinery_rental';
    case Excavation = 'excavation';
    case ConcretePump = 'concrete_pump';
    case Shuttering = 'shuttering';
    case SafetyEquipment = 'safety_equipment';
    case SiteOffice = 'site_office';
    case SiteUtilities = 'site_utilities';
    case SiteSecurity = 'site_security';
    case ProjectTransportation = 'project_transportation';
    case SiteMiscExpense = 'site_misc_expense';
    case SiteLegalSurveyor = 'site_legal_surveyor';

    // 7-Orbit Digital / Marketing
    case DomainHosting = 'domain_hosting';
    case CorporateBranding = 'corporate_branding';
    case ProjectMarketing = 'project_marketing';
    case RecruitmentAdvertising = 'recruitment_advertising';
    case DigitalAdSpend = 'digital_ad_spend';

    // Capital & Advances
    case FixedAssetPurchase = 'fixed_asset_purchase';
    case EmployeeAdvance = 'employee_advance';

    public function getLabel(): string
    {
        return match ($this) {
            self::Salaries => 'Salaries & Wages',
            self::StaffEngagement => 'Staff Engagement / Office Events',
            self::Fuel => 'Fuel Expense',
            self::OfficeRent => 'Office Rent',
            self::Utilities => 'Utilities (Electricity / Gas / Water)',
            self::CleaningExpense => 'Cleaning Expense',
            self::Internet => 'Internet & Communications',
            self::MobileTelephone => 'Mobile & Telephone Expense',
            self::VehicleRent => 'Vehicle Rent',
            self::Printing => 'Printing Expense',
            self::Stationery => 'Stationery Items',
            self::RepairsMaintenance => 'Repairs & Maintenance',
            self::VehicleMaintenance => 'Vehicle Maintenance & Service',
            self::Entertainment => 'Entertainment & Kitchen Supplies',
            self::TravellingConveyance => 'Travelling & Conveyance',
            self::HotelAccommodation => 'Hotel & Accommodation',
            self::LegalProfessional => 'Legal & Professional Fees',
            self::SoftwareSubscription => 'Software & Tool Subscriptions',
            self::Miscellaneous => 'Miscellaneous Office Expense',

            self::TenderFee => 'Tender Fee',
            self::TenderDocumentation => 'Tender Documentation Expense',
            self::BidPreparation => 'Bid Preparation Expense',
            self::SiteVisitBidding => 'Site Visit (Bidding)',
            self::BidBondBankCharges => 'Bank Charges – Bid Bond',
            self::EProcurementFee => 'E-Procurement Registration Fee',
            self::BiddingConsultancy => 'Bidding Consultancy Charges',
            self::MiscellaneousBidding => 'Miscellaneous Bidding Expenses',

            self::Cement => 'Cement',
            self::Steel => 'Steel',
            self::Sand => 'Sand',
            self::Crush => 'Crush',
            self::Bricks => 'Bricks',
            self::Electrical => 'Electrical Material & Works',
            self::Plumbing => 'Plumbing Material & Works',
            self::Paint => 'Paint Material & Works',
            self::Tiles => 'Tiles Material & Works',
            self::Labor => 'Site Labor',
            self::MachineryRental => 'Equipment & Machinery Rental',
            self::Excavation => 'Excavation & Earthworks',
            self::ConcretePump => 'Concrete Pump',
            self::Shuttering => 'Shuttering Material & Work',
            self::SafetyEquipment => 'Safety Equipment & PPE',
            self::SiteOffice => 'Site Office & Setup',
            self::SiteUtilities => 'Site Utilities',
            self::SiteSecurity => 'Site Security',
            self::ProjectTransportation => 'Site / Project Transportation',
            self::SiteMiscExpense => 'Site Miscellaneous Expense',
            self::SiteLegalSurveyor => 'Site Legal & Surveyor Charges',

            self::DomainHosting => 'Domain & Hosting Expense',
            self::CorporateBranding => 'Corporate Branding Expense',
            self::ProjectMarketing => 'Project Marketing Expense',
            self::RecruitmentAdvertising => 'Recruitment Advertising Expense',
            self::DigitalAdSpend => 'Digital Marketing / Ad Spend (Meta/Google)',

            self::FixedAssetPurchase => 'Company Fixed Asset (Furniture / Equipment / Computers)',
            self::EmployeeAdvance => 'Employee Salary Advance',
        };
    }

    public function defaultAccountCode(): string
    {
        return match ($this) {
            self::Salaries => '5100',
            self::StaffEngagement => '5150',
            self::Fuel => '5200',
            self::OfficeRent => '5300',
            self::Utilities => '5400',
            self::CleaningExpense => '5450',
            self::Internet => '5500',
            self::MobileTelephone => '5550',
            self::VehicleRent => '5600',
            self::Printing => '5700',
            self::Stationery => '5800',
            self::RepairsMaintenance => '5900',
            self::VehicleMaintenance => '6000',
            self::Entertainment => '6700',
            self::TravellingConveyance => '6800',
            self::HotelAccommodation => '6850',
            self::LegalProfessional => '6300',
            self::SoftwareSubscription => '6600',
            self::Miscellaneous => '6900',

            self::TenderFee => '5051',
            self::TenderDocumentation => '5052',
            self::BidPreparation => '5053',
            self::SiteVisitBidding => '5054',
            self::BidBondBankCharges => '5055',
            self::EProcurementFee => '5056',
            self::BiddingConsultancy => '5057',
            self::MiscellaneousBidding => '5058',

            self::Cement => '7100',
            self::Steel => '7110',
            self::Sand => '7120',
            self::Crush => '7130',
            self::Bricks => '7140',
            self::Electrical => '7150',
            self::Plumbing => '7160',
            self::Paint => '7170',
            self::Tiles => '7180',
            self::Labor => '7190',
            self::MachineryRental => '7200',
            self::Excavation => '7210',
            self::ConcretePump => '7220',
            self::Shuttering => '7230',
            self::SafetyEquipment => '7240',
            self::SiteOffice => '7250',
            self::SiteUtilities => '7260',
            self::SiteSecurity => '7270',
            self::ProjectTransportation => '7280',
            self::SiteMiscExpense => '7290',
            self::SiteLegalSurveyor => '7295',

            self::DomainHosting => '6210',
            self::CorporateBranding => '6220',
            self::ProjectMarketing => '6230',
            self::RecruitmentAdvertising => '6240',
            self::DigitalAdSpend => '6250',

            self::FixedAssetPurchase => '1280',
            self::EmployeeAdvance => '1140',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::Cement, self::Steel, self::Sand, self::Crush, self::Bricks,
            self::Electrical, self::Plumbing, self::Paint, self::Tiles, self::Labor,
            self::MachineryRental, self::Excavation, self::ConcretePump, self::Shuttering,
            self::SafetyEquipment, self::SiteOffice, self::SiteUtilities, self::SiteSecurity,
            self::ProjectTransportation, self::SiteMiscExpense, self::SiteLegalSurveyor => 'project',

            self::TenderFee, self::TenderDocumentation, self::BidPreparation,
            self::SiteVisitBidding, self::BidBondBankCharges, self::EProcurementFee,
            self::BiddingConsultancy, self::MiscellaneousBidding => 'bidding',

            self::DomainHosting, self::CorporateBranding, self::ProjectMarketing,
            self::RecruitmentAdvertising, self::DigitalAdSpend => 'digital_marketing',

            self::FixedAssetPurchase, self::EmployeeAdvance => 'capital_advances',

            default => 'operating',
        };
    }

    public function isDirectProjectCost(): bool
    {
        return $this->group() === 'project';
    }

    public function isBiddingExpense(): bool
    {
        return $this->group() === 'bidding';
    }
}
