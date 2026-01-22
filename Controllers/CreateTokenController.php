<?php

require_once __DIR__ . "/../jwt_utils.php";

class CreateToken
{
    public function index(): void
    {
        $token = generateJWT(['id' => '21603a56-2bf2-4a69-a109-a8ef3baac986', 'role' => 'admin'], 'sua_chave_secreta_super_segura_aqui_2024');
        echo $token;
    }
}
