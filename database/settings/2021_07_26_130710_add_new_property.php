<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

class addNewProperty extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.max_idle_time', 30);
    }
}
