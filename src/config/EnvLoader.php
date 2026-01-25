<?php
/**
 * Carregador de Variáveis de Ambiente
 * Suporta .env.local e .env
 */
class EnvLoader {
    public static function load() {
        $envFiles = [
            __DIR__ . '/.env.local',
            __DIR__ . '/.env'
        ];

        foreach ($envFiles as $file) {
            if (file_exists($file)) {
                self::loadFile($file);
                break;
            }
        }
    }

    private static function loadFile($file) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Ignora comentários
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse da variável
            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove aspas se existirem
                if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
                    (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
                    $value = substr($value, 1, -1);
                }
                
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }
}
?>
