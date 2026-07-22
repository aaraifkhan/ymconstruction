<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.company_name', '');
        $this->migrator->add('general.company_email', '');
        $this->migrator->add('general.company_phone', '');
        $this->migrator->add('general.company_address', '');
        $this->migrator->add('general.timezone', 'UTC');
        $this->migrator->add('general.locale', 'en');
        $this->migrator->add('general.seo_title', '');
        $this->migrator->add('general.seo_description', '');
        $this->migrator->add('general.seo_keywords', '');
    }
};
