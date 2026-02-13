<?php
// --------------------
// ENV
// --------------------
require_once APP_ROOT . '/vendor/autoload.php';

// Carrega env
$dotenv = Dotenv\Dotenv::createImmutable(APP_ROOT);
$dotenv->load();


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

function validateJWT($jwt) {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) return false;

    $header = json_decode(base64_decode($parts[0]), true);
    $payload = json_decode(base64_decode($parts[1]), true);

    $signature = $parts[2];
    $valid_signature = rtrim(strtr(base64_encode(hash_hmac('sha256', "$parts[0].$parts[1]", $_ENV['JWT_SECRET'], true)), '+/', '-_'), '=');
    if ($signature !== $valid_signature) return false;
    if (isset($payload['exp']) && $payload['exp'] < time()) return false;
    return $payload;
}

function generateJWT(array $payload): string
{
    $header = [
        'typ' => 'JWT',
        'alg' => 'HS256'
    ];

    $payload['iat'] = time();
    $payload['exp'] = time() + (24 * 60 * 60); // 24h

    $headerBase64  = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
    $payloadBase64 = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');

    $signature = rtrim(
        strtr(
            base64_encode(
                hash_hmac('sha256', "$headerBase64.$payloadBase64", $_ENV['JWT_SECRET'], true)
            ),
            '+/',
            '-_'
        ),
        '='
    );

    return "$headerBase64.$payloadBase64.$signature";
}
