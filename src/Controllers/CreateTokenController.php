<?php

require_once APP_ROOT . '/jwt_utils.php';


class CreateToken
{
    public function index(): void
    {
        $token = generateJWT(['id' => '21603a56-2bf2-4a69-a109-a8ef3baac986', 'role' => 'admin'], $_ENV['JWT_SECRET']);
        echo $token;
    }
}
