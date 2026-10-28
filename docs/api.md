# Kolae API v1 — contrato

Fonte da verdade da API consumida pelo app mobile. **Endpoint novo ou mudança de formato entra aqui antes do código**, e o João e o Rafael revisam juntos.

O formato segue o padrão do Laravel, para o Kolae 2.0 (Laravel) herdar o contrato sem mudanças no app. Mudança que quebra o app vira `/api/v2`.

> **Estado:** contrato da S2 revisado e aprovado pelo João e pelo Rafael em 28/10/2026. O formato, os tipos e os endpoints da S2 estão implementados e testados no ambiente local (branch `feat/s2-api`) e vão para a produção na **v1.5.0**. Até lá, a produção ainda responde no formato antigo (`{ "success", "data" }` / `{ "success", "error": { "message", "code", "fields" } }`).

## Base URL

| Ambiente | URL |
| :---- | :---- |
| Produção | `https://kolae.gamer.gd/api/v1`. **Não funciona para o app**: o InfinityFree devolve um desafio JavaScript em vez do JSON para clientes que não são navegador. |
| Local (XAMPP), no próprio PC | `http://localhost:8080/kolae/api/v1` |
| Local, a partir do celular | `http://<IP do notebook>:8080/kolae/api/v1` (ex: `http://192.168.0.172:8080/kolae/api/v1`) |

Até a migração de hospedagem, o app usa o backend local (`http://`, sem HTTPS). O Expo Go aceita; num APK é preciso liberar tráfego HTTP no build. Passo a passo em `docs/setup.md`.

## Formato das respostas

Toda resposta é JSON (`Content-Type: application/json; charset=utf-8`), inclusive erros, 404 e falhas internas.

**Sucesso**: o conteúdo vem em `data`. Quando não há conteúdo, `data` é `null`.

```json
{ "data": { } }
```

**Erro**: `message` sempre; `errors` só em erro de validação (422), com uma **lista** de mensagens por campo; `code` é um extra estável para o app decidir o que fazer.

```json
{
  "message": "Verifique os campos informados.",
  "code": "VALIDATION_ERROR",
  "errors": {
    "email": ["Este e-mail já está cadastrado."],
    "password": ["A senha precisa ter pelo menos 8 caracteres."]
  }
}
```

- Mostrar a primeira mensagem de `errors.<campo>` embaixo do campo; sem `errors`, mostrar `message`.
- Para decidir o fluxo (ex: token expirado → login), olhar o status HTTP e o `code`, nunca o texto.
- **Quando deslogar:** só nos erros de token (`AUTH_MISSING_TOKEN`, `AUTH_INVALID_FORMAT`, `AUTH_TOKEN_EXPIRED`, `AUTH_INVALID_TOKEN`, `AUTH_INVALID_USER`). O `401 INVALID_CREDENTIALS` também é 401, mas só significa e-mail ou senha errados na tela de login.
- As mensagens saem em português. O app já envia `Accept-Language`; responder no idioma do app é uma melhoria futura (sem data).

### Códigos de erro

| HTTP | `code` | Quando |
| :---- | :---- | :---- |
| 400 | `INVALID_JSON_BODY` | Corpo não é um objeto JSON válido |
| 401 | `AUTH_MISSING_TOKEN` | Sem cabeçalho `Authorization` |
| 401 | `AUTH_INVALID_FORMAT` | Cabeçalho fora do formato `Bearer <token>` |
| 401 | `AUTH_TOKEN_EXPIRED` | Token expirado: levar para o login |
| 401 | `AUTH_INVALID_TOKEN` | Token adulterado ou inválido |
| 401 | `AUTH_INVALID_USER` | Usuário do token foi desativado |
| 401 | `INVALID_CREDENTIALS` | Login com e-mail ou senha errados |
| 404 | `NOT_FOUND` | Rota ou recurso não existe |
| 405 | `METHOD_NOT_ALLOWED` | Rota existe, mas com outro método |
| 413 | `FILE_TOO_LARGE` | Upload acima do limite |
| 422 | `VALIDATION_ERROR` | Campos inválidos; ver `errors` |
| 500 | `INTERNAL_ERROR` e outros terminados em `_FAILED` | Erro inesperado no servidor; mostrar `message` e permitir tentar de novo |
| 503 | `DATABASE_UNAVAILABLE` | Banco fora do ar (ex: XAMPP com MySQL desligado) |

## Autenticação

Endpoints marcados como **protegidos** exigem o token devolvido pelo login ou cadastro:

```
Authorization: Bearer <token>
```

O token é um JWT (HS256) e vale **30 dias**. Não existe renovação: ao receber `401 AUTH_TOKEN_EXPIRED`, apagar o token e levar para o login.

## Tipos

- **Datas e horários**: ISO 8601 com fuso, horário de Brasília. Ex: `"2026-10-28T20:00:00-03:00"`.
- **Datas sem horário** (ex: `birthdate`): `"YYYY-MM-DD"`.
- **Dinheiro**: inteiro em **centavos**, campo com sufixo `_cents`. Ex: R$ 10,00 → `1000`.
- **Coordenadas**: número (`-23.5428`), não string.
- **Booleanos**: `true` / `false`.
- **Imagens**: URL completa, ou `null`.
- **Listas paginadas** (a partir da S3): `data` é a lista e `meta` traz a paginação.

```json
{
  "data": [ ],
  "meta": { "current_page": 1, "last_page": 3, "per_page": 20, "total": 47 }
}
```

### Objeto `user`

```json
{
  "id": 1,
  "name": "João",
  "email": "joao@exemplo.com",
  "birthdate": "2005-12-20",
  "role": "player",
  "avatar_url": "https://.../uploads/avatars/1/avatar_abc.jpg",
  "created_at": "2026-04-16T00:58:49-03:00"
}
```

- `role`: `admin` (administra o Kolae), `owner` (responsável por quadra: quem cadastrou CNPJ no site) ou `player`.
- `avatar_url`: `null` quando não há foto.

---

## Endpoints da S2 (v1.5.0)

### `POST /auth/login`

Público.

**Body**: `{ "email": "joao@exemplo.com", "password": "minhasenha" }`

**200**

```json
{
  "data": {
    "token": "eyJ0eXAiOiJKV1Qi...",
    "token_type": "Bearer",
    "expires_at": "2026-11-27T20:00:00-03:00",
    "user": { "...": "objeto user" }
  }
}
```

**Erros**: `422 VALIDATION_ERROR` (`email`, `password`), `401 INVALID_CREDENTIALS` (e-mail ou senha errados, ou conta inativa).

### `POST /auth/register`

Público. Cria a conta (sempre com `role: player`) e já devolve o token, sem precisar de login.

**Body**

```json
{
  "name": "João",
  "email": "joao@exemplo.com",
  "birthdate": "2005-12-20",
  "password": "minhasenha",
  "password_confirmation": "minhasenha"
}
```

| Campo | Regras |
| :---- | :---- |
| `name` | obrigatório, até 255 caracteres |
| `email` | obrigatório, e-mail válido, ainda não cadastrado |
| `birthdate` | obrigatório, `YYYY-MM-DD`, pessoa com **18 anos ou mais** (mesma regra do site) |
| `password` | obrigatório, **mínimo 8 caracteres** (o site ainda não exige mínimo; vai passar a exigir) |
| `password_confirmation` | igual a `password` |

**201**: mesmo `data` do login (`token`, `token_type`, `expires_at`, `user`).

**Erros**: `422 VALIDATION_ERROR` com `errors` nos campos acima.

### `POST /auth/forgot-password`

Público. Envia o e-mail com o link de redefinição. O link abre a página de nova senha **no site**; o app só faz o pedido.

**Body**: `{ "email": "joao@exemplo.com" }`

**200**: `{ "data": null }`, **com a mesma resposta exista ou não o e-mail** (para não revelar quem tem conta). O link vale 1 hora.

**Erros**: `422 VALIDATION_ERROR` (`email` vazio ou inválido).

### `GET /me`

**Protegido.** Dados da pessoa logada. Útil para abrir o app já logado com o token salvo.

**200**: `{ "data": { "...": "objeto user" } }`

### `PATCH /me`

**Protegido.** Edita o nome.

**Body**: `{ "name": "João Pedro" }`

| Campo | Regras |
| :---- | :---- |
| `name` | até 255 caracteres, não vazio |

**A data de nascimento não é editável**, nem no app nem no site: assim ninguém contorna a regra dos 18 anos depois do cadastro. Na tela de dados pessoais, ela aparece só para leitura. Se `birthdate` vier no body, a resposta é `422` com `errors.birthdate` (para o app não achar que salvou).

**200**: `user` atualizado. **Erros**: `422 VALIDATION_ERROR`.

### `POST /me/avatar`

**Protegido.** Envia ou troca a foto de perfil. A foto anterior é apagada.

**Body**: `multipart/form-data` com o arquivo no campo `avatar`. Aceita **JPG, PNG ou GIF de até 10 MB**; o tipo é conferido pelo conteúdo do arquivo, não pela extensão.

**200**: `user` atualizado, com o novo `avatar_url`.

**Erros**: `422 VALIDATION_ERROR` (`avatar` ausente ou não é imagem aceita), `413 FILE_TOO_LARGE`.

### `PUT /me/password`

**Protegido.** Troca a senha. O token atual continua válido.

**Body**

```json
{ "current_password": "senhaatual", "password": "senhanova", "password_confirmation": "senhanova" }
```

**200**: `{ "data": null }`

**Erros**: `422 VALIDATION_ERROR`, incluindo `errors.current_password` quando a senha atual está errada. As regras de `password` são as do cadastro.

---

## Endpoints atuais que mudam depois

### `GET /sports`

Público. Lista os esportes ativos, em ordem alfabética. Na v1.5.0 passa a usar o formato novo e datas ISO 8601.

```json
{ "id": 1, "name": "Futebol", "description": null, "icon_path": "fa-futbol" }
```

- `icon_path`: classe do Font Awesome (ex: `fa-futbol`). No app, mapear para o ícone equivalente.

### `GET /venues`

Público. **Formato legado até a S3**: a v1.5.0 só troca o envelope (`{ "data": [...] }`); os campos continuam como hoje (preço como string `"10.00"`, coordenadas como string, booleanos `0/1`, `floor_type` com as chaves `synthetic | court | sand | clay | grass | wood`). Na S3 o endpoint é refeito com filtros, paginação, status calculado e os tipos acima.

### `POST /games`

**Será removido.** Pelo modelo de produto decidido em 28/10, a partida é agendada pelo dono no site; o app não cria nem entra em partidas. Não usar.

---

## Planejado

| Quando | O quê |
| :---- | :---- |
| S3 | `GET /venues` com busca, filtros (`q`, `sport_id`, `status`, `max_price_cents`, `is_covered`, `has_lighting`, `floor_type`, `lat`/`lng`/`radius_km`, `sort`), paginação e os campos `status` e `status_until`; `GET /venues/{id}` com o endereço em texto e o objeto `owner` (abaixo) |
| S3 | `GET /venues/{id}/schedule?date=` (agenda do dia); status calculado a partir do horário de funcionamento e das partidas agendadas pelo dono (adiantado da S4, D28) |
| ~~S5~~ | ~~Troca de e-mail com código (`POST /me/email`, `POST /me/email/confirm`)~~: fora do escopo da entrega (D28); a tela fica no mock do app |

A regra completa do status, o horário de funcionamento e a agenda estão em [`modelo-status.md`](modelo-status.md) (aprovado em 28/10, D21–D27); o contrato da S3 será escrito aqui a partir dele.

Status da quadra (S3/S4), calculado pela API e nunca pelo app: `available` (aberta e sem partida agora), `in_use` (partida neste horário), `closed` (fora do horário ou fechada pelo dono). `status_until` diz quando o status atual muda.

Dono da quadra (S3, combinado em 28/10): um objeto `owner` no lugar do antigo `owner_whatsapp` solto, para a tela de informações da quadra. O `whatsapp` exige uma coluna nova em `users` e um campo no site.

```json
"owner": {
  "name": "Rafael",
  "avatar_url": "https://.../uploads/avatars/2/avatar_abc.jpg",
  "member_since": "2026-05-22T14:57:07-03:00",
  "whatsapp": "5511999999999"
}
```

Avaliações e favoritos não existem no backend; no app ficam como vitrine.

Cada item é descrito aqui (body, resposta, erros) antes de ser implementado.
