<?php
// Create authorization header
$token = '5|Gj69pS1yFF9ap6HZ7q1pA1MtMZwFRZgh3z7dKq3J';
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
