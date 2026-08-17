<?php

namespace App\Enums;

enum SocialWorkflowStage: string
{
    case Idea = 'idea';
    case ContentBrief = 'content_brief';
    case Designing = 'designing';
    case Editing = 'editing';
    case InternalReview = 'internal_review';
    case DepartmentApproval = 'department_approval';
    case Scheduled = 'scheduled';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Idea => 'Idea Conception',
            self::ContentBrief => 'Content Brief Ready',
            self::Designing => 'Designing in Progress',
            self::Editing => 'Video Editing in Progress',
            self::InternalReview => 'Internal Team Review',
            self::DepartmentApproval => 'Department Head Approval',
            self::Scheduled => 'Scheduled for Publishing',
            self::Published => 'Published',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Idea => 'gray',
            self::ContentBrief => 'info',
            self::Designing, self::Editing => 'warning',
            self::InternalReview => 'primary',
            self::DepartmentApproval => 'secondary',
            self::Scheduled => 'warning',
            self::Published => 'success',
        };
    }
}
