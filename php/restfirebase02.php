<?php

$data = array(
    "nome" => "Ciclano",
    "email" => "ciclano@ifsul.edu.br"
);

$curl = curl_init("https://cstsibda-default-rtdb.firebaseio.com/users.json");
//curl_setopt_array($curl, array(
//    CURLOPT_RETURNTRANSFER => true,
//    CURLOPT_ENCODING => "",
//    CURLOPT_MAXREDIRS => 10,
//    CURLOPT_TIMEOUT => 30,
//    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//    CURLOPT_POST  => true,
//    CURLOPT_POSTFIELDS => json_encode($data),
//    CURLOPT_HTTPHEADER => array(
//        "cache-control: no-cache",
//        "Content-Type: application/json",
//        "X-Firebase-ETag: true",
//        "ETag: 2",
//        'Content-Length: ' . strlen(json_encode($data))
//        //"x-api-key: whateveriyouneedinyourheader"
//    ),
//));
//$response = curl_exec($curl);
curl_close($curl);


$data2 = array(
    "nome" => "ZCiclano",
    "email" => "Zciclano@ifsul.edu.br"
);
$curl = curl_init("https://cstsibda-default-rtdb.firebaseio.com/users/6.json");
curl_setopt_array($curl, array(
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "PUT",
    CURLOPT_POSTFIELDS => json_encode($data2),
    CURLOPT_HTTPHEADER => array(
        "cache-control: no-cache",
        "Content-Type: application/json",
        "X-Firebase-ETag: true",
        'Content-Length: ' . strlen(json_encode($data2))
        //"x-api-key: whateveriyouneedinyourheader"
    ),
));


$response = curl_exec($curl);
print_r($response);


$err = curl_error($curl);

curl_close($curl);

if ($err) {
    echo "cURL Error #:" . $err;
} else {
    echo $response;
}