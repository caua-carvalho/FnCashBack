<?php
require_once __DIR__ . '/../Models/TransactionModel.php';
require_once __DIR__ . '/../jwt_utils.php';

class TransactionController
{
    private $model;

    public function __construct()
    {
        $this->model = new TransactionModel();
    }

    // GET /transactions
    public function index()
    {
        $user = $_GLOBALS['auth_user'] ?? null;
        
        if (!$user || !isset($user['id'])) {
            http_response_code(401);
            echo json_encode($user);
            echo json_encode(['error' => 'Usuário não autenticado', 'user' => $user]);
            return;
        } 
        $transactions = $this->model->findByUser($user['id']);
        header('Content-Type: application/json');
        echo json_encode($transactions);
        
    }

    // GET /transactions/{id}
    public function show()
    {
        $user = $GLOBALS['auth_user'] ?? null;
        $id = $_GET['id'] ?? null;
        if (!$user || !isset($user['id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Usuário não autenticado']);
            return;
        }
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID não informado']);
            return;
        }
        $transaction = $this->model->find($id);
        if (!$transaction || $transaction['user_id'] !== $user['id']) {
            http_response_code(404);
            echo json_encode(['error' => 'Transação não encontrada']);
            return;
        }
        header('Content-Type: application/json');
        echo json_encode($transaction);
    }

    // POST /transactions
    public function store()
    {
        $user = $GLOBALS['auth_user'] ?? null;
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$user || !isset($user['id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Usuário não autenticado']);
            return;
        }
        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Dados inválidos']);
            return;
        }
        $data['user_id'] = $user['id'];
        try {
            $id = $this->model->create($data);
            http_response_code(201);
            echo json_encode(['id' => $id]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function storeAudio()
    {
        $user = $GLOBALS['auth_user'] ?? null;

        if (!$user || !isset($user['id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Usuário não autenticado']);
            return;
        }

        if (!isset($_FILES['audio'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Arquivo de áudio não enviado']);
            return;
        }

        $file = $_FILES['audio'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['error' => 'Erro no upload do áudio']);
            return;
        }

        // Validação básica de MIME (não confie só nisso em produção)
        $allowedMimeTypes = [
            'audio/webm',
            'audio/wav',
            'audio/mpeg',
            'audio/mp3',
            'audio/ogg',
            'audio/m4a',
            'audio/x-m4a',
            'audio/mp4',
        ];

        if (!in_array($file['type'], $allowedMimeTypes, true)) {
            http_response_code(415);
            echo json_encode(['error' => 'Formato de áudio não suportado']);
            return;
        }

        $userId = $user['id'];
        $baseDir = __DIR__ . '/../uploads/audio/' . $userId;

        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0777, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'audio_' . time() . '.' . $extension;
        $filepath = $baseDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            http_response_code(500);
            echo json_encode(['error' => 'Falha ao salvar o arquivo']);
            return;
        }

        // URL relativa (ajuste conforme seu deploy)
        $audioUrl = '/uploads/audio/' . $userId . '/' . $filename;

        http_response_code(201);
        echo json_encode([
            'audio_url' => $audioUrl,
            'mime_type' => $file['type'],
            'size' => $file['size']
        ]);
    }


    // PUT /transactions/{id}
    public function update()
    {
        $user = $GLOBALS['auth_user'] ?? null;
        $id = $_GET['id'] ?? null;
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$user || !isset($user['id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Usuário não autenticado']);
            return;
        }
        if (!$id || !$data) {
            http_response_code(400);
            echo json_encode(['error' => 'ID ou dados inválidos']);
            return;
        }
        $transaction = $this->model->find($id);
        if (!$transaction || $transaction['user_id'] !== $user['id']) {
            http_response_code(404);
            echo json_encode(['error' => 'Transação não encontrada']);
            return;
        }
        try {
            $updated = $this->model->update($id, $data);
            if ($updated) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Transação não encontrada']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // DELETE /transactions/{id}
    public function destroy()
    {
        $user = $GLOBALS['auth_user'] ?? null;
        $id = $_GET['id'] ?? null;
        if (!$user || !isset($user['id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Usuário não autenticado']);
            return;
        }
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID não informado']);
            return;
        }
        $transaction = $this->model->find($id);
        if (!$transaction || $transaction['user_id'] !== $user['id']) {
            http_response_code(404);
            echo json_encode(['error' => 'Transação não encontrada']);
            return;
        }
        try {
            $deleted = $this->model->delete($id);
            if ($deleted) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Transação não encontrada']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
