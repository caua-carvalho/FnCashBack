
<?php
require_once __DIR__ . '/../config/DatabaseConnection.php';

/**
 * Classe BaseModel
 * Fornece métodos genéricos para acesso ao banco de dados (CRUD) para todos os models.
 * Deve ser estendida por cada model específico.
 */
abstract class BaseModel {
    // Nome da tabela no banco
    protected $table = '';
    // Conexão PDO
    protected $db = null;
    // Nome da chave primária
    protected $primaryKey = 'id';
    // Campos permitidos para inserção/atualização em massa
    protected $fillable = [];
    // Campos ocultos ao retornar dados
    protected $hidden = [];
    // Controla timestamps automáticos
    protected $timestamps = true;
    protected $createdAt = 'created_at';
    protected $updatedAt = 'updated_at';

    /**
     * Construtor: inicializa conexão e valida tabela
     */
    public function __construct() {
        $this->db = DatabaseConnection::getInstance()->getConnection();
        if (!$this->table) {
            throw new Exception('Propriedade $table não definida em ' . get_class($this));
        }
    }

    /**
     * Busca registro por ID
     * @param mixed $id
     * @return array|null
     */
    public function find($id) {
        $query = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $result = DatabaseConnection::getInstance()->fetch($query, [$id]);
        return $result ? $this->formatResult($result) : null;
    }

    /**
     * Busca todos os registros de um usuário
     * @param string $userId
     * @return array
     */
    public function findByUser ($userId) {
        $query = "SELECT * FROM {$this->table} WHERE user_id = ?::uuid";
        $results = DatabaseConnection::getInstance()->fetchAll($query, [$userId]);
        return array_map([$this, 'formatResult'], $results);
    }

    /**
     * Busca todos os registros da tabela
     * @param int|null $limit
     * @param int $offset
     * @return array
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
     * Busca registros com filtro simples
     * @param string $column
     * @param string|null $operator
     * @param mixed|null $value
     * @return array
     */
    public function where($column, $operator = null, $value = null) {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        $query = "SELECT * FROM {$this->table} WHERE {$column} {$operator} ?";
        $results = DatabaseConnection::getInstance()->fetchAll($query, [$value]);
        return array_map([$this, 'formatResult'], $results);
    }

    /**
     * Cria um novo registro
     * @param array $data
     * @return mixed|null
     */
    public function create($data) {
        $fields = array_intersect(array_keys($data), $this->fillable);
        $columns = implode(',', $fields);
        $placeholders = implode(',', array_fill(0, count($fields), '?'));
        $values = array_map(fn($f) => $data[$f], $fields);
        $query = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders) RETURNING {$this->primaryKey}";
        $result = DatabaseConnection::getInstance()->fetch($query, $values);
        return $result[$this->primaryKey] ?? null;
    }

    /**
     * Atualiza um registro existente
     * @param mixed $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data) {
        $fields = array_intersect(array_keys($data), $this->fillable);
        $set = implode(', ', array_map(fn($f) => "$f = ?", $fields));
        $values = array_map(fn($f) => $data[$f], $fields);
        $values[] = $id;
        $query = "UPDATE {$this->table} SET $set WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute($values);
    }

    /**
     * Remove um registro
     * @param mixed $id
     * @return bool
     */
    public function delete($id) {
        $query = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$id]);
    }

    /**
     * Remove campos ocultos do resultado
     * @param array $result
     * @return array
     */
    protected function formatResult($result) {
        foreach ($this->hidden as $field) {
            unset($result[$field]);
        }
        return $result;
    }
}
