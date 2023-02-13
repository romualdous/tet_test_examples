<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('EachDay:IdleUser', function () {
    /*
    //TODO: Use this code,when will be new authorization method,for this script.
    $token = '';
    $arr_header = "Authorization: Bearer " . $token;

    $url = 'http://localhost/api/activity/checkIdleLiteners';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        $arr_header,
        'Content-Type: application/json'
    ));
    $data = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($httpcode>=200 && $httpcode<300) ? $data : false;
    */

    // Getting current time and configuration value. To calculate time,until person is valid and dont need to be modyfied.
    $get_idle_time = app(GeneralSettings::class)->max_idle_time;
    $get_time = date('Y-m-d H:i:s', strtotime("-{$get_idle_time} minutes"));

    $getallusers = DB::table('users')
        ->where('type', '=', 'listener')
        ->where('last_activity_date', '<', $get_time)
        ->orWhere('last_activity_date', '=', null)
        ->select('status', 'id')
        ->get();

    foreach ($getallusers as $oneuser)
    {
        DB::update('update users set status = ?, last_activity_date = ? where id = ?', ['offline', null, $oneuser->id]);
    }
})->purpose('Refreshing User');
