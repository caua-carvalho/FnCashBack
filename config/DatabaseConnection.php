<?php
require_once 'Database.php';
/**
 * Gerenciador de Conexão com Banco de Dados
 * Implementa o padrão Singleton para garantir uma única conexão
 */
class DatabaseConnection {
    private static $instance = null;
    private $connection = null;
    private $isConnected = false;
    private $lastQuery = null;
    private $config = null;

    private function __construct() {
        $this->config = DatabaseConfig::getInstance();
    }

    /**
     * Obtém a instância única da conexão
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Conecta ao banco de dados
     */
    public function connect($environment = null) {
        if ($this->isConnected && $this->connection !== null) {
            return $this->connection;
        }

        try {
            $dsn = $this->config->getDSN($environment);
            $credentials = $this->config->getCredentials($environment);
            $options = $this->config->getOptions($environment);

            $this->connection = new PDO(
                $dsn,
                $credentials['user'],
                $credentials['password'],
                $options
            );

            $this->isConnected = true;
            error_log('✓ Conexão com banco de dados estabelecida com sucesso');
            return $this->connection;

        } catch (PDOException $e) {
            error_log('✗ Erro ao conectar com o banco de dados: ' . $e->getMessage());
            throw new Exception('Erro na conexão com o banco de dados: ' . $e->getMessage());
        }
    }

    /**
     * Obtém a conexão PDO (já conectada)
     */
    public function getConnection() {
        if (!$this->isConnected) {
            $this->connect();
        }
        return $this->connection;
    }

    /**
     * Verifica se está conectado
     */
    public function isConnected() {
        return $this->isConnected;
    }

    /**
     * Executa uma query preparada com segurança contra SQL Injection
     */
    public function execute($query, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($query);
            $this->lastQuery = $query;
            
            // Bind parameters seguramente
            foreach ($params as $key => $value) {
                $paramKey = is_int($key) ? $key + 1 : $key;
                $stmt->bindValue($paramKey, $value, $this->getPDOType($value));
            }
            
            $stmt->execute();
            return $stmt;

        } catch (PDOException $e) {
            error_log("Erro na query: {$query} - " . $e->getMessage());
            throw new Exception('Erro ao executar query: ' . $e->getMessage());
        }
    }

    /**
     * Executa uma query e retorna todos os resultados
     */
    public function fetchAll($query, $params = []) {
        $stmt = $this->execute($query, $params);
        return $stmt->fetchAll();
    }

    /**
     * Executa uma query e retorna apenas a primeira linha
     */
    public function fetch($query, $params = []) {
        $stmt = $this->execute($query, $params);
        return $stmt->fetch();
    }

    /**
     * Executa uma query e retorna apenas um valor (coluna)
     */
    public function fetchColumn($query, $params = [], $columnIndex = 0) {
        $stmt = $this->execute($query, $params);
        return $stmt->fetchColumn($columnIndex);
    }

    /**
     * Executa uma query e retorna o ID do último registro inserido
     */
    public function lastInsertId() {
        return $this->getConnection()->lastInsertId();
    }

    /**
     * Retorna o número de linhas afetadas
     */
    public function rowCount($stmt) {
        return $stmt->rowCount();
    }

    /**
     * Inicia uma transação
     */
    public function beginTransaction() {
        return $this->getConnection()->beginTransaction();
    }

    /**
     * Confirma uma transação
     */
    public function commit() {
        return $this->getConnection()->commit();
    }

    /**
     * Desfaz uma transação
     */
    public function rollback() {
        return $this->getConnection()->rollback();
    }

    /**
     * Executa múltiplas queries em uma transação
     */
    public function transaction(callable $callback) {
        try {
            $this->beginTransaction();
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Desconecta do banco de dados
     */
    public function disconnect() {
        $this->connection = null;
        $this->isConnected = false;
        error_log('Conexão com banco de dados encerrada');
    }

    /**
     * Determina o tipo PDO baseado no valor
     */
    private function getPDOType($value) {
        if (is_int($value)) {
            return PDO::PARAM_INT;
        } elseif (is_bool($value)) {
            return PDO::PARAM_BOOL;
        } elseif (is_null($value)) {
            return PDO::PARAM_NULL;
        }
        return PDO::PARAM_STR;
    }

    /**
     * Retorna a última query executada (para debug)
     */
    public function getLastQuery() {
        return $this->lastQuery;
    }
}
?>
