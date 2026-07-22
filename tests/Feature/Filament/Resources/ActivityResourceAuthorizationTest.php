<?php

namespace Tests\Feature\Filament\Resources;

use App\Filament\Resources\Activities\ActivityResource;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ActivityResourceAuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.env', 'local');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_user_without_activity_permission_cannot_see_or_open_activity_log(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->assertFalse(ActivityResource::canViewAny());
        $this->assertFalse(ActivityResource::canAccess());

        $this->get(ActivityResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_view_any_permission_allows_the_activity_log_index(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('ViewAny:Activity'));

        $this->actingAs($user);

        $this->assertTrue(ActivityResource::canViewAny());
        $this->assertTrue(ActivityResource::canAccess());

        $this->get(ActivityResource::getUrl('index'))
            ->assertOk();
    }

    public function test_view_permission_is_required_to_open_an_activity_record(): void
    {
        $user = User::factory()->create();
        $activity = activity()
            ->causedBy($user)
            ->log('Authorization test activity');

        $user->givePermissionTo(Permission::findOrCreate('ViewAny:Activity'));

        $this->actingAs($user);

        $this->get(ActivityResource::getUrl('view', ['record' => $activity]))
            ->assertForbidden();

        $user->givePermissionTo(Permission::findOrCreate('View:Activity'));

        $this->get(ActivityResource::getUrl('view', ['record' => $activity]))
            ->assertOk();
    }
}
