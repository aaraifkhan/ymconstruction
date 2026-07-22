<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.brand_name', 'YM Construction');
        $this->migrator->add('general.brand_logo', null);
        $this->migrator->add('general.primary_color', '#1e40af');
    }
};
