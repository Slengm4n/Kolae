# Kolae API v1 — contrato

Fonte da verdade da API consumida pelo app mobile. **Endpoint novo ou mudança de formato entra aqui antes do código**, e o João e o Rafael revisam juntos.

O formato segue o padrão do Laravel, para o Kolae 2.0 (Laravel) herdar o contrato sem mudanças no app. Mudança que quebra o app vira `/api/v2`.

> **Estado:**
> - **S2 (v1.5.0):** revisado e aprovado pelo João e pelo Rafael em 28/10/2026, implementado e em produção.
> - **S3 (v1.6.0):** revisado pelo Rafael em 28/10/2026 (D29–D33), em produção desde 28/10 e lançado na **v1.6.0** (02/11/2026).
> - **Depois da S3 (v1.6.0):** `429 TOO_MANY_ATTEMPTS` no login (D45), limite de envios no `forgot-password` (D52), fotos na ordem do dono e otimizadas (D46, D48) e `email_verified` no objeto `user` (D58). Nenhuma mudança quebrou o app.
> - **Kolae Premium (v1.7.0, D61–D64):** aprovado em 05/11/2026. Anúncios (`ad`, `GET /ads`), registro de cliques, pedidos de horário e notificações do jogador; só acréscimos, ver a seção [Kolae Premium](#kolae-premium-v170-d61d64).
> - **Chat (v1.7.0, D66):** aprovado em 09/11/2026. Conversa entre a pessoa e o responsável pela quadra (o dono responde pelo site); só acréscimos, ver a seção [Chat](#chat-v170-d66).

## Base URL

| Ambiente | URL |
| :---- | :---- |
| **Produção** | **`https://kolae.online/api/v1`** (HostGator, D41). É a que o app usa, inclusive pelo 4G e na apresentação |
| Local (XAMPP), no próprio PC | `http://localhost:8080/kolae/api/v1` no notebook; `http://localhost/kolae/api/v1` no PC de casa (porta 80) |
| Local, a partir do celular | `http://<IP do notebook>:8080/kolae/api/v1` (ex: `http://192.168.0.172:8080/kolae/api/v1`), só para testar mudanças antes do deploy |

A produção é `https://`, então o APK não precisa liberar tráfego HTTP para ela. O backend local é `http://`: para testar o app contra o notebook, o Expo Go aceita; num APK é preciso liberar HTTP no build. Passo a passo em `docs/setup.md`.

## Páginas do site abertas pelo app

Não são endpoints da API: são páginas HTML do site, para o app abrir no navegador ou numa WebView (D42).

| Página | URL de produção |
| :---- | :---- |
| Termos de Uso | `https://kolae.online/termos` |
| Política de Privacidade | `https://kolae.online/privacidade` |
| Esqueci a senha (site) | `https://kolae.online/forgot-password` |
| Página pública da quadra (D49) | `https://kolae.online/quadra/{id}` (o mesmo `id` da API). Serve para um botão "Compartilhar" no detalhe da quadra: o link abre no navegador de quem recebe e mostra a prévia com foto |

O texto é só em português. Para abrir com título e menu em outro idioma, acrescente `?lang=` (ex: `?lang=en-us`).

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
| 429 | `TOO_MANY_ATTEMPTS` | Muitos logins errados (D45). O cabeçalho `Retry-After` diz em quantos segundos tentar de novo; `message` já traz os minutos |
| 403 | `EMAIL_NOT_VERIFIED` | Ação que pede e-mail confirmado (D58), como pedir horário (D64) |
| 403 | `OWN_VENUE` | O dono tentou pedir horário na própria quadra (D64) |
| 404 | `NOT_FOUND` | Rota ou recurso não existe |
| 405 | `METHOD_NOT_ALLOWED` | Rota existe, mas com outro método |
| 413 | `FILE_TOO_LARGE` | Upload acima do limite |
| 409 | `REQUEST_LIMIT` | Pedidos de horário pendentes demais (D64) |
| 409 | `CANNOT_CANCEL` | Pedido fora do prazo de cancelamento ou já encerrado (D64) |
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
  "email_verified": false,
  "birthdate": "2005-12-20",
  "role": "player",
  "avatar_url": "https://.../uploads/avatars/1/avatar_abc.jpg",
  "created_at": "2026-04-16T00:58:49-03:00"
}
```

- `role`: `admin` (administra o Kolae), `owner` (responsável por quadra: quem cadastrou CNPJ no site) ou `player`.
- `avatar_url`: `null` quando não há foto ou o arquivo não existe no servidor.
- `email_verified` (D58): `true` depois que a pessoa abre o link do e-mail de confirmação, enviado no cadastro (site e `POST /auth/register`). É só informativo: nada fica bloqueado sem confirmar, e o app pode ignorar o campo ou mostrar um aviso. O reenvio do link é pelo painel do site.

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

**Erros**: `422 VALIDATION_ERROR` (`email`, `password`), `401 INVALID_CREDENTIALS` (e-mail ou senha errados, ou conta inativa), `429 TOO_MANY_ATTEMPTS` (5 erros seguidos na mesma conta em 15 minutos, D45: mostrar `message` e não deslogar nada).

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
  "distance_km": 1.8,
  "ad": null
}
```

| Campo | Observação |
| :---- | :---- |
| `price_per_hour_cents` | Preço médio por hora, em centavos. **`0` = grátis; `null` = o dono não informou** (mostrar "Preço não informado") (D31) |
| `floor_type` | `synthetic`, `court`, `sand`, `clay`, `grass` ou `wood` (D15); o app traduz |
| `has_leisure_area` | Tem área de lazer (D32) |
| `court_capacity` | Pessoas na quadra; `null` se não informado |
| `cover_image_url` | Capa da quadra: a primeira foto na ordem escolhida pelo dono na aba Fotos (D46); `null` se não há foto ou o arquivo não existe no servidor (o app mostra a imagem padrão) |
| `sports` | Esportes que a quadra atende (lista, pode ser vazia até o dono marcar) (D29) |
| `latitude`, `longitude` | `null` quando o endereço não foi geolocalizado: a quadra aparece na lista, mas não no mapa |
| `distance_km` | Distância em km (uma casa decimal) até `lat`/`lng` enviados; `null` quando a busca não manda posição |
| `ad` | Anúncio no ar da quadra (D63, a partir da v1.7.0) ou `null`; sempre presente. Formato na seção [Anúncio da quadra](#anúncio-da-quadra-ad). Não vem dentro do `venue` do `GET /ads` |

### `GET /venues`

Lista paginada das quadras. As desativadas pelo dono no site não aparecem (D34); as fechadas aparecem com `status: closed`.

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
    },
    "socials": {
      "instagram": "arenadoxuxa",
      "tiktok": null,
      "facebook_url": "https://facebook.com/arenadoxuxa",
      "website_url": null
    }
  }
}
```

| Campo | Observação |
| :---- | :---- |
| `distance_km` | Não vem no detalhe: o app calcula com a posição da pessoa e as coordenadas da quadra (D33) |
| `description` | Texto livre do dono para a seção "Sobre a quadra", até 1000 caracteres. `null` se não preenchido: esconder a seção (D30) |
| `leisure_area_capacity` | Pessoas na área de lazer; `null` se não informado ou sem área de lazer |
| `images` | Todas as fotos (até 10), na ordem escolhida pelo dono, a capa primeiro (D46). Fotos novas chegam em JPEG de até 1600 px (D48). Fotos cujo arquivo não existe no servidor ficam de fora. Lista vazia se não há foto |
| `address.full` | Endereço pronto para exibir ou para abrir no app de mapas |
| `opening_hours` | Só os dias em que abre. `weekday`: `0` = domingo ... `6` = sábado. Lista vazia = horário não cadastrado (D23): mostrar "Horário não informado" |
| `owner.whatsapp` | Só dígitos, com DDI e DDD. **Pode ser `null`: nesse caso, esconder o botão "Falar com o dono"** (D24). Com valor, abrir `https://wa.me/<whatsapp>` |
| `owner.avatar_url` | `null` quando o dono não tem foto ou o arquivo não existe no servidor |
| `socials` | Redes sociais da quadra (D60, a partir da v1.7.0). **Sempre presente**, com `null` em cada rede não informada. Com as quatro `null`, esconder a seção. Não vem no resumo de `GET /venues` |
| `socials.instagram` | Só o usuário, sem `@` (letras, números, `.` e `_`, até 30). Abrir `https://instagram.com/<instagram>`. `null` = sem ícone |
| `socials.tiktok` | Só o usuário, sem `@` (letras, números, `.` e `_`, até 30). Abrir `https://www.tiktok.com/@<tiktok>`. `null` = sem ícone |
| `socials.facebook_url` | Endereço completo, sempre `https://` e de `facebook.com` ou `fb.com`. Abrir como está. `null` = sem ícone |
| `socials.website_url` | Endereço completo, sempre `https://`. Abrir como está. `null` = sem ícone |
| `ad` | Anúncio ativo da quadra (D63, a partir da v1.7.0) ou `null`. Formato na seção [Kolae Premium](#kolae-premium-v170-d61d64). Também vem em cada item do `GET /venues` |

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

## Kolae Premium (v1.7.0, D61–D64)

Aprovado em 05/11/2026 (ideia do Rafael). Proposta completa, regras e telas do site em
[`proposta-premium.md`](proposta-premium.md). Tudo abaixo é **acréscimo**: nada muda no que o app
já usa. O app pode começar com mock a partir daqui.

### Anúncio da quadra (`ad`)

`GET /venues` (cada item) e `GET /venues/{id}` ganham o campo **`ad`**, sempre presente: o
anúncio ativo da quadra ou `null`.

```json
"ad": {
  "id": 7,
  "title": "Terça com 20% off",
  "body": "Reserve pelo WhatsApp e ganhe desconto às terças.",
  "image_url": "https://kolae.online/uploads/ads/7/ad_abc.jpg",
  "ends_on": "2026-11-30"
}
```

| Campo | Regra |
| :---- | :---- |
| `title` | Até 60 caracteres |
| `body` | Até 140 caracteres |
| `image_url` | Foto do anúncio; sem foto, a capa da quadra; `null` se a quadra também não tiver foto |
| `ends_on` | Último dia do anúncio (`YYYY-MM-DD`) |

Mostrar sempre com a etiqueta **"Patrocinado"**. Só vem com a quadra Premium, o anúncio ativo
e dentro do período (D63). Quadra em manutenção continua com o anúncio.

**Números do anúncio (o app que envia):** quando o anúncio aparecer na tela (item do carrossel
"Destaques" ou faixa no detalhe), envie `ad_view`; quando a pessoa tocar nele, envie `ad_click`,
os dois pelo [`POST /venues/{id}/events`](#post-venuesidevents) com o `id` da quadra. A API não
conta sozinha ao responder o `GET /ads` nem o `ad` do detalhe. Pode enviar a cada vez que aparecer:
o servidor ignora a repetição do mesmo aparelho (30 minutos para `ad_view`, 1 minuto para
`ad_click`) e o que chegar sem anúncio no ar.

### `GET /ads`

Público. Até 5 anúncios ativos para o carrossel "Destaques", em ordem sorteada a cada dia.

```json
{
  "data": [
    {
      "id": 7,
      "title": "Terça com 20% off",
      "body": "Reserve pelo WhatsApp e ganhe desconto às terças.",
      "image_url": "https://kolae.online/uploads/ads/7/ad_abc.jpg",
      "ends_on": "2026-11-30",
      "venue": { "...": "mesmo formato do objeto venue da lista, sem ad e sem distance_km" }
    }
  ]
}
```

- Lista vazia (`"data": []`) quando não há anúncio: esconder o carrossel.
- A ordem é sorteada uma vez por dia (horário de Brasília) e fica igual o dia todo; o site usa o
  mesmo sorteio nos destaques da home e da `/quadras` (até 3).
- Sem paginação e sem filtros. Tocar no item abre o detalhe da quadra (`venue.id`).

### `POST /venues/{id}/events`

Público (o token é opcional; com token do dono da quadra, não é contado). Registra um clique para
as estatísticas do dono (D62). A visualização (`view`) **não** precisa ser enviada: o próprio
`GET /venues/{id}` já conta.

```json
{ "type": "whatsapp", "source": "app" }
```

- `type`: `whatsapp`, `instagram`, `tiktok`, `facebook`, `website`, `share`, `ad_view`, `ad_click`.
  `ad_view` e `ad_click` só contam se a quadra tiver anúncio no ar (a resposta é `204` do mesmo jeito).
- `source`: `app` (padrão, para o app), `site` (usado pela página pública da quadra) ou `qr`.
- Resposta: **`204`** sem corpo. Erros: `404 NOT_FOUND` (quadra inexistente ou desativada),
  `422 VALIDATION_ERROR` (`type` inválido), `429 TOO_MANY_ATTEMPTS` (cliques demais do mesmo aparelho).
- Enviar sem esperar a resposta e **nunca** travar a abertura do WhatsApp ou da rede por causa dela.

### `POST /venues/{id}/requests`

Jogador logado. Pede um horário na quadra (D64). Não bloqueia a agenda até o dono aprovar.

```json
{
  "start_time": "2026-11-12T19:00:00-03:00",
  "end_time": "2026-11-12T20:00:00-03:00",
  "sport_id": 1,
  "message": "Somos 10, futebol society"
}
```

- `sport_id` e `message` (até 200) são opcionais.
- Resposta: **`201`** com o objeto `request` (abaixo).
- Erros:
  - `422 VALIDATION_ERROR`, com `errors` por campo: horário no passado ou a mais de 30 dias, fora
    do funcionamento, fora dos blocos de 30 minutos, duração fora de 30 min a 4 h, horário já ocupado,
    esporte que a quadra não tem;
  - `403 EMAIL_NOT_VERIFIED`: a conta ainda não confirmou o e-mail (D58);
  - `403 OWN_VENUE`: o dono não pede horário na própria quadra;
  - `409 REQUEST_LIMIT`: já tem 3 pedidos pendentes, ou 1 pendente nesta quadra;
  - `404 NOT_FOUND`: quadra inexistente ou desativada.

### Objeto `request`

```json
{
  "id": 31,
  "venue": { "id": 2, "name": "Arena Central", "cover_image_url": "https://kolae.online/uploads/venues/2/thumb_x.jpg" },
  "sport_id": 1,
  "start_time": "2026-11-12T19:00:00-03:00",
  "end_time": "2026-11-12T20:00:00-03:00",
  "message": "Somos 10, futebol society",
  "status": "pending",
  "response_note": null,
  "created_at": "2026-11-05T21:10:00-03:00",
  "responded_at": null
}
```

| Campo | Regra |
| :---- | :---- |
| `status` | `pending`, `approved`, `declined`, `cancelled` ou `expired` (sem resposta até o início do horário) |
| `response_note` | Motivo da recusa, quando o dono escreveu; senão `null` |

### `GET /me/requests`

Jogador logado. Os pedidos da pessoa, mais recentes primeiro, paginados como o `GET /venues`
(`?page=`, `meta`). Filtro opcional `?status=pending`.

### `DELETE /me/requests/{id}`

Jogador logado. Cancela o pedido: se `pending`, a qualquer momento; se `approved`, até **2 horas**
antes do início (a partida sai da agenda e o dono é avisado). Resposta: **`200`** com o objeto
`request` atualizado. Erros: `404 NOT_FOUND` (pedido de outra pessoa ou inexistente),
`409 CANNOT_CANCEL` (fora do prazo, ou já recusado, cancelado ou expirado).

### `GET /me/notifications`

Jogador logado. Lista paginada (`?page=`), mais recentes primeiro.

```json
{
  "data": [
    {
      "id": 120,
      "type": "request_approved",
      "title": "Pedido aprovado",
      "body": "Arena Central aprovou seu horário de 12/11, 19:00 às 20:00.",
      "data": { "request_id": 31, "venue_id": 2 },
      "read_at": null,
      "created_at": "2026-11-06T10:02:00-03:00"
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 20, "total": 1, "unread": 1 }
}
```

- `type` para o jogador: `request_approved`, `request_declined`, `request_expired`. Tipos novos
  podem aparecer depois: o app mostra `title` e `body` como vierem.
- `title` e `body` vêm no idioma do cabeçalho `Accept-Language` (padrão `pt-BR`).
- `data` traz os ids para abrir a tela certa (o pedido ou a quadra).

### `POST /me/notifications/read`

Jogador logado. Marca como lidas: `{ "ids": [120, 121] }` ou `{ "all": true }`. Resposta **`204`**.

---

## Mudanças da v1.6.0 em endpoints antigos

### `GET /sports`

Público. Lista os esportes ativos, em ordem alfabética. Sem mudança na S3.

```json
{ "id": 1, "name": "Futebol", "description": null, "icon_path": "fa-futbol" }
```

- `icon_path`: classe do Font Awesome (ex: `fa-futbol`). No app, mapear para o ícone equivalente.

### `GET /venues` (formato legado, substituído na v1.6.0)

Antes da S3, a produção respondia no formato antigo: lista sem paginação, preço como string `"10.00"`, coordenadas como string, booleanos `0/1`. Desde 28/10 (lançado na v1.6.0) vale só o formato descrito acima. O app usava mock até lá, então não houve impacto.

### `POST /games`

**Removido na v1.6.0** (responde `404 NOT_FOUND`). Pelo modelo de produto (D02), a partida é agendada pelo dono no site; o app não cria nem entra em partidas.

---

## Chat (v1.7.0, D66)

Aprovado em 09/11/2026. A pessoa conversa com o responsável pela quadra; **o dono responde pelo
painel do site** (D02). Uma conversa por pessoa e quadra, só texto. Proposta completa (banco,
painel, plano B) em [`proposta-chat.md`](proposta-chat.md).

- **Todas as rotas exigem token.** Sem token ou com token inválido: os `401 AUTH_*` de sempre (seção [Formato das respostas](#formato-das-respostas)).
- **Quem não faz parte da conversa recebe `404 NOT_FOUND`**, igual a uma conversa que não existe
  (não revela que ela existe).
- No app, o `GET /conversations` traz só as conversas em que a pessoa logada é **quem pergunta**.
- As mensagens chegam por **consulta periódica** (a HostGator não mantém conexão aberta): conversa
  aberta a cada 5 s com `after_id`, lista a cada 30 s, nada com o app em segundo plano.
- Datas com `-03:00` (D16).

### Objetos

**Conversa**

```json
{
  "id": 12,
  "venue": { "id": 8, "name": "Quadra do Xuxa", "cover_image_url": "https://..." },
  "last_message": { "id": 340, "body": "Tem horário sábado?", "mine": true, "created_at": "2026-11-10T19:02:00-03:00" },
  "unread_count": 0,
  "blocked": false
}
```

| Campo | Regra |
| :---- | :---- |
| `venue.cover_image_url` | Capa da quadra, como na lista; `null` sem foto |
| `last_message` | Última mensagem, de qualquer lado; `null` numa conversa ainda sem mensagens |
| `unread_count` | Mensagens do responsável que a pessoa ainda não leu (`POST /conversations/{id}/read`) |
| `blocked` | O responsável bloqueou: dá para ler, não para enviar (`403 CHAT_BLOCKED`). Ele pode desbloquear |

**Mensagem**

```json
{ "id": 341, "body": "Tem sim, das 8h às 12h.", "mine": false, "created_at": "2026-11-10T19:05:00-03:00" }
```

`mine` = foi a pessoa logada que enviou (o app não precisa comparar ids de usuário).

### `GET /conversations`

Conversas da pessoa logada, **a mais recente primeiro** (pela última mensagem). **Conversas sem
mensagem ficam de fora.** Sem paginação nesta versão.

```json
{ "data": [ { "...": "objeto conversa" } ] }
```

### `POST /venues/{id}/conversations`

Abre a conversa com a quadra, ou devolve a que já existe. Sem corpo. **`201`** (nova) ou **`200`**
(já existia), com o objeto conversa (`last_message` = `null` se ainda não há mensagem).

| Erro | Quando |
| :---- | :---- |
| `404 VENUE_NOT_FOUND` | Quadra não existe ou está desativada (D34) |
| `422 CHAT_OWN_VENUE` | O responsável tentando falar com a própria quadra |

### `GET /conversations/{id}/messages`

| Parâmetro | Regra |
| :---- | :---- |
| `after_id` | Só as mais novas que esta (polling). Sem mensagem nova: `"data": []` |
| `before_id` | As mais antigas que esta (rolar para cima) |
| `per_page` | 1 a 50 (padrão 30) |

Sem `after_id` nem `before_id`: as `per_page` **mais recentes**. Sempre em **ordem crescente de
`id`**. `after_id` e `before_id` juntos, ou valor que não é número inteiro positivo:
`422 VALIDATION_ERROR`. Conversa de quadra desativada continua legível.

```json
{ "data": [ { "...": "objeto mensagem" } ] }
```

### `POST /conversations/{id}/messages`

Corpo `{ "body": "..." }` → **`201`** com o objeto mensagem. Quem envia já conta como tendo lido.

| Erro | Quando |
| :---- | :---- |
| `422 VALIDATION_ERROR`, `errors.body` | Vazio, mais de 1000 caracteres, ou com link (`http`, `www.`, domínio) |
| `403 CHAT_BLOCKED` | O responsável bloqueou a conversa |
| `404 VENUE_NOT_FOUND` | A quadra foi desativada (a conversa fica só para leitura) |
| `404 NOT_FOUND` | Conversa que não existe ou não é sua |
| `429 TOO_MANY_ATTEMPTS` | Mais de 20 mensagens por minuto; cabeçalho `Retry-After` em segundos (o app pode mostrar só a mensagem) |

### `POST /conversations/{id}/read`

Corpo `{ "last_message_id": 341 }` → **`204`**. Marca como lidas as mensagens até essa (zera o
`unread_count` se for a última). Só avança: um id menor que o já lido não muda nada. Id que não é
número inteiro positivo: `422 VALIDATION_ERROR`.

---

## Fora do escopo da entrega

- **Troca de e-mail com código** (`POST /me/email`, `POST /me/email/confirm`): cortada com o prazo novo (D28); a tela fica no mock do app.
- **Avaliações, favoritos e duas etapas**: vitrine no app, sem backend (D04). O chat saiu da vitrine com a D66.

Cada endpoint novo é descrito aqui (parâmetros, resposta, erros) antes de ser implementado.
