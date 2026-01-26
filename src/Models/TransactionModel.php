
<?php
require_once APP_ROOT . '/Models/BaseModel.php';

/**
 * Model TransactionModel
 * Responsável por mapear a tabela 'transactions' e fornecer métodos de acesso aos dados de transações.
 * Herdado de BaseModel, já possui métodos CRUD genéricos.
 */
class TransactionModel extends BaseModel {
    // Nome da tabela no banco
    protected $table = 'transactions';
    // Nome da chave primária
    protected $primaryKey = 'id';
    // Campos permitidos para inserção/atualização em massa
    protected $fillable = [
        'user_id', 'amount', 'category', 'type', 'description', 'date',
        'confidence', 'created_at', 'updated_at', 'deleted_at'
    ];
    // Campos ocultos ao retornar dados
    protected $hidden = [];
    // Controla timestamps automáticos
    protected $timestamps = true;
}
