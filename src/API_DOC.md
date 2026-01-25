# Documentação FnCashBack API

## Rotas

### Pública
- **GET /**
  - Retorna a página inicial (HTML).

### Protegida (JWT)
- **GET /api/protected**
  - Requer header: `Authorization: Bearer <token>`
  - Retorna JSON com dados do usuário autenticado.

## Autenticação JWT
- O token JWT deve ser enviado no header `Authorization`.
- O backend valida assinatura e expiração do token.
- Exemplo de header:
  ```
  Authorization: Bearer SEU_TOKEN_JWT
  ```

## CORS
- O backend aceita requisições de qualquer origem (`*`).
- Métodos permitidos: GET, POST, PUT, DELETE, OPTIONS.
- Headers permitidos: Content-Type, Authorization.

## Integração com Expo (React Native)
- Use fetch/axios normalmente, incluindo o header Authorization.
- Exemplo:
  ```js
  fetch('https://SEU_BACKEND/api/protected', {
    headers: { Authorization: 'Bearer SEU_TOKEN_JWT' }
  })
  ```

## Observações
- Altere a chave secreta do JWT em `index.php` para produção.
- Adicione novas rotas seguindo o padrão do arquivo `index.php`.
