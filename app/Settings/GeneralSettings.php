<?php

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public float $caller_time_rate;

    public float $listener_time_rate;

    public int $max_idle_time;

    public static function group(): string
    {
        return 'general';
    }
}
