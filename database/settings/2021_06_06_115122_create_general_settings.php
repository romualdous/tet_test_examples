<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

class CreateGeneralSettings extends SettingsMigration
{
    public function up(): void
    {

        $this->migrator->add('general.caller_time_rate', 0.1);
        $this->migrator->add('general.listener_time_rate', 0.1);
    }
}
