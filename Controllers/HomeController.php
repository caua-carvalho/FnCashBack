<?php

require_once __DIR__ . "/../jwt_utils.php";

class HomeController
{
    public function index(): void
    {
        $payload = ['user_id' => 123, 'username' => 'john_doe'];
        generateJWT($payload, $_ENV['JWT_SECRET'] ?? 'changeme', 3600);
    }

    public function about(): void
    {
        echo 'Sobre o sistema';
    }
}
