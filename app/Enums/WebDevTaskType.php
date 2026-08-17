<?php

namespace App\Enums;

enum WebDevTaskType: string
{
    case Frontend = 'frontend';
    case BackendOrApi = 'backend_api';
    case BugFix = 'bug_fix';
    case UiStyling = 'ui_styling';
    case PerformanceOrSeo = 'performance_seo';
    case DatabaseOrMigration = 'database_migration';
    case FullFeature = 'full_feature';

    public function label(): string
    {
        return match ($this) {
            self::Frontend => 'Frontend Development',
            self::BackendOrApi => 'Backend / API Development',
            self::BugFix => 'Bug Fix',
            self::UiStyling => 'UI / Styling Enhancement',
            self::PerformanceOrSeo => 'Performance / SEO',
            self::DatabaseOrMigration => 'Database & Migration',
            self::FullFeature => 'Full Stack Feature',
        };
    }
}
