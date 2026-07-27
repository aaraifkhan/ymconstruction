<?php

declare(strict_types=1);
use App\Filament\Resources\Activities\ActivityResource;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\CompanyBankAccounts\CompanyBankAccountResource;
use App\Filament\Resources\CompanyModules\CompanyModuleResource;
use App\Filament\Resources\Departments\DepartmentResource;
use App\Filament\Resources\Designations\DesignationResource;
use App\Filament\Resources\DocumentCategories\DocumentCategoryResource;
use App\Filament\Resources\Documents\DocumentResource;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Filament\Resources\EmploymentCompensation\EmploymentCompensationResource;
use App\Filament\Resources\Employments\EmploymentResource;
use App\Filament\Resources\JoiningLetters\JoiningLetterResource;
use App\Filament\Resources\JoiningLetterTemplates\JoiningLetterTemplateResource;
use App\Filament\Resources\JournalEntries\JournalEntryResource;
use App\Filament\Resources\Modules\ModuleResource;
use App\Filament\Resources\OpeningBalanceBatches\OpeningBalanceBatchResource;
use App\Filament\Resources\PayrollRuns\PayrollRunResource;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Users\UserResource;
use Filament\Pages\Dashboard;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;

return [

    /*
    |--------------------------------------------------------------------------
    | Shield Resource
    |--------------------------------------------------------------------------
    |
    | Here you may configure the built-in role management resource. You can
    | customize the URL, choose whether to show model paths, group it under
    | a cluster, and decide which permission tabs to display.
    |
    */

    'shield_resource' => [
        'slug' => 'shield/roles',
        'show_model_path' => true,
        'cluster' => null,
        'tabs' => [
            'pages' => true,
            'widgets' => true,
            'resources' => true,
            'custom_permissions' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy
    |--------------------------------------------------------------------------
    |
    | When your application supports teams, Shield will automatically detect
    | and configure the tenant model during setup. This enables tenant-scoped
    | roles and permissions throughout your application.
    |
    */

    'tenant_model' => null,

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | This value contains the class name of your user model. This model will
    | be used for role assignments and must implement the HasRoles trait
    | provided by the Spatie\Permission package.
    |
    */

    'auth_provider_model' => 'App\\Models\\User',

    /*
    |--------------------------------------------------------------------------
    | Super Admin
    |--------------------------------------------------------------------------
    |
    | Here you may define a super admin that has unrestricted access to your
    | application. You can choose to implement this via Laravel's gate system
    | or as a traditional role with all permissions explicitly assigned.
    |
    */

    'super_admin' => [
        'enabled' => true,
        'name' => 'super_admin',
        'define_via_gate' => false,
        'intercept_gate' => 'before',
    ],

    /*
    |--------------------------------------------------------------------------
    | Panel User
    |--------------------------------------------------------------------------
    |
    | When enabled, Shield will create a basic panel user role that can be
    | assigned to users who should have access to your Filament panels but
    | don't need any specific permissions beyond basic authentication.
    |
    */

    'panel_user' => [
        'enabled' => true,
        'name' => 'panel_user',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Builder
    |--------------------------------------------------------------------------
    |
    | You can customize how permission keys are generated to match your
    | preferred naming convention and organizational standards. Shield uses
    | these settings when creating permission names from your resources.
    |
    | Supported formats: snake, kebab, pascal, camel, upper_snake, lower_snake
    |
    */

    'permissions' => [
        'separator' => ':',
        'case' => 'pascal',
        'generate' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Policies
    |--------------------------------------------------------------------------
    |
    | Shield can automatically generate Laravel policies for your resources.
    | When merge is enabled, the methods below will be combined with any
    | resource-specific methods you define in the resources section.
    |
    */

    'policies' => [
        'path' => app_path('Policies'),
        'merge' => false,
        'generate' => true,
        'methods' => [
            'viewAny', 'view', 'create', 'update', 'delete', 'deleteAny', 'restore', 'restoreAny', 'forceDelete', 'forceDeleteAny',
        ],
        'single_parameter_methods' => [
            'viewAny',
            'create',
            'deleteAny',
            'restoreAny',
            'forceDeleteAny',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Localization
    |--------------------------------------------------------------------------
    |
    | Shield supports multiple languages out of the box. When enabled, you
    | can provide translated labels for permissions to create a more
    | localized experience for your international users.
    |
    */

    'localization' => [
        'enabled' => false,
        'key' => 'filament-shield::filament-shield.resource_permission_prefixes_labels',
    ],

    /*
    |--------------------------------------------------------------------------
    | Resources
    |--------------------------------------------------------------------------
    |
    | Here you can fine-tune permissions for specific Filament resources.
    | Use the 'manage' array to override the default policy methods for
    | individual resources, giving you granular control over permissions.
    |
    */

    'resources' => [
        'subject' => 'model',
        'manage' => [
            RoleResource::class => [
                'viewAny',
                'view',
                'create',
                'update',
                'delete',
                'deleteAny',
            ],
            UserResource::class => [
                'viewAny',
                'view',
                'create',
                'update',
                'delete',
                'deleteAny',
                'restore',
                'restoreAny',
                'forceDelete',
                'forceDeleteAny',
                'resetPassword',
            ],
            ActivityResource::class => [
                'viewAny',
                'view',
            ],
            CompanyResource::class => [
                'viewAny',
                'view',
                'create',
                'update',
                'delete',
                'deleteAny',
                'restore',
                'restoreAny',
                'manageMembers',
            ],
            ModuleResource::class => [
                'viewAny',
                'view',
                'create',
                'update',
                'delete',
                'deleteAny',
            ],
            CompanyModuleResource::class => [
                'viewAny',
                'view',
                'create',
                'update',
                'delete',
                'deleteAny',
            ],
            CompanyBankAccountResource::class => [
                'viewAny',
                'view',
                'create',
                'update',
                'delete',
                'deleteAny',
                'restore',
                'restoreAny',
                'viewSensitive',
            ],
            DocumentCategoryResource::class => [
                'viewAny',
                'view',
                'create',
                'update',
                'delete',
                'deleteAny',
                'restore',
                'restoreAny',
            ],
            DocumentResource::class => [
                'viewAny',
                'view',
                'create',
                'update',
                'delete',
                'deleteAny',
                'restore',
                'restoreAny',
                'viewSensitive',
                'download',
                'preview',
                'uploadVersion',
                'verify',
                'approve',
                'reject',
            ],
            EmployeeResource::class => [
                'viewAny',
                'view',
                'create',
                'update',
                'delete',
                'restore',
                'viewIdentity',
                'viewContact',
                'viewMedical',
                'manageSensitive',
            ],
            DepartmentResource::class => [
                'viewAny',
                'view',
                'create',
                'update',
                'delete',
                'restore',
                'restoreAny',
            ],
            DesignationResource::class => [
                'viewAny',
                'view',
                'create',
                'update',
                'delete',
                'restore',
                'restoreAny',
            ],
            EmploymentResource::class => [
                'viewAny',
                'view',
                'create',
                'update',
                'delete',
                'deleteAny',
                'restore',
                'restoreAny',
                'viewHrNotes',
                'manageHrVerification',
            ],
            JoiningLetterTemplateResource::class => [
                'viewAny',
                'view',
                'create',
                'update',
                'delete',
                'restore',
            ],
            JoiningLetterResource::class => [
                'viewAny',
                'view',
                'create',
                'update',
                'delete',
                'restore',
                'viewSensitive',
                'viewCompensation',
                'manageCompensation',
                'regenerate',
                'submit',
                'approve',
                'reject',
                'issue',
                'recordAcceptance',
            ],
            EmploymentCompensationResource::class => [
                'viewAny',
                'view',
                'create',
                'update',
                'delete',
                'restore',
                'viewAmounts',
                'manageAmounts',
                'submit',
                'approve',
                'reject',
            ],
            PayrollRunResource::class => [
                'viewAny', 'view', 'create', 'update', 'delete', 'restore', 'viewAmounts',
                'generateEntries', 'submit', 'approve', 'reject', 'markPaid', 'lock',
            ],
            JournalEntryResource::class => [
                'viewAny', 'view', 'create', 'update', 'delete',
                'submit', 'approve', 'reject', 'post', 'reverse',
            ],
            OpeningBalanceBatchResource::class => [
                'viewAny', 'view', 'create', 'update', 'delete', 'validate', 'post',
            ],
        ],
        'exclude' => [
            //
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
    |
    | Most Filament pages only require view permissions. Pages listed in the
    | exclude array will be skipped during permission generation and won't
    | appear in your role management interface.
    |
    */

    'pages' => [
        'subject' => 'class',
        'prefix' => 'view',
        'exclude' => [
            Dashboard::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Widgets
    |--------------------------------------------------------------------------
    |
    | Like pages, widgets typically only need view permissions. Add widgets
    | to the exclude array if you don't want them to appear in your role
    | management interface.
    |
    */

    'widgets' => [
        'subject' => 'class',
        'prefix' => 'view',
        'exclude' => [
            AccountWidget::class,
            FilamentInfoWidget::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Permissions
    |--------------------------------------------------------------------------
    |
    | Sometimes you need permissions that don't map to resources, pages, or
    | widgets. Define any custom permissions here and they'll be available
    | when editing roles in your application.
    |
    */

    'custom_permissions' => [
        'Update:Settings',
        'ViewAny:EmployeeEmergencyContact',
        'View:EmployeeEmergencyContact',
        'Create:EmployeeEmergencyContact',
        'Update:EmployeeEmergencyContact',
        'Delete:EmployeeEmergencyContact',
        'Restore:EmployeeEmergencyContact',
        'ViewAny:EmployeeQualification',
        'View:EmployeeQualification',
        'Create:EmployeeQualification',
        'Update:EmployeeQualification',
        'Delete:EmployeeQualification',
        'Restore:EmployeeQualification',
        'ViewAny:EmployeeExperience',
        'View:EmployeeExperience',
        'Create:EmployeeExperience',
        'Update:EmployeeExperience',
        'Delete:EmployeeExperience',
        'Restore:EmployeeExperience',
        'ViewAny:EmployeeBankAccount',
        'View:EmployeeBankAccount',
        'Create:EmployeeBankAccount',
        'Update:EmployeeBankAccount',
        'Delete:EmployeeBankAccount',
        'Restore:EmployeeBankAccount',
        'ViewSensitive:EmployeeBankAccount',
    ],

    /*
    |--------------------------------------------------------------------------
    | Entity Discovery
    |--------------------------------------------------------------------------
    |
    | By default, Shield only looks for entities in your default Filament
    | panel. Enable these options if you're using multiple panels and want
    | Shield to discover entities across all of them.
    |
    */

    'discovery' => [
        'discover_all_resources' => false,
        'discover_all_widgets' => false,
        'discover_all_pages' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Policy
    |--------------------------------------------------------------------------
    |
    | Shield can automatically register a policy for role management itself.
    | This lets you control who can manage roles using Laravel's built-in
    | authorization system. Requires a RolePolicy class in your app.
    |
    */

    'register_role_policy' => true,

];
