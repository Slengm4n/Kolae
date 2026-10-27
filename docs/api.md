# Kolae API v1 — contrato

Fonte da verdade da API consumida pelo app mobile. **Endpoint novo ou mudança de formato entra aqui antes do código.**

## Base URL

| Ambiente | URL |
| :---- | :---- |
| Produção | `https://<domínio do site>/api/v1` |
| Local (XAMPP) | `http://localhost:8080/kolae/api/v1` |

No emulador Android, `localhost` é o próprio emulador: use o IP da máquina na rede (ex: `http://192.168.0.10:8080/kolae/api/v1`).

## Formato das respostas

Toda resposta é JSON (`Content-Type: application/json; charset=utf-8`), inclusive erros, 404 e falhas internas.

**Sucesso**

```json
{ "success": true, "data": { } }
```

**Erro**

```json
{
  "success": false,
  "error": {
    "message": "Texto legível, pode ser exibido ao usuário.",
    "code": "CODIGO_ESTAVEL",
    "fields": { "email": "Formato de e-mail inválido." }
  }
}
```

- `code` é estável: o app deve decidir o que fazer olhando o `code`, nunca o texto da `message`.
- `fields` só aparece em erros de validação (`VALIDATION_ERROR`), com uma mensagem por campo.

### Códigos de erro comuns

| HTTP | `code` | Quando |
| :---- | :---- | :---- |
| 400 | `INVALID_JSON_BODY` | Corpo não é um objeto JSON válido |
| 401 | `AUTH_MISSING_TOKEN` | Sem cabeçalho `Authorization` |
| 401 | `AUTH_INVALID_FORMAT` | Cabeçalho fora do formato `Bearer <token>` |
| 401 | `AUTH_TOKEN_EXPIRED` | Token expirado: fazer login de novo |
| 401 | `AUTH_SIGNATURE_INVALID` / `AUTH_INVALID_TOKEN` | Token adulterado ou inválido |
| 401 | `AUTH_INVALID_USER` | Usuário do token foi desativado |
| 404 | `NOT_FOUND` | Rota não existe |
| 405 | `METHOD_NOT_ALLOWED` | Rota existe, mas com outro método (ex: GET em vez de POST) |
| 422 | `VALIDATION_ERROR` | Campos inválidos; ver `error.fields` |
| 500 | `INTERNAL_ERROR` | Erro inesperado no servidor |

## Autenticação

Endpoints protegidos exigem o token devolvido pelo login:

```
Authorization: Bearer <token>
```

O token (JWT HS256) vale **8 horas**. Ao receber `AUTH_TOKEN_EXPIRED`, levar o usuário para o login.

## Tipos

- Datas do banco (`created_at` etc.): string `YYYY-MM-DD HH:MM:SS`, **sem fuso definido ainda**: a produção provavelmente devolve UTC e o ambiente local, horário de Brasília. Não usar para lógica no app até isso ser padronizado (pendente, S4 junto com partidas). `expires_at` do login já vem em ISO 8601 com fuso.
- Booleanos do banco (`has_lighting`, `is_covered`, `has_leisure_area`): inteiros `0` / `1`.
- Decimais (`average_price_per_hour`, `latitude`, `longitude`): **string** (ex: `"10.00"`, `"-23.54801710"`). Converter no app.

---

## Endpoints

### `POST /auth/login`

Público. Autentica e devolve o token.

**Body**

```json
{ "email": "joao@exemplo.com", "password": "minhasenha" }
```

**200**

```json
{
  "success": true,
  "data": {
    "token": "eyJ0eXAiOiJKV1Qi...",
    "token_type": "Bearer",
    "expires_at": "2026-10-28T02:44:34-03:00",
    "user": {
      "id": 1,
      "name": "João",
      "email": "joao@exemplo.com",
      "role": "player",
      "avatar_url": "https://.../uploads/avatars/1/avatar_abc.jpg"
    }
  }
}
```

- `role`: `admin` | `owner` | `player`
- `avatar_url`: `null` quando o usuário não tem foto.

**Erros**: `422 VALIDATION_ERROR` (campos `email`, `password`), `401 INVALID_CREDENTIALS` (e-mail/senha errados ou usuário inativo), `400 INVALID_JSON_BODY`, `500 LOGIN_FAILED`.

### `GET /venues`

Público. Lista as quadras que têm coordenadas (para o mapa).

**200**: `data` é uma lista de:

```json
{
  "id": 1,
  "user_id": 1,
  "address_id": 1,
  "name": "Quadra Tácito Zanchetta",
  "average_price_per_hour": "10.00",
  "court_capacity": 20,
  "has_leisure_area": 0,
  "leisure_area_capacity": 0,
  "floor_type": "court",
  "has_lighting": 1,
  "is_covered": 1,
  "status": "available",
  "created_at": "2026-06-12 13:50:03",
  "updated_at": "2026-06-12 13:50:03",
  "deleted_at": null,
  "street": "Rua José Maria Claro",
  "number": "600",
  "cep": "08503-200",
  "complement": "Escola Estadual",
  "neighborhood": "Jardim do Castelo",
  "city": "Ferraz de Vasconcelos",
  "state": "SP",
  "latitude": "-23.54801710",
  "longitude": "-46.36231860",
  "image_path": "venue_6a2c38bb65dcd5.98849634.webp",
  "full_image_url": "https://.../uploads/venues/1/venue_6a2c38bb65dcd5.98849634.webp"
}
```

- `floor_type`: `synthetic` (grama sintética) | `court` (cimento/poliesportivo) | `sand` (areia) | `clay` (saibro) | `grass` (grama natural) | `wood` (madeira/taco) | `null`. O app traduz para exibir.
- `status`: `available` | `unavailable` | `maintenance`.
- `full_image_url`: primeira foto da quadra, ou `null`.
- **Limitação atual**: devolve todas as quadras com coordenadas, sem filtrar por `status`. Filtros e `GET /venues/{id}` chegam na S3.

**Erros**: `500 VENUES_FETCH_FAILED`.

### `GET /sports`

Público. Lista os esportes ativos, em ordem alfabética.

**200**: `data` é uma lista de:

```json
{
  "id": 1,
  "name": "Futebol",
  "status": "active",
  "description": null,
  "icon_path": "fa-futbol",
  "created_at": "2026-06-12 21:09:35",
  "updated_at": "2026-06-12 21:09:35"
}
```

- `icon_path`: classe do Font Awesome (ex: `fa-futbol`). No app, mapear para o ícone equivalente.

**Erros**: `500 SPORTS_FETCH_FAILED`.

### `POST /games`

**Protegido.** Cria uma partida. **Não funciona ainda**: o model grava uma coluna que não existe (`duration_minutes`). Será refeito na S4 junto com participantes.

---

## Planejado

| Semana | Endpoints |
| :---- | :---- |
| S2 | `POST /auth/register`, `GET /me`, avatar, `POST /auth/forgot-password` |
| S3 | `GET /venues` com filtros, `GET /venues/{id}`, `GET /sports` (revisão) |
| S4 | `GET/POST /games`, entrar/sair de partida (`match_participants`) |

Cada um deve ser descrito aqui (body, resposta, erros) antes de ser implementado.
