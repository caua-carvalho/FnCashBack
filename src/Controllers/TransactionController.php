<?php
require_once APP_ROOT . '/Models/TransactionModel.php';
require_once APP_ROOT . '/jwt_utils.php';


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
        $user = $GLOBALS['auth_user'] ?? null;
        
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

    public function storeAudio(): void
    {
        error_log('[TransactionController::storeAudio] START');

        /**
         * Auth
         */
        $user = $GLOBALS['auth_user'] ?? null;

        if (!$user || empty($user['id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Usuário não autenticado']);
            return;
        }

        $userId = $user['id'];

        /**
         * Upload validation
         */
        if (empty($_FILES['audio'])) {
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

        /**
         * MIME real (não confie em $_FILES['type'])
         */
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);

        $allowedMimeTypes = [
            'audio/webm',
            'audio/wav',
            'audio/mpeg',
            'audio/ogg',
            'audio/mp4',
            'audio/x-m4a',
        ];

        if (!in_array($mime, $allowedMimeTypes, true)) {
            error_log("[storeAudio] MIME inválido: {$mime}");
            http_response_code(415);
            echo json_encode(['error' => 'Formato de áudio não suportado']);
            return;
        }

        /**
         * Diretório do usuário
         */
        $baseDir = APP_ROOT . "/uploads/audio/{$userId}";
        if (!is_dir($baseDir) && !mkdir($baseDir, 0775, true)) {
            http_response_code(500);
            echo json_encode(['error' => 'Falha ao criar diretório']);
            return;
        }

        /**
         * Arquivo original
         */
        $inputPath  = $baseDir . '/input_' . uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $outputPath = $baseDir . '/audio_' . uniqid() . '.wav';

        if (!move_uploaded_file($file['tmp_name'], $inputPath)) {
            http_response_code(500);
            echo json_encode(['error' => 'Falha ao salvar áudio']);
            return;
        }

        /**
         * Normalização com FFmpeg
         * - wav
         * - mono
         * - 16kHz (ideal para STT)
         */
        $cmd = sprintf(
            'ffmpeg -y -i %s -ac 1 -ar 16000 %s 2>&1',
            escapeshellarg($inputPath),
            escapeshellarg($outputPath)
        );

        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0 || !file_exists($outputPath)) {
            error_log('[storeAudio] FFmpeg error: ' . implode("\n", $output));
            http_response_code(500);
            echo json_encode(['error' => 'Falha ao processar áudio']);
            return;
        }

        /**
         * Processamento IA
         */
        try {
            require_once APP_ROOT . '/Service/GeminiAudioService.php';

            $gemini = new \App\Service\GeminiAudioService(
                $_ENV['GEMINI_API_KEY'] ?? ''
            );

            $transactionData = $gemini->processAudio($outputPath);
        } catch (\Throwable $e) {
            error_log('[storeAudio] IA ERROR: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Falha ao processar áudio']);
            return;
        }

        /**
         * Cleanup opcional
         */
        @unlink($inputPath);

        /**
         * Response
         */
        echo json_encode([
            'success' => true,
            'data' => $transactionData
        ]);
    }



    public function create() {
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
            echo json_encode([
                'data'      => $data,
                'erro'      => null,
                'success'   => true
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
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
