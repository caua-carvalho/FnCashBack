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

    public function storeAudio()
    {
        error_log('[TransactionController::storeAudio] Início da requisição');

        $user = $GLOBALS['auth_user'] ?? null;

        if (!$user || !isset($user['id'])) {
            error_log('[TransactionController::storeAudio] Usuário não autenticado');
            http_response_code(401);
            echo json_encode(['error' => 'Usuário não autenticado']);
            return;
        }

        $userId = $user['id'];
        error_log("[TransactionController::storeAudio] Usuário autenticado | user_id={$userId}");

        if (!isset($_FILES['audio'])) {
            error_log('[TransactionController::storeAudio] Nenhum arquivo enviado');
            http_response_code(400);
            echo json_encode(['error' => 'Arquivo de áudio não enviado']);
            return;
        }

        $file = $_FILES['audio'];

        error_log('[TransactionController::storeAudio] Arquivo recebido', [
            'name' => $file['name'],
            'type' => $file['type'],
            'size' => $file['size'],
            'error' => $file['error']
        ]);

        if ($file['error'] !== UPLOAD_ERR_OK) {
            error_log('[TransactionController::storeAudio] Erro no upload | code=' . $file['error']);
            http_response_code(400);
            echo json_encode(['error' => 'Erro no upload do áudio']);
            return;
        }

        /**
         * Validação de MIME
         */
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
            error_log("[TransactionController::storeAudio] MIME não suportado: {$file['type']}");
            http_response_code(415);
            echo json_encode(['error' => 'Formato de áudio não suportado']);
            return;
        }

        /**
         * Diretório do usuário
         */
        $baseDir = __DIR__ . '/../uploads/audio/' . $userId;

        if (!is_dir($baseDir)) {
            error_log("[TransactionController::storeAudio] Criando diretório: {$baseDir}");
            if (!mkdir($baseDir, 0777, true) && !is_dir($baseDir)) {
                error_log('[TransactionController::storeAudio] Falha ao criar diretório');
                http_response_code(500);
                echo json_encode(['error' => 'Falha ao preparar diretório de upload']);
                return;
            }
        }

        /**
         * Geração do nome do arquivo
         */
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'audio_' . time() . '.' . $extension;
        $filepath = $baseDir . '/' . $filename;

        error_log("[TransactionController::storeAudio] Salvando arquivo em {$filepath}");

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            error_log('[TransactionController::storeAudio] move_uploaded_file falhou');
            http_response_code(500);
            echo json_encode(['error' => 'Falha ao salvar o arquivo']);
            return;
        }

        error_log('[TransactionController::storeAudio] Upload concluído com sucesso');

        /**
         * Processamento com Gemini
         */
        try {
            error_log('[TransactionController::storeAudio] Inicializando GeminiAudioService');

            require_once APP_ROOT . '/Service/GeminiAudioService.php';

            // ⚠️ OBS: API key hardcoded é erro arquitetural (logando só para debug)
            error_log('[TransactionController::storeAudio] Chamando processAudio');

            $gemini = new \App\Service\GeminiAudioService(
                $_ENV['GEMINI_API_KEY'] ?? 'API_KEY_NAO_DEFINIDA'
            );

            $transactionData = $gemini->processAudio($filepath);

            error_log('[TransactionController::storeAudio] Gemini retornou dados válidos');
            error_log('[TransactionController::storeAudio] Resultado: ' . json_encode($transactionData));
        } catch (\Throwable $e) {
            error_log('[TransactionController::storeAudio] ERRO Gemini: ' . $e->getMessage());
            error_log($e->getTraceAsString());

            http_response_code(500);
            echo json_encode(['error' => 'Falha ao processar áudio']);
            return;
        }

        /**
         * Resposta final
         */
        error_log('[TransactionController::storeAudio] Finalizando request com sucesso');

        echo json_encode($transactionData);
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
