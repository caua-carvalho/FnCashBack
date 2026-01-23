<?php
/**
 * Configuração do Banco de Dados
 * Centraliza todas as configurações de conexão
 */
class DatabaseConfig {
    private static $instance = null;
    private $config = [];

    private function __construct() {
        // Detectar ambiente (desenvolvimento, produção, teste)
        $environment = $_ENV['APP_ENV'] ?? 'development';
        
        $this->config = [
            'development' => [
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'port' => $_ENV['DB_PORT'] ?? 5432,
                'user' => $_ENV['DB_USER'] ?? 'postgres',
                'password' => $_ENV['DB_PASSWORD'] ?? '',
                'database' => $_ENV['DB_NAME'] ?? 'postgres',
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            ],
            'production' => [
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'port' => $_ENV['DB_PORT'] ?? 5432,
                'user' => $_ENV['DB_USER'] ?? 'postgres',
                'password' => $_ENV['DB_PASSWORD'] ?? '',
                'database' => $_ENV['DB_NAME'] ?? 'fncash',
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            ],
            'testing' => [
                'host' => 'localhost',
                'port' => 5432,
                'user' => 'test_user',
                'password' => 'test_password',
                'database' => 'fncash_test',
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            ]
        ];
    }

    /**
     * Obtém a instância única (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retorna a configuração do ambiente atual
     */
    public function getConfig($environment = null) {
        if (!$environment) {
            $environment = $_ENV['APP_ENV'] ?? 'development';
        }
        
        if (!isset($this->config[$environment])) {
            throw new Exception("Ambiente '{$environment}' não configurado");
        }
        
        return $this->config[$environment];
    }

    /**
     * Constrói a DSN (Data Source Name) para PDO
     */
    public function getDSN($environment = null) {
        $config = $this->getConfig($environment);
        // DSN para PostgreSQL: pgsql:host=HOST;port=PORT;dbname=DBNAME
        return sprintf(
            'pgsql:host=%s;port=%d;dbname=%s',
            $config['host'],
            $config['port'],
            $config['database']
        );
    }

    /**
     * Retorna as credenciais do banco
     */
    public function getCredentials($environment = null) {
        $config = $this->getConfig($environment);
        return [
            'user' => $config['user'],
            'password' => $config['password']
        ];
    }

    /**
     * Retorna as opções PDO
     */
    public function getOptions($environment = null) {
        $config = $this->getConfig($environment);
        return $config['options'];
    }
}
?>
