<?php

final class DatabaseConfig
{
    private static ?self $instance = null;
    private array $config;

    private function __construct()
    {
        $env = getenv('APP_ENV') ?: 'development';

        $this->config = [
            'development' => $this->buildFromEnv('postgres'),
            'production'  => $this->buildFromEnv('fncash'),
            'testing'     => [
                'host'     => 'localhost',
                'port'     => 5432,
                'user'     => 'test_user',
                'password' => 'test_password',
                'database' => 'fncash_test',
                'options'  => $this->defaultOptions(),
            ],
        ];

        if (!isset($this->config[$env])) {
            throw new RuntimeException("APP_ENV inválido: {$env}");
        }
    }

    private function buildFromEnv(string $defaultDb): array
    {
        $host = getenv('DB_HOST');
        $user = getenv('DB_USER');
        $pass = getenv('DB_PASSWORD');
        $db   = getenv('DB_NAME') ?: $defaultDb;
        $port = (int)(getenv('DB_PORT') ?: 5432);

        // 🔥 DEBUG E FAIL FAST
        $missing = [];
        foreach ([
            'DB_HOST' => $host,
            'DB_USER' => $user,
            'DB_PASSWORD' => $pass,
        ] as $key => $value) {
            if ($value === false || $value === '') {
                $missing[] = $key;
            }
        }

        if ($missing) {
            throw new RuntimeException(
                'Variáveis de ambiente ausentes: ' . implode(', ', $missing)
            );
        }

        return [
            'host'     => $host,
            'port'     => $port,
            'user'     => $user,
            'password' => $pass,
            'database' => $db,
            'options'  => $this->defaultOptions(),
        ];
    }

    private function defaultOptions(): array
    {
        return [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public function getConfig(): array
    {
        $env = getenv('APP_ENV') ?: 'development';
        return $this->config[$env];
    }

    public function getDSN(): string
    {
        $c = $this->getConfig();

        return sprintf(
            'pgsql:host=%s;port=%d;dbname=%s',
            $c['host'],
            $c['port'],
            $c['database']
        );
    }

    public function getCredentials(): array
    {
        $c = $this->getConfig();

        return [
            'user'     => $c['user'],
            'password' => $c['password'],
        ];
    }

    public function getOptions(): array
    {
        return $this->getConfig()['options'];
    }
}
