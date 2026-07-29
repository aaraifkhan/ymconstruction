<?php

namespace App\Enums;

enum HrDocumentTypeCode: string
{
    case Cnic = 'cnic';
    case EducationalDocument = 'educational_document';
    case ExperienceCertificate = 'experience_certificate';
    case AppointmentLetter = 'appointment_letter';
    case MedicalCertificate = 'medical_certificate';
    case PoliceVerification = 'police_verification';

    public function label(): string
    {
        return match ($this) {
            self::Cnic => 'CNIC',
            self::EducationalDocument => 'Educational Document',
            self::ExperienceCertificate => 'Experience Certificate',
            self::AppointmentLetter => 'Appointment Letter',
            self::MedicalCertificate => 'Medical Certificate',
            self::PoliceVerification => 'Police Verification',
        };
    }

    public function applicability(): HrDocumentApplicability
    {
        return $this === self::AppointmentLetter
            ? HrDocumentApplicability::Employment
            : HrDocumentApplicability::Employee;
    }

    public function defaultClassification(): DocumentClassification
    {
        return match ($this) {
            self::AppointmentLetter => DocumentClassification::Confidential,
            default => DocumentClassification::Restricted,
        };
    }

    public function requiresVerification(): bool
    {
        return true;
    }

    public function requiresApproval(): bool
    {
        return in_array($this, [
            self::AppointmentLetter,
            self::PoliceVerification,
        ], true);
    }

    public function requiresIdentityPermission(): bool
    {
        return in_array($this, [self::Cnic, self::PoliceVerification], true);
    }

    public function requiresMedicalPermission(): bool
    {
        return $this === self::MedicalCertificate;
    }
}
