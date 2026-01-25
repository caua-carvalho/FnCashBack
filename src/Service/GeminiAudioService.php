<?php

namespace App\Service;

class GeminiAudioService
{
    private string $apiKey;
    private string $model;

    public function __construct(string $apiKey, string $model = 'gemini-3-flash-preview')
    {
        if (empty($apiKey)) {
            throw new \RuntimeException("GEMINI_API_KEY não definido");
        }

        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    /**
     * Recebe o caminho de um arquivo de áudio e retorna o JSON interpretado pelo Gemini
     */
    public function processAudio(string $audioPath): array
    {
        if (!file_exists($audioPath)) {
            throw new \RuntimeException("Arquivo de áudio não encontrado: $audioPath");
        }

        $mimeType = mime_content_type($audioPath);
        $numBytes = filesize($audioPath);
        $displayName = 'AUDIO';

        // === 1️⃣ Start resumable upload ===
        $metadata = json_encode(['file' => ['display_name' => $displayName]]);
        $ch = curl_init("https://generativelanguage.googleapis.com/upload/v1beta/files");
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "x-goog-api-key: {$this->apiKey}",
                "Content-Type: application/json",
                "X-Goog-Upload-Protocol: resumable",
                "X-Goog-Upload-Command: start",
                "X-Goog-Upload-Header-Content-Length: {$numBytes}",
                "X-Goog-Upload-Header-Content-Type: {$mimeType}"
            ],
            CURLOPT_POSTFIELDS => $metadata,
            CURLOPT_HEADER => true,
            CURLOPT_RETURNTRANSFER => true,
        ]);

        $response = curl_exec($ch);
        if ($response === false) {
            throw new \RuntimeException("Erro ao iniciar upload: " . curl_error($ch));
        }

        if (!preg_match('/x-goog-upload-url:\s*(\S+)/i', $response, $matches)) {
            throw new \RuntimeException("Não foi possível obter upload URL do Gemini");
        }
        $uploadUrl = trim($matches[1]);

        // === 2️⃣ Upload do arquivo ===
        $ch = curl_init($uploadUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "x-goog-api-key: {$this->apiKey}",
                "Content-Length: {$numBytes}",
                "X-Goog-Upload-Offset: 0",
                "X-Goog-Upload-Command: upload, finalize"
            ],
            CURLOPT_POSTFIELDS => file_get_contents($audioPath),
            CURLOPT_RETURNTRANSFER => true,
        ]);

        $uploadResponse = curl_exec($ch);
        if ($uploadResponse === false) {
            throw new \RuntimeException("Erro ao enviar áudio: " . curl_error($ch));
        }

        $fileInfo = json_decode($uploadResponse, true);
        if (!isset($fileInfo['file']['uri'])) {
            throw new \RuntimeException("Upload falhou, URI do arquivo não retornada");
        }
        $fileUri = $fileInfo['file']['uri'];

        // === 3️⃣ Chamada para gerar conteúdo a partir do arquivo ===
        $payload = [
            "contents" => [
                [
                    "parts" => [
                        ["text" => "Interprete este áudio e retorne apenas e exclusivamente um JSON, nao quero que tenha texto adicional, com amount, category ('Alimentação' | 'Transporte' | 'Compras' | 'Contas' | 'Saúde'), type('expense' | 'income'), date (mostre de forma indexada, exemplo, hoje = 0, ontem = -1, amanha = 1, caso o audio nao tenha data, presuma 0), description e confidence"],
                        ["file_data" => ["mime_type" => $mimeType, "file_uri" => $fileUri]]
                    ]
                ]
            ]
        ];

        $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent");
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "x-goog-api-key: {$this->apiKey}",
                "Content-Type: application/json"
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
        ]);

        $response = curl_exec($ch);
        if ($response === false) {
            throw new \RuntimeException("Erro ao chamar Gemini: " . curl_error($ch));
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($status !== 200) {
            throw new \RuntimeException("Gemini retornou HTTP {$status}: {$response}");
        }

        // === 4️⃣ Extrair JSON do texto retornado pelo modelo ===
        $responseData = json_decode($response, true);
        $text = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';

        $cleanJson = trim(str_replace(['```json', '```'], '', $text));
        $data = json_decode($cleanJson, true);

        if (!is_array($data)) {
            echo "Resposta do Gemini: " . $text . "\n";
            throw new \RuntimeException("Falha ao decodificar JSON retornado pelo Gemini");
        }

        // === 5️⃣ Converter campo date indexado em data real ===
        if (isset($data['date']) && is_numeric($data['date'])) {
            $dayOffset = (int) $data['date'];
            $data['date'] = (new \DateTime())->modify("{$dayOffset} day")->format('Y-m-d');
        }

        // === 6️⃣ Garantir campos obrigatórios para salvar no banco ===
        $data['amount'] = $data['amount'] ?? 0; // ou lance exceção se obrigatório
        $data['category'] = $data['category'] ?? 'Outros';
        $data['type'] = $data['type'] ?? 'expense';
        $data['description'] = $data['description'] ?? '';
        $data['confidence'] = $data['confidence'] ?? 0;

        return $data;
    }
}
