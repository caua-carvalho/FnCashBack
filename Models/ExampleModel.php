<?php
/**
 * Modelo de Exemplo - Estenda BaseModel para suas tabelas
 */
class ExampleModel extends BaseModel {
    protected $table = 'example_table';
    protected $primaryKey = 'id';
    
    // Colunas que podem ser atribuídas em massa (Mass Assignment)
    protected $fillable = ['name', 'description', 'status'];
    
    // Colunas que não devem ser retornadas
    protected $hidden = ['password', 'secret'];
    
    // Usar timestamps automaticamente
    protected $timestamps = true;

    /**
     * Exemplo de método customizado
     */
    public function getMessage() {
        return 'Bem-vindo ao FnCashBack!';
    }

    /**
     * Exemplo: Buscar registros ativos
     */
    public function getActive() {
        return $this->where('status', 'active');
    }

    /**
     * Exemplo: Query customizada com JOIN
     */
    public function getWithRelations() {
        $sql = "SELECT * FROM {$this->table} et
                LEFT JOIN other_table ot ON et.id = ot.example_id
                WHERE et.status = ?";
        return $this->queryAll($sql, ['active']);
    }
}
