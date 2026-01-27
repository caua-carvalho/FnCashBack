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

        try {
            $normalizedPath = $this->normalizeAudio($filepath);
            unlink($filepath); // remove o original
        } catch (RuntimeException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao normalizar áudio']);
            return;
        }

        $audioUrl = '/uploads/audio/' . $userId . '/' . basename($normalizedPath);

        return [
            'url' => $audioUrl,
            'format' => 'wav',
            'sample_rate' => 16000,
            'channels' => 1,
        ];
    }

    public function processAudio($userId, $audioData)
    {
        if (empty($audioData)) {
            throw new Exception('Dados de áudio inválidos');
        }

        return [
            'amount' => 100.00,
            'category' => 'Alimentação',
            'type' => 'expense',
            'description' => 'Almoço',
            'confidence' => 0.95,
        ];
    }

    private function normalizeAudio(string $inputPath): string
    {
        if (!file_exists($inputPath)) {
            throw new RuntimeException('Arquivo de áudio não encontrado');
        }

        $outputPath = preg_replace('/\.[^.]+$/', '', $inputPath) . '_normalized.wav';

        $command = sprintf(
            'ffmpeg -y -i %s -ac 1 -ar 16000 -f wav %s 2>&1',
            escapeshellarg($inputPath),
            escapeshellarg($outputPath)
        );

        exec($command, $output, $exitCode);

        if ($exitCode !== 0 || !file_exists($outputPath)) {
            throw new RuntimeException(
                'Falha ao normalizar áudio: ' . implode("\n", $output)
            );
        }

        return $outputPath;
    }
}
