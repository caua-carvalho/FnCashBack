<?php
// Funções utilitárias para JWT
function getBearerToken() {
    $headers = apache_request_headers();
    if (isset($headers['Authorization'])) {
        $matches = array();
        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            return $matches[1];
        }
    }
    return null;
}

function validateJWT($jwt, $secret) {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) return false;

    $header = json_decode(base64_decode($parts[0]), true);
    $payload = json_decode(base64_decode($parts[1]), true);

    $signature = $parts[2];
    $valid_signature = rtrim(strtr(base64_encode(hash_hmac('sha256', "$parts[0].$parts[1]", $secret, true)), '+/', '-_'), '=');
    if ($signature !== $valid_signature) return false;
    if (isset($payload['exp']) && $payload['exp'] < time()) return false;
    return $payload;
}

function generateJWT($payload, $secret) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload['iat'] = time();
    $payload['exp'] = time() + (24 * 60 * 60); // 24 horas
    $payload_encoded = json_encode($payload);
    
    $header_base64 = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
    $payload_base64 = rtrim(strtr(base64_encode($payload_encoded), '+/', '-_'), '=');
    
    $signature = rtrim(strtr(base64_encode(hash_hmac('sha256', "$header_base64.$payload_base64", $secret, true)), '+/', '-_'), '=');
    
    echo "Header: " . $payload['iat'] . "<br>";
    echo "Payload: " . $payload['exp'] . "<br>";
    echo "Signature: " . $signature . "<br>";
    return "$header_base64.$payload_base64.$signature";
}