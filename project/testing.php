<?php
$ch = curl_init("https://api.sandbox.africastalking.com/version1/messaging");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
if(curl_errno($ch)) {
    echo 'cURL error: ' . curl_error($ch);
} else {
    echo 'Success!';
}
curl_close($ch);
