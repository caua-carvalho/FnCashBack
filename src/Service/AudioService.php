<?php

class AudioService
{
    public function storeAudio($file, $user)
    {
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

        return [
            'url' => $audioUrl,
        ];
    }

    public function processAudio($userId, $audioData)
    {
        // Lógica para processar o áudio e extrair informações
        // Esta é uma implementação fictícia para fins de exemplo
        if (empty($audioData)) {
            throw new Exception('Dados de áudio inválidos');
        }

        // Simular processamento e retorno de dados extraídos
        return [
            'amount' => 100.00 ,
            'category' => 'Alimentação',
            'type' => 'expense',
            'description' => 'Almoço',
            'confidence' => 0.95
        ];
    }
}