<?php

namespace Tests\Feature;

use Database\Seeders\FoundationPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FixedAssetReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_fixed_asset_permissions_are_complete_and_idempotent(): void
    {
        $this->seed(FoundationPermissionSeeder::class);
        $this->seed(FoundationPermissionSeeder::class);

        foreach ([
            'ViewAny:AssetCategory',
            'ViewAny:FixedAsset',
            'Submit:FixedAsset',
            'Approve:FixedAsset',
            'Reject:FixedAsset',
            'Capitalize:FixedAsset',
            'Transfer:FixedAsset',
            'Generate:DepreciationRun',
            'Submit:DepreciationRun',
            'Approve:DepreciationRun',
            'Post:DepreciationRun',
            'Reverse:DepreciationRun',
            'Approve:AssetDisposal',
            'Post:AssetDisposal',
            'Reverse:AssetDisposal',
            'View:FixedAssetReports',
        ] as $permission) {
            $this->assertSame(1, Permission::query()->where('name', $permission)->count(), $permission);
        }
    }
}
