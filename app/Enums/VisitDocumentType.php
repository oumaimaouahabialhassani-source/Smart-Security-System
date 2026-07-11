<?php

namespace App\Enums;

enum VisitDocumentType: string
{
    case NationalId = 'national_id';
    case Passport = 'passport';
    case DrivingLicense = 'driving_license';
    case ResidencePermit = 'residence_permit';

    public function label(): string
    {
        return match ($this) {
            self::NationalId => 'National ID',
            self::Passport => 'Passport',
            self::DrivingLicense => 'Driving License',
            self::ResidencePermit => 'Residence Permit',
        };
    }
}
