# Proposta: Kolae Premium para quadras (plano, estatísticas, anúncios, solicitações e notificações)

**Situação:** **aprovada** (05/11/2026; ideia do Rafael, aprovada pelo João). Virou as
[D61 a D64](decisoes.md#d61--kolae-premium-por-quadra-ativado-pelo-admin-05112026); o contrato está no
[`api.md`](api.md#kolae-premium-v170-d61d64). Sai na **v1.7.0**, uma PR por parte.
**Esforço:** G no total, dividido em quatro partes de esforço M. Backend em 05–17/11; o app
integra em paralelo, a partir dos mocks, e liga na API até 20/11.

## O que é

Um plano **Premium por quadra**, ativado pelo admin (sem pagamento dentro do Kolae), e quatro
telas novas no painel do dono:

| Tela | Para quem | O que faz |
| :---- | :---- | :---- |
| **Plano da quadra** | Dono | Compara Grátis e Premium e pede a ativação; mostra o plano atual e a validade |
| **Estatísticas** (estilo YouTube Analytics) | Dono | Visualizações, visitantes, cliques no WhatsApp e nas redes, ocupação da agenda, por período, com gráfico e comparação |
| **Criar anúncio** (Premium) | Dono | Monta um destaque com título, texto, foto e período; aparece no site e no app com a etiqueta "Patrocinado" |
| **Notificações e solicitações** | Dono | Pedidos de horário dos jogadores (aprovar ou recusar) e os avisos do sistema |

**Por quê:** mostra o Kolae como produto com modelo de negócio (a pergunta clássica da banca), dá
ao dono um motivo para manter a quadra completa e abre o app para o jogador **pedir** um horário,
sem tirar do dono o controle da agenda (D02 continua: nada entra na agenda sem ele aprovar).

## Princípios

1. **A agenda continua do dono (D02).** Uma solicitação aprovada vira uma partida normal; uma
   solicitação pendente não bloqueia o horário.
2. **Sem dinheiro no Kolae.** O plano é ativado pelo admin; o aluguel continua combinado com o
   dono pelo WhatsApp.
3. **Privacidade.** Estatísticas são contagens, nunca "quem visitou". O dono vê o primeiro nome
   de quem pediu o horário e, depois de aprovar, o WhatsApp da pessoa só se ela tiver cadastrado.
4. **Grátis continua útil.** Solicitações e notificações valem para **todas** as quadras; o
   Premium soma anúncios e estatísticas completas.

## Grátis x Premium

| | Grátis | Premium |
| :---- | :---- | :---- |
| Aparecer no mapa, status ao vivo, página pública, QR Code | ✔ | ✔ |
| Receber solicitações de horário e notificações | ✔ | ✔ |
| Estatísticas | Resumo dos últimos 7 dias | 7, 28 ou 90 dias, gráfico por dia, comparação com o período anterior, origem das visitas, horários mais ocupados |
| Anúncios ("Patrocinado") | — | 1 anúncio ativo por vez |
| Selo "Premium" no painel | — | ✔ (não aparece para o jogador, para não parecer "quadra melhor") |

## Proposta de banco (patch 009)

### Plano: colunas em `venues`

| Coluna | Tipo | O que guarda |
| :---- | :---- | :---- |
| `plan` | `VARCHAR(10) NOT NULL DEFAULT 'free'` | `free` \| `premium` |
| `plan_until` | `DATE NULL` | Até quando o Premium vale (`NULL` = sem prazo). Vencido, volta a contar como Grátis sozinho |
| `plan_requested_at` | `TIMESTAMP NULL` | Quando o dono pediu o Premium (o admin vê os pedidos) |

### Estatísticas: `venue_events`

| Coluna | Tipo | O que guarda |
| :---- | :---- | :---- |
| `id` | `BIGINT` | |
| `venue_id` | `INT` | FK `venues` |
| `type` | `VARCHAR(20)` | `view`, `whatsapp`, `instagram`, `tiktok`, `facebook`, `website`, `share`, `ad_view`, `ad_click` |
| `source` | `VARCHAR(10)` | `site`, `app`, `qr` (visita que chegou pelo QR Code, `?ref=qr`) |
| `visitor` | `CHAR(16)` | Hash do dia (IP + navegador + data, com segredo): conta visitantes únicos sem guardar IP |
| `created_at` | `TIMESTAMP` | |

- Índice `(venue_id, created_at)`. Não conta: robôs (User-Agent conhecido), o próprio dono e o
  admin, e a mesma visita repetida em menos de 30 minutos.
- A ocupação (horas agendadas, partidas, dias e horários mais cheios) vem da `matches`, que já existe.

### Anúncios: `venue_ads`

| Coluna | Tipo | O que guarda |
| :---- | :---- | :---- |
| `id` | `INT` | |
| `venue_id` | `INT` | FK `venues` |
| `title` | `VARCHAR(60)` | "Terça com 20% off" |
| `body` | `VARCHAR(140)` | Texto curto |
| `image_path` | `VARCHAR(255) NULL` | Foto do anúncio; sem foto, usa a capa da quadra |
| `starts_on`, `ends_on` | `DATE` | Período (máximo de 30 dias) |
| `status` | `VARCHAR(10)` | `active` \| `paused` \| `removed` (o admin pode remover) |
| `created_at`, `updated_at` | `TIMESTAMP` | |

Só aparece se a quadra estiver Premium, o anúncio `active`, dentro do período, e a quadra não
estiver desativada (D34).

### Solicitações: `match_requests`

| Coluna | Tipo | O que guarda |
| :---- | :---- | :---- |
| `id` | `INT` | |
| `venue_id`, `user_id` | `INT` | Quadra e jogador que pediu |
| `sport_id` | `INT NULL` | Esporte (opcional, como na D25) |
| `start_time`, `end_time` | `DATETIME` | Horário pedido |
| `message` | `VARCHAR(200) NULL` | "Somos 10, futebol society" |
| `status` | `VARCHAR(10)` | `pending`, `approved`, `declined`, `cancelled`, `expired` |
| `response_note` | `VARCHAR(200) NULL` | Motivo da recusa (opcional) |
| `match_id` | `INT NULL` | Partida criada na aprovação |
| `created_at`, `responded_at` | `TIMESTAMP` | |

### Notificações: `notifications`

| Coluna | Tipo | O que guarda |
| :---- | :---- | :---- |
| `id` | `BIGINT` | |
| `user_id` | `INT` | Quem recebe |
| `type` | `VARCHAR(30)` | `request_new`, `request_approved`, `request_declined`, `request_cancelled`, `request_expired`, `plan_activated`, `plan_ending`, `ad_removed` |
| `data` | `JSON` (texto) | Ids e textos curtos para montar a mensagem no idioma de quem lê |
| `read_at` | `TIMESTAMP NULL` | |
| `created_at` | `TIMESTAMP` | |

## Regras das solicitações

- **Quem pede:** jogador logado no app, com e-mail confirmado (D58). O dono não pede na própria quadra.
- **Horário válido:** no futuro (até 30 dias), dentro do horário de funcionamento (D21), em
  blocos de 30 minutos, de 30 minutos a 4 horas, sem conflito com partida já agendada.
- **Limite:** no máximo 3 pedidos pendentes por jogador e 1 pendente por quadra.
- **Expira** sozinho no início do horário pedido se ninguém responder.
- **Aprovar:** confere o conflito de novo e cria a partida (`renter_name` = primeiro nome de quem
  pediu). Os outros pedidos pendentes que batem com esse horário são recusados automaticamente.
- **Recusar:** com motivo opcional. **Cancelar:** o jogador pode cancelar enquanto estiver
  pendente, ou depois de aprovado até 2 horas antes (a partida é cancelada e o dono é avisado).
- **Avisos:** o dono recebe notificação no painel e **e-mail** (Titan) a cada pedido novo; o
  jogador recebe notificação no app quando o pedido é respondido. Push no celular fica para
  depois (precisa de Firebase e não funciona no Expo Go).

## Proposta de contrato (para revisar antes de entrar no `api.md`)

Mesmo formato da API (D06). Erros de validação: `422 VALIDATION_ERROR` com `errors` por campo.

### Anúncios

`GET /venues` e `GET /venues/{id}` ganham o campo `ad` (ou `null`):

```json
"ad": {
  "id": 7,
  "title": "Terça com 20% off",
  "body": "Reserve pelo WhatsApp e ganhe desconto às terças.",
  "image_url": "https://kolae.online/uploads/ads/7/ad_abc.jpg",
  "ends_on": "2026-11-30"
}
```

`GET /ads` (público): até 5 anúncios ativos para o carrossel "Destaques" do app, com o resumo da
quadra (`venue` no mesmo formato da lista). Ordem aleatória a cada dia.

### Estatísticas (registro, sem leitura pelo app)

`POST /venues/{id}/events` (público, sem login; com login, o dono não é contado)

```json
{ "type": "whatsapp", "source": "app" }
```

- `type`: `whatsapp`, `instagram`, `tiktok`, `facebook`, `website`, `share`, `ad_view`, `ad_click`.
  A visualização (`view`) é contada pelo próprio `GET /venues/{id}`, sem chamada extra.
- Resposta `204` sem corpo. Limite por aparelho para não inflar números (`429` se passar).

### Solicitações (jogador logado)

- `POST /venues/{id}/requests`: `{ "start_time": "2026-11-12T19:00:00-03:00", "end_time": "2026-11-12T20:00:00-03:00", "sport_id": 1, "message": "Somos 10" }` → `201` com o pedido.
  Erros: `422` (horário fora do funcionamento, ocupado, no passado, duração inválida),
  `409 REQUEST_LIMIT` (já tem pedidos pendentes demais), `403 EMAIL_NOT_VERIFIED`.
- `GET /me/requests`: os pedidos da pessoa, mais recentes primeiro.
- `DELETE /me/requests/{id}`: cancela (regras acima).

```json
{
  "id": 31,
  "venue": { "id": 2, "name": "Arena Central" },
  "sport_id": 1,
  "start_time": "2026-11-12T19:00:00-03:00",
  "end_time": "2026-11-12T20:00:00-03:00",
  "message": "Somos 10",
  "status": "pending",
  "response_note": null,
  "created_at": "2026-11-05T21:10:00-03:00"
}
```

### Notificações (jogador logado)

- `GET /me/notifications?page=1`: lista paginada com `id`, `type`, `title`, `body` (já no
  idioma do app, pelo `Accept-Language`), `data` (ids para abrir a tela certa), `read_at`, `created_at`;
  e `meta.unread`.
- `POST /me/notifications/read`: `{ "ids": [1, 2] }` ou `{ "all": true }` → `204`.

## Telas no site

- **Plano** (`/dashboard/quadras/{id}/plano`, aba nova da quadra): tabela Grátis x Premium,
  plano atual e validade, botão "Quero o Premium" (vira pedido para o admin) e, no Premium,
  "Seu plano vale até 30/11".
- **Estatísticas** (`/dashboard/quadras/{id}/estatisticas`): seletor de período (7, 28, 90 dias);
  cartões com o número e a variação em relação ao período anterior (↑ 12%); gráfico de linha por
  dia (visualizações e contatos); origem das visitas (site, app, QR Code); mapa de calor de
  ocupação (dia da semana x hora) a partir da agenda; taxa de contato (cliques ÷ visualizações).
  No Grátis: só os cartões de 7 dias e um convite para o Premium. Gráfico com Chart.js (cdnjs).
- **Anúncio** (`/dashboard/quadras/{id}/anuncio`): formulário com prévia ao vivo de como fica no
  site e no app; pausar, editar e encerrar; números do anúncio (vezes que apareceu e cliques).
- **Notificações e solicitações** (`/dashboard/notificacoes`): sino com contador no cabeçalho do
  painel; abas "Solicitações" (pendentes primeiro, com aprovar e recusar, e o histórico) e
  "Notificações" (avisos, marcar como lidas).
- **Admin:** lista de pedidos de Premium (ativar com data de validade, desativar) e de anúncios
  (remover com motivo).
- **Público:** card "Patrocinado" no topo da home e da `/quadras` (até 3, sorteados por dia) e
  faixa do anúncio na página pública da quadra.

## O que o app precisa (Rafael)

1. Carrossel **"Destaques"** na tela inicial (`GET /ads`) e o selo "Patrocinado"; faixa do
   anúncio no detalhe da quadra (`ad`).
2. Botão **"Pedir horário"** no detalhe da quadra: escolher dia e faixa livre (a partir do
   `GET /venues/{id}/schedule`), esporte e mensagem.
3. Tela **"Meus pedidos"** (`GET /me/requests`), com cancelar.
4. Tela **"Notificações"** com contador no ícone (`GET /me/notifications`).
5. Registrar os cliques (`POST /venues/{id}/events`) no WhatsApp, nas redes, no compartilhar e
   no anúncio.

Tudo pode começar com mock a partir deste contrato, sem esperar o backend.

## Cronograma até o congelamento

| Data | Backend e site (João) | App (Rafael) |
| :---- | :---- | :---- |
| 05–06/11 | Revisão desta proposta; contrato no `api.md`; D61–D64 | Revisão; mocks a partir do contrato |
| 06–08/11 | Patch 009, plano (aba, pedido, admin) | Destaques e anúncio (mock) |
| 08–11/11 | Estatísticas: registro dos eventos (site e API) e tela | Registro dos cliques |
| 11–13/11 | Anúncios: criar, exibir no site, `ad` e `GET /ads` | Liga anúncios na API |
| 13–17/11 | Solicitações e notificações: API, regras, tela do dono, e-mail | "Pedir horário", "Meus pedidos", "Notificações" |
| 17–20/11 | Testes de clique, revisão, deploy, dados de demonstração | Liga tudo na API, APK |
| **21/11** | **Congelamento** | **Congelamento** |

Cada parte sai numa PR própria, com patch, testes e documentação, na ordem acima. Rota nova só
entra junto com a sua tela (a `main` publica direto).

## Riscos e plano B

- **Prazo apertado para o app.** Se até 17/11 o app não ligar as solicitações, a demonstração
  usa o **site** (o dono aprova no painel) e o app mostra só os anúncios; as telas do app ficam
  para a v1.7.
- **Números baixos na demonstração.** As estatísticas reais começam do zero. Para a banca, um
  seed de demonstração (só no banco da demonstração, nunca dados falsos para usuário real)
  preenche 90 dias de eventos.
- **Spam de pedidos.** Limites por jogador e por quadra, e-mail confirmado obrigatório.

## Decidido na aprovação

1. **Validade do Premium:** 30 dias por padrão; o admin escolhe outra data ou "sem prazo" ao ativar.
2. **Selo "Premium":** só no painel do dono, nunca para o jogador.
3. **Cancelar pedido aprovado:** até 2 horas antes do início.
4. **E-mail ao dono:** um a cada pedido novo (não resumo diário).
