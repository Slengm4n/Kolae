# Kolae API v1 — contrato

Fonte da verdade da API consumida pelo app mobile. **Endpoint novo ou mudança de formato entra aqui antes do código**, e o João e o Rafael revisam juntos.

O formato segue o padrão do Laravel, para o Kolae 2.0 (Laravel) herdar o contrato sem mudanças no app. Mudança que quebra o app vira `/api/v2`.

> **Estado:**
> - **S2 (v1.5.0):** revisado e aprovado pelo João e pelo Rafael em 28/10/2026, implementado e em produção.
> - **S3 (v1.6.0, até 08/11):** revisado pelo Rafael em 28/10/2026; ajustes da revisão já aplicados (D29–D33). Escrito a partir do `docs/modelo-status.md` (D21–D27). Até ser implementado, `GET /venues` continua no formato legado e o app usa o mock **com os campos daqui**.

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

## Endpoints da S3 (v1.6.0)

Todos **públicos** (não precisam de token): o mapa e o detalhe da quadra abrem sem login.

### Status da quadra

Calculado pela API a cada requisição, no horário de Brasília. **O app nunca calcula status** (D03): só exibe `status` e `status_until`. Regra completa em [`modelo-status.md`](modelo-status.md), seção 3.

| `status` | Texto no app | Quando |
| :---- | :---- | :---- |
| `available` | Disponível | Dentro do horário de funcionamento e sem partida agora |
| `in_use` | Em uso | Há uma partida agendada pelo dono neste horário |
| `closed` | Fechada | Fora do horário, dia sem funcionamento, quadra sem horário cadastrado ou fechada pelo dono |

- `status_until`: quando o status atual muda (fim da partida, fechamento ou próxima abertura), em ISO 8601. **Pode ser `null`**: quadra fechada pelo dono, sem horário cadastrado ou sem abertura nos próximos 7 dias.
- O status muda com o relógio. Para manter a tela atualizada, buscar de novo ao voltar para ela ou quando `status_until` passar.

### Objeto `venue` (resumo, usado na lista)

```json
{
  "id": 12,
  "name": "Arena Centro",
  "status": "available",
  "status_until": "2026-11-03T19:00:00-03:00",
  "price_per_hour_cents": 12000,
  "floor_type": "synthetic",
  "is_covered": true,
  "has_lighting": true,
  "has_leisure_area": true,
  "court_capacity": 14,
  "cover_image_url": "https://.../uploads/venues/12/venue_abc.jpg",
  "sports": [
    { "id": 1, "name": "Futebol", "icon_path": "fa-futbol" }
  ],
  "neighborhood": "Centro",
  "city": "Ferraz de Vasconcelos",
  "latitude": -23.5428,
  "longitude": -46.3686,
  "distance_km": 1.8
}
```

| Campo | Observação |
| :---- | :---- |
| `price_per_hour_cents` | Preço médio por hora, em centavos. **`0` = grátis; `null` = o dono não informou** (mostrar "Preço não informado") (D31) |
| `floor_type` | `synthetic`, `court`, `sand`, `clay`, `grass` ou `wood` (D15); o app traduz |
| `has_leisure_area` | Tem área de lazer (D32) |
| `court_capacity` | Pessoas na quadra; `null` se não informado |
| `cover_image_url` | Primeira foto da quadra; `null` se não há foto |
| `sports` | Esportes que a quadra atende (lista, pode ser vazia até o dono marcar) (D29) |
| `latitude`, `longitude` | `null` quando o endereço não foi geolocalizado: a quadra aparece na lista, mas não no mapa |
| `distance_km` | Distância em km (uma casa decimal) até `lat`/`lng` enviados; `null` quando a busca não manda posição |

### `GET /venues`

Lista paginada das quadras (as excluídas pelo dono não aparecem; as fechadas aparecem com `status: closed`).

**Parâmetros** (todos opcionais, na query string):

| Parâmetro | Exemplo | Regra |
| :---- | :---- | :---- |
| `q` | `arena` | Busca no nome da quadra, no bairro e na cidade (sem diferenciar maiúsculas) |
| `sport_id` | `1` | Só quadras que atendem esse esporte |
| `status` | `available` | `available`, `in_use` ou `closed` |
| `max_price_cents` | `15000` | Preço por hora até esse valor, **incluindo as grátis** (`0`). `max_price_cents=0` = só as grátis. Quadras com preço não informado (`null`) ficam de fora quando este filtro é usado |
| `is_covered` | `true` | `true` ou `false` |
| `has_lighting` | `true` | `true` ou `false` |
| `has_leisure_area` | `true` | `true` ou `false` (D32) |
| `floor_type` | `synthetic` | Uma das chaves de `floor_type` |
| `lat`, `lng` | `-23.54`, `-46.37` | Posição de quem busca. Os dois juntos. Preenche `distance_km` |
| `radius_km` | `10` | Só quadras até essa distância. Exige `lat`/`lng`. De `1` a `50` |
| `sort` | `distance` | `name` (padrão), `price` (mais barata primeiro, grátis no começo; sem preço informado vão para o fim) ou `distance` (exige `lat`/`lng`; sem coordenadas vão para o fim). Não há ordenação por avaliação (D33) |
| `page` | `2` | Página, a partir de `1` |
| `per_page` | `20` | Itens por página, de `1` a `100` (padrão `20`). Para o mapa, usar `100` com `radius_km` |

**200**

```json
{
  "data": [ { "...": "objeto venue (resumo)" } ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 20, "total": 3 }
}
```

Página além da última: `data` vazio, com o `meta` normal.

**Erros**: `422 VALIDATION_ERROR` com `errors` no parâmetro inválido. Ex: `status=aberta`, `radius_km` sem `lat`/`lng`, `sort=distance` sem posição, `per_page=500`.

### `GET /venues/{id}`

Detalhe de uma quadra: tudo do resumo, mais:

```json
{
  "data": {
    "id": 12,
    "name": "Arena Centro",
    "status": "in_use",
    "status_until": "2026-11-03T21:00:00-03:00",
    "...": "demais campos do resumo (sem distance_km)",
    "description": "Quadra society com grama sintética nova, vestiário e estacionamento.",
    "leisure_area_capacity": 30,
    "images": [
      "https://.../uploads/venues/12/venue_abc.jpg",
      "https://.../uploads/venues/12/venue_def.jpg"
    ],
    "address": {
      "street": "Rua Exemplo",
      "number": "100",
      "complement": null,
      "neighborhood": "Centro",
      "city": "Ferraz de Vasconcelos",
      "state": "SP",
      "cep": "08500-000",
      "full": "Rua Exemplo, 100 - Centro, Ferraz de Vasconcelos - SP"
    },
    "opening_hours": [
      { "weekday": 1, "opens_at": "08:00", "closes_at": "22:00" },
      { "weekday": 6, "opens_at": "09:00", "closes_at": "18:00" }
    ],
    "owner": {
      "name": "Rafael",
      "avatar_url": "https://.../uploads/avatars/2/avatar_abc.jpg",
      "member_since": "2026-05-22T14:57:07-03:00",
      "whatsapp": "5511999999999"
    }
  }
}
```

| Campo | Observação |
| :---- | :---- |
| `distance_km` | Não vem no detalhe: o app calcula com a posição da pessoa e as coordenadas da quadra (D33) |
| `description` | Texto livre do dono para a seção "Sobre a quadra", até 1000 caracteres. `null` se não preenchido: esconder a seção (D30) |
| `leisure_area_capacity` | Pessoas na área de lazer; `null` se não informado ou sem área de lazer |
| `images` | Todas as fotos, a capa primeiro. Lista vazia se não há foto |
| `address.full` | Endereço pronto para exibir ou para abrir no app de mapas |
| `opening_hours` | Só os dias em que abre. `weekday`: `0` = domingo ... `6` = sábado. Lista vazia = horário não cadastrado (D23): mostrar "Horário não informado" |
| `owner.whatsapp` | Só dígitos, com DDI e DDD. **Pode ser `null`: nesse caso, esconder o botão "Falar com o dono"** (D24). Com valor, abrir `https://wa.me/<whatsapp>` |
| `owner.avatar_url` | `null` quando o dono não tem foto |

**Erros**: `404 NOT_FOUND` (quadra não existe ou foi excluída).

### `GET /venues/{id}/schedule?date=YYYY-MM-DD`

Agenda de um dia: o horário de funcionamento dividido em faixas livres e ocupadas. `date` é opcional (padrão: hoje, no horário de Brasília).

**200**

```json
{
  "data": {
    "date": "2026-11-03",
    "opens_at": "08:00",
    "closes_at": "22:00",
    "slots": [
      { "start": "2026-11-03T08:00:00-03:00", "end": "2026-11-03T19:00:00-03:00", "status": "available" },
      { "start": "2026-11-03T19:00:00-03:00", "end": "2026-11-03T21:00:00-03:00", "status": "in_use" },
      { "start": "2026-11-03T21:00:00-03:00", "end": "2026-11-03T22:00:00-03:00", "status": "available" }
    ]
  }
}
```

- `slots[].status`: `available` (livre) ou `in_use` (partida agendada). Partidas em sequência viram uma faixa só.
- Dia em que a quadra não abre, quadra sem horário cadastrado ou fechada pelo dono: `opens_at` e `closes_at` = `null` e `slots` = `[]`.
- As faixas que já passaram continuam na lista (o app pode escurecê-las).
- Quem alugou o horário **não** aparece: não é informação pública.

**Erros**: `404 NOT_FOUND` (quadra), `422 VALIDATION_ERROR` (`errors.date`: data inválida ou fora do formato `YYYY-MM-DD`).

### Decisões da revisão (28/10/2026)

| Decisão | Resumo |
| :---- | :---- |
| D29 | Esportes por quadra (`venue_sports`), aprovado. Até o dono marcar, `sports` vem vazio e a quadra não aparece no filtro `sport_id`: **os dados de demonstração precisam ter os esportes marcados** |
| D30 | `description` opcional da quadra, para a seção "Sobre a quadra" |
| D31 | Preço `0` = grátis, `null` = não informado; `max_price_cents` inclui as grátis |
| D32 | `has_leisure_area` no resumo e como filtro |
| D33 | Resolvido no app, sem mudar o contrato: a seção "Mais avaliadas" vira "Disponíveis agora" (`status=available`), e a distância no detalhe é calculada pelo app |

---

## Endpoints atuais que mudam

### `GET /sports`

Público. Lista os esportes ativos, em ordem alfabética. Sem mudança na S3.

```json
{ "id": 1, "name": "Futebol", "description": null, "icon_path": "fa-futbol" }
```

- `icon_path`: classe do Font Awesome (ex: `fa-futbol`). No app, mapear para o ícone equivalente.

### `GET /venues` (formato legado)

Até a implementação da S3, responde no formato antigo: lista sem paginação, preço como string `"10.00"`, coordenadas como string, booleanos `0/1`. Na v1.6.0 é substituído pelo formato descrito acima (**mudança que quebra quem usa o legado**; o app usa mock até lá, então não há impacto).

### `POST /games`

**Será removido na v1.6.0.** Pelo modelo de produto (D02), a partida é agendada pelo dono no site; o app não cria nem entra em partidas. Não usar.

---

## Fora do escopo da entrega

- **Troca de e-mail com código** (`POST /me/email`, `POST /me/email/confirm`): cortada com o prazo novo (D28); a tela fica no mock do app.
- **Avaliações, favoritos, chat e duas etapas**: vitrine no app, sem backend (D04).

Cada endpoint novo é descrito aqui (parâmetros, resposta, erros) antes de ser implementado.
