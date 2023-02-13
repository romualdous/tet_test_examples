<?php

$username = "root";
$password = "root";
$host = "localhost";
$database = "drivein";
$d=strtotime("now");
$d2 = date("Y-m-d h:i:s", $d);


$mysqli = new mysqli($host, $username, $password, $database);
$result = $mysqli->query("
SELECT reminder.sent_time,reminder.id as r_id,users.id as u_id,reminder.text as text
FROM reminder
    INNER JOIN cars ON reminder.car_reminder_id = cars.id
    INNER JOIN users ON cars.user_id = users.id
    INNER JOIN devices ON users.id = devices.userID
WHERE reminder.reminder_time <= '$d2' && reminder.sent_time IS NULL && devices.deleted = false");
var_dump($result);
while ($row = $result->fetch_assoc()) {
    $reminderid= $row['r_id'];
    $result3 = $mysqli->query("UPDATE reminder SET sent_time = '$d2' WHERE id = '$reminderid'");
    //Sending push notification
    $to = $row['u_id'];
    $data = array(
        'body' => 'New messeage'
    );
    $apiKey = 'AAAAr5xPYnM:APA91bEMNOCGAA-aWWLvWOvXG2g65KJXFnWu_c6K-jA0WY3inMh7nPcKoJn0N5BtH9sItrA_UOXLSYCPFGUiXunlr34ZIkAfAO8chs2a0pzGWIkPVWe17oI3srPWYj1kD9qIHD8M0B1u';
    $fields = array(
        'to' => $to,
        'notification' => $data);

    $headers = array( 'Authorization: key=' . $apiKey, 'Content-Type: application/json');
    $url = 'https://fcm.googleapis.com/fcm/send';
    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_URL, $url);
    curl_setopt( $ch, CURLOPT_POST, true);
    curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch,  CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode($fields));

    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result, true);

}
?>
