<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Settings\GeneralSettings;
use Database\Seeders\ProductionDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductionDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_data_seeding_is_idempotent_and_preserves_existing_passwords(): void
    {
        $existingPassword = Hash::make('existing-secure-password');
        $existingSettings = app(GeneralSettings::class);
        $existingSettings->company_name = 'Production Company Name';
        $existingSettings->save();

        User::factory()->create([
            'name' => 'Old Admin Name',
            'email' => 'superadmin@gmail.com',
            'password' => $existingPassword,
            'email_verified_at' => null,
        ]);

        $this->seed(ProductionDataSeeder::class);
        $this->seed(ProductionDataSeeder::class);

        $this->assertSame(4, Company::query()->count());
        $this->assertSame(2, User::query()->count());
        $this->assertSame(2, Role::query()->count());

        $superAdmin = User::query()
            ->where('email', 'superadmin@gmail.com')
            ->firstOrFail();
        $manager = User::query()
            ->where('email', 'manager@gmail.com')
            ->firstOrFail();

        $this->assertSame('Super Admin', $superAdmin->name);
        $this->assertSame($existingPassword, $superAdmin->password);
        $this->assertTrue($superAdmin->hasExactRoles('super_admin'));
        $this->assertTrue($manager->hasExactRoles('Manager'));
        $this->assertCount(11, $manager->getAllPermissions());
        $this->assertFalse(Hash::check('password', $manager->password));

        $membership = $superAdmin->companies()
            ->where('companies.slug', 'ymc-construction')
            ->firstOrFail()
            ->pivot;

        $this->assertTrue((bool) $membership->is_active);
        $this->assertFalse((bool) $membership->can_access_descendants);

        $settings = app(GeneralSettings::class);

        $this->assertSame('YMC Group Management', $settings->brand_name);
        $this->assertSame('#14bf97', $settings->primary_color);
        $this->assertSame('Production Company Name', $settings->company_name);
        $this->assertSame('UTC', $settings->timezone);
        $this->assertSame('en', $settings->locale);
    }

    public function test_production_seeder_does_not_create_operational_transactions(): void
    {
        $this->seed(ProductionDataSeeder::class);

        $this->assertDatabaseCount('journal_entries', 0);
        $this->assertDatabaseCount('purchase_requisitions', 0);
        $this->assertDatabaseCount('purchase_orders', 0);
        $this->assertDatabaseCount('vendor_bills', 0);
        $this->assertDatabaseCount('treasury_transactions', 0);
        $this->assertDatabaseCount('customer_invoices', 0);
        $this->assertDatabaseCount('payroll_runs', 0);
        $this->assertDatabaseCount('fixed_assets', 0);
    }

    public function test_new_baseline_users_receive_the_configured_seed_password_without_overwriting_existing_users(): void
    {
        config()->set('baseline.initial_user_password', 'seeded-password');

        $this->seed(ProductionDataSeeder::class);

        $superAdmin = User::query()->where('email', 'superadmin@gmail.com')->firstOrFail();
        $manager = User::query()->where('email', 'manager@gmail.com')->firstOrFail();

        $this->assertTrue(Hash::check('seeded-password', $superAdmin->password));
        $this->assertTrue(Hash::check('seeded-password', $manager->password));
    }
}
