<?php
require_once APP_ROOT . '/Models/BaseModel.php';

/**
 * Model UserModel
 * Responsável por mapear a tabela 'users' e fornecer métodos de acesso aos dados de usuários.
 * Herdado de BaseModel, já possui métodos CRUD genéricos.
 */
class UserModel extends BaseModel {
    // Nome da tabela no banco
    protected $table = 'users';
    // Nome da chave primária
    protected $primaryKey = 'id';
    // Campos permitidos para inserção/atualização em massa
    protected $fillable = [
        'email', 'password_hash', 'name'
    ];
    // Campos ocultos ao retornar dados
    protected $hidden = [
        'password_hash'
    ];
    // Controla timestamps automáticos
    protected $timestamps = true;
}
