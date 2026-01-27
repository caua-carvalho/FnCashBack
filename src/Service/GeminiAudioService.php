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

        error_log("[GeminiAudioService] Inicializado | model={$this->model}");
    }

    public function processAudio(string $audioPath): array
    {
        error_log("[GeminiAudioService] processAudio iniciado | path={$audioPath}");

        if (!is_readable($audioPath)) {
            throw new \RuntimeException("Arquivo não legível: {$audioPath}");
        }

        $mimeType = mime_content_type($audioPath);
        if ($mimeType === false) {
            throw new \RuntimeException("Falha ao detectar MIME type");
        }

        $numBytes = filesize($audioPath);
        if ($numBytes <= 0) {
            throw new \RuntimeException("Arquivo vazio");
        }

        error_log("[GeminiAudioService] Áudio OK | mime={$mimeType} | bytes={$numBytes}");

        /**
         * 1️⃣ Start resumable upload
         */
        error_log("[GeminiAudioService] Iniciando upload resumable");

        $metadata = json_encode(['file' => ['display_name' => 'AUDIO']]);
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

        error_log("[GeminiAudioService] response bruto:" . $response);
        
        if ($response === false) {
            throw new \RuntimeException("Erro curl (start): " . curl_error($ch));
        }

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $headerSize);

        error_log("[GeminiAudioService] Upload start headers:\n{$headers}");

        if (!preg_match('/x-goog-upload-url:\s*(.+)/i', $headers, $matches)) {
            throw new \RuntimeException("Upload URL não encontrada nos headers");
        }

        $uploadUrl = trim($matches[1]);
        error_log("[GeminiAudioService] Upload URL obtida");

        /**
         * 2️⃣ Upload do arquivo
         */
        error_log("[GeminiAudioService] Enviando áudio");

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
            throw new \RuntimeException("Erro curl (upload): " . curl_error($ch));
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        error_log("[GeminiAudioService] Upload status={$status}");
        error_log("[GeminiAudioService] Upload response={$uploadResponse}");

        if ($status !== 200 && $status !== 201) {
            throw new \RuntimeException("Falha no upload ({$status})");
        }

        $fileInfo = json_decode($uploadResponse, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("JSON inválido no upload");
        }

        if (!isset($fileInfo['file']['uri'])) {
            throw new \RuntimeException("URI do arquivo não retornada");
        }

        $fileUri = $fileInfo['file']['uri'];
        error_log("[GeminiAudioService] Upload concluído | uri={$fileUri}");

        /**
         * 3️⃣ generateContent
         */
        error_log("[GeminiAudioService] Chamando Gemini generateContent");

        $payload = [
            "contents" => [
                [
                    "parts" => [
                        [
                            "text" =>
                                "Retorne exclusivamente um JSON válido, sem markdown ou texto extra, " .
                                "com campos: amount, category (Alimentação|Transporte|Compras|Contas|Saúde), " .
                                "type (expense|income), date (hoje=0, ontem=-1, amanhã=1), description, confidence"
                        ],
                        [
                            "file_data" => [
                                "mime_type" => $mimeType,
                                "file_uri" => $fileUri
                            ]
                        ]
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
            throw new \RuntimeException("Erro curl (generate): " . curl_error($ch));
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        error_log("[GeminiAudioService] generateContent status={$status}");
        error_log("[GeminiAudioService] generateContent response={$response}");

        if ($status !== 200) {
            throw new \RuntimeException("Gemini retornou HTTP {$status}");
        }

        /**
         * 4️⃣ Extração robusta do JSON
         */
        $responseData = json_decode($response, true);
        $text = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';

        error_log("[GeminiAudioService] Texto retornado pelo modelo: {$text}");

        if (!preg_match('/\{.*\}/s', $text, $matches)) {
            throw new \RuntimeException("JSON não encontrado na resposta");
        }

        $data = json_decode($matches[0], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("JSON inválido retornado pelo Gemini");
        }

        error_log("[GeminiAudioService] JSON decodificado com sucesso");

        /**
         * 5️⃣ Normalização
         */
        if (isset($data['date']) && is_numeric($data['date'])) {
            $offset = (int) $data['date'];
            $data['date'] = (new \DateTimeImmutable())
                ->modify(($offset >= 0 ? '+' : '') . "{$offset} day")
                ->format('Y-m-d');
        } else {
            $data['date'] = (new \DateTime())->format('Y-m-d');
        }

        $data['amount'] = (float) ($data['amount'] ?? 0);
        $data['category'] = $data['category'] ?? 'Outros';
        $data['type'] = $data['type'] ?? 'expense';
        $data['description'] = $data['description'] ?? '';
        $data['confidence'] = (float) ($data['confidence'] ?? 0);

        error_log("[GeminiAudioService] Processamento finalizado com sucesso");

        return $data;
    }
}
