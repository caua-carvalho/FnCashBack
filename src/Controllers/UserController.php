<?php
require_once APP_ROOT . '/Service/UserService.php';
require_once APP_ROOT . '/jwt_utils.php';


class UserController
{
    private $UserService;

    public function __construct()
    {
        $this->UserService = new UserService();
    }

    public function login(): void

    {
        $data = json_decode(file_get_contents('php://input'), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            http_response_code(400);
            echo json_encode(['error' => 'Email e senha são obrigatórios']);
            return;
        }

        $user = $this->UserService->login($email, $password);
        
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Credenciais inválidas']);
            return;
        }


        $token = generateJWT([
            'id'   => $user['id'],
            'email' => $user['email'],
            'name'  => $user['name']
        ], $_ENV['JWT_SECRET']);

        header('Content-Type: application/json');
        echo json_encode([
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name']
            ]
        ]);
    }
}