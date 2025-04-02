<?php
// Exemplo de atualização de dados com ID
$data = array(
    "email" => "Zciclano.novo@ifsul.edu.br"
);

$curl = curl_init("https://cstsibda-default-rtdb.firebaseio.com/users/6.json");
curl_setopt_array($curl, array(
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "PATCH",
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => array(
        "cache-control: no-cache",
        "Content-Type: application/json",
        'Content-Length: ' . strlen(json_encode($data))
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