
<?php

$url=$_GET['url'];
$value= (empty($_GET['value'])) ? 'empty' : $_GET['value'];
//Initiate cURL.
$ch = curl_init($url);



$jsonData='{"text":"'.$value.'"}';

 
//Attach our encoded JSON string to the POST fields.
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
 
//Set the content type to application/json
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); 
 
//Execute the request
$result = curl_exec($ch);
echo $response
?>

