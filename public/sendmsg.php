<?php

$mobileNumber = "9682527195";

$senderId = "tester";

$msg = "hello niraj";

$message = urlencode($msg);

$route = 4;

//Prepare you post parameters
$postData = array(
    'mobiles' => $mobileNumber,
    'message' => $message,
    'sender' => $senderId,
    'route' => $route
);

$url="http://my.msgwow.com/api/v2/sendsms";


$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => "$url",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_HTTPHEADER => array(
        "authkey: 143650ARxcC3zO5b7e5edf",
        "content-type: multipart/form-data"
    ),
));

$response = curl_exec($curl);

$err = curl_error($curl);

curl_close($curl);

if ($err) {
    echo "cURL Error #:" . $err;
} else {
    echo $response;
}
?>
                        