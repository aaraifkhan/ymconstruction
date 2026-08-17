<?php

namespace App\Enums;

enum WebDevStatus: string
{
    case RequirementAnalysis = 'requirement_analysis';
    case InDevelopment = 'in_development';
    case CodeReview = 'code_review';
    case ReadyForQa = 'ready_for_qa';
    case Approved = 'approved';
    case DeployedLive = 'deployed_live';

    public function label(): string
    {
        return match ($this) {
            self::RequirementAnalysis => 'Requirement Analysis',
            self::InDevelopment => 'In Development',
            self::CodeReview => 'Code Review',
            self::ReadyForQa => 'Ready for QA / Testing',
            self::Approved => 'Approved for Release',
            self::DeployedLive => 'Deployed to Live',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::RequirementAnalysis => 'gray',
            self::InDevelopment => 'info',
            self::CodeReview => 'warning',
            self::ReadyForQa => 'primary',
            self::Approved, self::DeployedLive => 'success',
        };
    }
}
