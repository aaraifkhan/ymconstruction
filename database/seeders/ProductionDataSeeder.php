<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use App\Settings\GeneralSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ProductionDataSeeder extends Seeder
{
    /**
     * @var array<string, array{name: string, role: string}>
     */
    private const USERS = [
        'superadmin@gmail.com' => [
            'name' => 'Super Admin',
            'role' => 'super_admin',
        ],
        'manager@gmail.com' => [
            'name' => 'Manager',
            'role' => 'Manager',
        ],
    ];

    /**
     * @var array<int, string>
     */
    private const MANAGER_PERMISSIONS = [
        'Create:User',
        'Delete:User',
        'DeleteAny:User',
        'ForceDelete:User',
        'ForceDeleteAny:User',
        'Restore:User',
        'RestoreAny:User',
        'Update:User',
        'View:User',
        'View:UserStatsOverview',
        'ViewAny:User',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            CompanySeeder::class,
            AccountingFoundationSeeder::class,
            FoundationPermissionSeeder::class,
        ]);

        $this->seedApplicationSettings();
        $this->seedAccessConfiguration();
    }

    private function seedApplicationSettings(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->brand_name = 'YM Construction';
        $settings->primary_color = '#14bf97';
        $settings->save();
    }

    private function seedAccessConfiguration(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $superAdminRole = Role::query()->updateOrCreate(
            ['name' => 'super_admin', 'guard_name' => 'web'],
        );
        $managerRole = Role::query()->updateOrCreate(
            ['name' => 'Manager', 'guard_name' => 'web'],
        );

        $superAdminRole->syncPermissions(Permission::query()->get());
        $managerRole->syncPermissions(
            Permission::query()->whereIn('name', self::MANAGER_PERMISSIONS)->get(),
        );

        foreach (self::USERS as $email => $definition) {
            $existingUser = User::withTrashed()
                ->where('email', $email)
                ->first();

            $user = User::withTrashed()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $definition['name'],
                    'email_verified_at' => $existingUser?->email_verified_at ?? now(),
                    'password' => $existingUser?->password ?? Hash::make(Str::random(64)),
                ],
            );

            if ($user->trashed()) {
                $user->restore();
            }

            $user->syncRoles([$definition['role']]);
        }

        $superAdmin = User::query()
            ->where('email', 'superadmin@gmail.com')
            ->firstOrFail();
        $ymConstruction = Company::query()
            ->where('slug', 'ym-construction')
            ->firstOrFail();

        $superAdmin->companies()->syncWithoutDetaching([
            $ymConstruction->getKey() => [
                'is_active' => true,
                'can_access_descendants' => true,
            ],
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
