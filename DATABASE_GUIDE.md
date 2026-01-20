# 🗄️ Guia de Banco de Dados - FnCash Backend

## Visão Geral

O sistema de banco de dados foi redesenhado para ser mais robusto, seguro e inteligente, seguindo padrões de desenvolvimento profissional.

## 📋 Arquitetura

### 1. **DatabaseConfig** (`config/Database.php`)
- Centraliza todas as configurações de conexão
- Suporta múltiplos ambientes (development, production, testing)
- Carrega variáveis do `.env`
- Padrão Singleton

### 2. **DatabaseConnection** (`config/DatabaseConnection.php`)
- Gerencia a conexão PDO
- Protege contra SQL Injection
- Suporta transações
- Padrão Singleton (uma única conexão)

### 3. **BaseModel** (`models/BaseModel.php`)
- Classe base para todos os modelos
- Métodos CRUD prontos para uso
- ORM básico integrado
- Suporte a timestamps automáticos

## ⚙️ Configuração Inicial

### 1. Criar arquivo `.env.local`

```bash
cp .env.example .env.local
```

### 2. Preencher as variáveis de ambiente

```env
APP_ENV=development
DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASSWORD=sua_senha
DB_NAME=fncash_dev
JWT_SECRET=sua_chave_secreta
```

## 💡 Como Usar

### Criar um Novo Modelo

```php
<?php
class User extends BaseModel {
    // Define a tabela do banco de dados
    protected $table = 'users';
    
    // Chave primária (padrão é 'id')
    protected $primaryKey = 'id';
    
    // Colunas que podem ser atribuídas em massa
    protected $fillable = ['name', 'email', 'password'];
    
    // Colunas que nunca devem ser retornadas
    protected $hidden = ['password'];
    
    // Usar created_at e updated_at automaticamente
    protected $timestamps = true;
}
```

### Operações CRUD

#### **CREATE - Criar um registro**

```php
$user = new User();
$result = $user->create([
    'name' => 'João Silva',
    'email' => 'joao@example.com',
    'password' => password_hash('senha123', PASSWORD_BCRYPT)
]);

if ($result['success']) {
    $newUserId = $result['id'];
}
```

#### **READ - Buscar registros**

```php
$user = new User();

// Buscar todos
$allUsers = $user->all();

// Buscar com limite
$firstTen = $user->all(limit: 10, offset: 0);

// Buscar por ID
$user = $user->find(1);

// Buscar o primeiro que atende à condição
$user = $user->first('email', 'joao@example.com');

// Buscar com filtro (WHERE)
$activeUsers = $user->where('status', '=', 'active');

// Buscar com operadores
$recentUsers = $user->where('created_at', '>', '2024-01-01');
```

#### **UPDATE - Atualizar um registro**

```php
$user = new User();
$result = $user->update(1, [
    'name' => 'João Santos',
    'email' => 'joao.santos@example.com'
]);

if ($result['success']) {
    echo "Atualizadas " . $result['affected_rows'] . " linhas";
}
```

#### **DELETE - Deletar um registro**

```php
$user = new User();
$result = $user->delete(1);

if ($result['success']) {
    echo "Deletado com sucesso";
}
```

### Queries Customizadas

```php
$user = new User();

// Query customizada - todos os resultados
$activeEmails = $user->queryAll(
    "SELECT email FROM users WHERE status = ? AND created_at > ?",
    ['active', '2024-01-01']
);

// Query customizada - um resultado
$user = $user->queryOne(
    "SELECT * FROM users WHERE email = ?",
    ['joao@example.com']
);

// Query com contagem
$count = $user->count('status', 'active');
```

### Transações

```php
$db = DatabaseConnection::getInstance();

// Usar callback
$db->transaction(function($db) {
    $user = new User();
    $user->create(['name' => 'João', 'email' => 'joao@example.com']);
    
    $transaction = new Transaction();
    $transaction->create(['user_id' => $db->lastInsertId(), 'amount' => 100]);
});

// Ou manual
try {
    $db->beginTransaction();
    
    // suas operações
    
    $db->commit();
} catch (Exception $e) {
    $db->rollback();
    throw $e;
}
```

## 🔒 Segurança

### SQL Injection Protection

Todos os parâmetros são preparados automaticamente:

```php
// ✅ SEGURO - Automaticamente protegido
$user = new User();
$results = $user->where('email', 'joao@example.com');

// ✅ SEGURO - Prepared statements
$user->queryAll(
    "SELECT * FROM users WHERE id = ? AND status = ?",
    [$id, $status]
);

// ❌ NUNCA faça assim
$user->queryAll("SELECT * FROM users WHERE id = $id"); // SQL Injection!
```

### Variáveis de Ambiente

- Nunca comite suas credenciais no git
- Use `.env.local` para configurações sensíveis
- `.env` serve como template de exemplo

```bash
# .gitignore deve conter:
.env.local
.env
```

## 📊 Exemplo Completo - Sistema de Transações

```php
<?php
// Modelo de Usuário
class User extends BaseModel {
    protected $table = 'users';
    protected $fillable = ['name', 'email', 'balance'];
    protected $hidden = ['password'];
    protected $timestamps = true;
}

// Modelo de Transação
class Transaction extends BaseModel {
    protected $table = 'transactions';
    protected $fillable = ['user_id', 'type', 'amount', 'category', 'description'];
    protected $timestamps = true;
}

// No seu Controller
class TransactionController {
    
    public function create() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $db = DatabaseConnection::getInstance();
        
        try {
            $result = $db->transaction(function($db) use ($data) {
                // Verificar se usuário existe
                $user = new User();
                $userRecord = $user->find($data['user_id']);
                
                if (!$userRecord) {
                    throw new Exception('Usuário não encontrado');
                }
                
                // Criar transação
                $transaction = new Transaction();
                $transResult = $transaction->create([
                    'user_id' => $data['user_id'],
                    'type' => $data['type'],
                    'amount' => $data['amount'],
                    'category' => $data['category'],
                    'description' => $data['description']
                ]);
                
                if (!$transResult['success']) {
                    throw new Exception('Erro ao criar transação');
                }
                
                // Atualizar saldo do usuário
                $newBalance = $userRecord['balance'] + 
                              ($data['type'] === 'income' ? $data['amount'] : -$data['amount']);
                
                $updateResult = $user->update($data['user_id'], [
                    'balance' => $newBalance
                ]);
                
                if (!$updateResult['success']) {
                    throw new Exception('Erro ao atualizar saldo');
                }
                
                return ['success' => true, 'transaction_id' => $transResult['id']];
            });
            
            http_response_code(201);
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
```

## 🐛 Debug

### Ver última query executada

```php
$db = DatabaseConnection::getInstance();
$user = new User();
$user->where('status', 'active');
echo $db->getLastQuery(); // SELECT * FROM users WHERE status = ?
```

### Logs

Todas as operações são logadas automaticamente. Verifique o arquivo de logs em `./logs`.

## 🚀 Boas Práticas

1. ✅ Sempre use prepared statements (automático neste sistema)
2. ✅ Filtre dados com `$fillable` para evitar atribuição em massa perigosa
3. ✅ Use transações para operações que dependem uma da outra
4. ✅ Use `$hidden` para dados sensíveis
5. ✅ Estenda `BaseModel` para cada tabela do seu banco
6. ✅ Valide dados antes de salvar no banco
7. ✅ Use `$timestamps` para rastrear criação/edição

## ❌ Evite

1. ❌ Concatenar variáveis em queries (SQL Injection)
2. ❌ Retornar dados sensíveis em APIs
3. ❌ Operações longas sem transações
4. ❌ Hardcoded de credenciais do banco
5. ❌ Ignorar erros de conexão

## 📚 Próximos Passos

- Implementar validação de dados
- Criar sistema de migrations
- Adicionar cache de queries
- Implementar soft deletes
- Criar relacionamentos entre modelos (HasMany, BelongsTo)
