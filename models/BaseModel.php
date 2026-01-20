<?php
/**
 * Classe Base para Modelos
 * Fornece métodos comuns para operações de banco de dados (CRUD)
 */
abstract class BaseModel {
    protected $table = '';
    protected $db = null;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $hidden = [];
    protected $timestamps = true;
    protected $createdAt = 'created_at';
    protected $updatedAt = 'updated_at';

    public function __construct() {
        $this->db = DatabaseConnection::getInstance()->getConnection();
        if (!$this->table) {
            throw new Exception('Propriedade $table não definida em ' . get_class($this));
        }
    }

    /**
     * Encontra um registro pelo ID
     */
    public function find($id) {
        $query = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $result = DatabaseConnection::getInstance()->fetch($query, [$id]);
        return $result ? $this->formatResult($result) : null;
    }

    /**
     * Encontra todos os registros
     */
    public function all($limit = null, $offset = 0) {
        $query = "SELECT * FROM {$this->table}";
        
        if ($limit) {
            $query .= " LIMIT {$limit} OFFSET {$offset}";
        }
        
        $results = DatabaseConnection::getInstance()->fetchAll($query);
        return array_map([$this, 'formatResult'], $results);
    }

    /**
     * Encontra registros com filtros
     */
    public function where($column, $operator = null, $value = null) {
        // Suporta where($column, $value) ou where($column, $operator, $value)
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $query = "SELECT * FROM {$this->table} WHERE {$column} {$operator} ?";
        $results = DatabaseConnection::getInstance()->fetchAll($query, [$value]);
        return array_map([$this, 'formatResult'], $results);
    }

    /**
     * Encontra apenas o primeiro resultado
     */
    public function first($column = null, $operator = null, $value = null) {
        if (!$column) {
            $query = "SELECT * FROM {$this->table} LIMIT 1";
            $result = DatabaseConnection::getInstance()->fetch($query);
        } else {
            if ($value === null) {
                $value = $operator;
                $operator = '=';
            }
            $query = "SELECT * FROM {$this->table} WHERE {$column} {$operator} ? LIMIT 1";
            $result = DatabaseConnection::getInstance()->fetch($query, [$value]);
        }
        return $result ? $this->formatResult($result) : null;
    }

    /**
     * Conta registros
     */
    public function count($column = null, $operator = null, $value = null) {
        if (!$column) {
            $query = "SELECT COUNT(*) as count FROM {$this->table}";
            $result = DatabaseConnection::getInstance()->fetch($query);
        } else {
            if ($value === null) {
                $value = $operator;
                $operator = '=';
            }
            $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE {$column} {$operator} ?";
            $result = DatabaseConnection::getInstance()->fetch($query, [$value]);
        }
        return $result ? $result['count'] : 0;
    }

    /**
     * Cria um novo registro
     */
    public function create($data) {
        $filteredData = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $filteredData[$this->createdAt] = date('Y-m-d H:i:s');
            $filteredData[$this->updatedAt] = date('Y-m-d H:i:s');
        }

        $columns = implode(', ', array_keys($filteredData));
        $placeholders = implode(', ', array_fill(0, count($filteredData), '?'));
        
        $query = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = DatabaseConnection::getInstance()->execute($query, array_values($filteredData));
        
        return [
            'success' => $stmt->rowCount() > 0,
            'id' => DatabaseConnection::getInstance()->lastInsertId(),
            'affected_rows' => $stmt->rowCount()
        ];
    }

    /**
     * Atualiza um registro pelo ID
     */
    public function update($id, $data) {
        $filteredData = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $filteredData[$this->updatedAt] = date('Y-m-d H:i:s');
        }

        $setClauses = [];
        $values = [];

        foreach ($filteredData as $column => $value) {
            $setClauses[] = "{$column} = ?";
            $values[] = $value;
        }

        $values[] = $id;
        $setString = implode(', ', $setClauses);
        
        $query = "UPDATE {$this->table} SET {$setString} WHERE {$this->primaryKey} = ?";
        $stmt = DatabaseConnection::getInstance()->execute($query, $values);
        
        return [
            'success' => $stmt->rowCount() > 0,
            'affected_rows' => $stmt->rowCount()
        ];
    }

    /**
     * Delete um registro pelo ID
     */
    public function delete($id) {
        $query = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = DatabaseConnection::getInstance()->execute($query, [$id]);
        
        return [
            'success' => $stmt->rowCount() > 0,
            'affected_rows' => $stmt->rowCount()
        ];
    }

    /**
     * Executa uma query customizada
     */
    protected function query($sql, $params = []) {
        return DatabaseConnection::getInstance()->execute($sql, $params);
    }

    /**
     * Obtém todos os resultados de uma query customizada
     */
    protected function queryAll($sql, $params = []) {
        return DatabaseConnection::getInstance()->fetchAll($sql, $params);
    }

    /**
     * Obtém um resultado de uma query customizada
     */
    protected function queryOne($sql, $params = []) {
        return DatabaseConnection::getInstance()->fetch($sql, $params);
    }

    /**
     * Filtra os dados para apenas as colunas permitidas (fillable)
     */
    private function filterFillable($data) {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }

    /**
     * Formata o resultado (pode ser sobrescrito nas subclasses)
     */
    protected function formatResult($result) {
        if (is_array($result)) {
            foreach ($this->hidden as $column) {
                unset($result[$column]);
            }
        }
        return $result;
    }
}
?>
