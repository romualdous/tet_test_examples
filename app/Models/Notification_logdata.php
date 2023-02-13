<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification_logdata extends Model
{
    use HasFactory;

    protected $fillable = [
        'sendToUser',
        'device_token',
        'title',
        'data',
        'curl_response',
        'body'
    ];
}
