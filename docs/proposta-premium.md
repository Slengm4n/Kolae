# Proposta: Kolae Premium para quadras (plano, estatísticas, anúncios, solicitações e notificações)

**Situação:** **aprovada** (05/11/2026; ideia do Rafael, aprovada pelo João). Virou as
[D61 a D64](decisoes.md#d61--kolae-premium-por-quadra-ativado-pelo-admin-05112026); o contrato está no
[`api.md`](api.md#kolae-premium-v170-d61d64). Sai na **v1.7.0**, uma PR por parte.
**Esforço:** G no total, dividido em quatro partes de esforço M. Backend em 05–17/11; o app
integra em paralelo, a partir dos mocks, e liga na API até 20/11.

## O que é

Um plano **Premium do dono** (perfil, vale para todas as quadras dele; era por quadra na versão de 05/11), ativado pelo admin (sem pagamento dentro do Kolae), e quatro
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

### Plano: colunas em `users` (o plano é do dono)

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

- **Plano** (`/dashboard/perfil/plano`, em Configurações > Kolae Premium; era uma aba da quadra na versão de 05/11): tabela Grátis x Premium,
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

   Itens 2 a 4 em detalhe, com os estados e os erros: seção "O que o Rafael precisa fazer: pedidos
   de horário e notificações (D64)", logo abaixo.
5. Registrar os cliques (`POST /venues/{id}/events`) no WhatsApp, nas redes, no compartilhar e
   no anúncio.

Tudo pode começar com mock a partir deste contrato, sem esperar o backend.

### O que o Rafael precisa fazer: pedidos de horário e notificações (D64)

**Situação em 10/11:** o site e a API estão prontos (partes A a C; entram na `main` com as PRs
`feat/pedidos-regras` e `feat/pedidos-api`). O dono já aprova e recusa no painel; falta o app
pedir, acompanhar e receber os avisos. Contrato completo no [`api.md`](api.md#post-venuesidrequests).
**Prazo do app: 17/11.**

**1. Botão "Pedir horário" no detalhe da quadra**
- Só para quem está logado. Sem login, o botão leva ao login.
- Com `user.email_verified` = `false`, mostrar "Confirme seu e-mail para pedir horário" (a API
  responde `403 EMAIL_NOT_VERIFIED`); o reenvio do link é pelo site.
- O dono da própria quadra recebe `403 OWN_VENUE`: mostrar a `message` (ele marca pela agenda do
  site).

**2. Tela do pedido**
- **Dia:** de hoje até 30 dias à frente. Ao escolher, carregar `GET /venues/{id}/schedule?date=` e
  mostrar só as faixas `available`.
- **Horário:** início e fim em blocos de 30 minutos (19:00, 19:30...), dentro de uma faixa livre,
  de 30 min a 4 h. Mandar com o fuso: `2026-11-12T19:00:00-03:00`.
- **Esporte** (opcional): um da lista `sports` da quadra. **Mensagem** (opcional): até 200
  caracteres, com contador.
- **Enviar:** `POST /venues/{id}/requests`.
  - `201`: tela "Pedido enviado". Deixar claro que **o horário só fica reservado quando o dono
    aprovar** (pedido pendente não aparece como ocupado na agenda).
  - `422`: mostrar cada erro embaixo do campo (`errors.start_time`, `errors.end_time`,
    `errors.sport_id`, `errors.message`, sempre listas de textos). Se for horário ocupado,
    recarregar a agenda do dia.
  - `409 REQUEST_LIMIT`: mostrar a `message` (já tem 1 pedido nesta quadra ou 3 no total) com um
    atalho para "Meus pedidos".
  - `404 NOT_FOUND`: quadra desativada; voltar.

**3. Tela "Meus pedidos"** (no Menu)
- `GET /me/requests` (paginado; `?status=pending` para a aba "Esperando").
- Cada cartão: capa e nome da quadra, dia e horário, situação (`pending` Esperando resposta,
  `approved` Aprovado, `declined` Recusado, `cancelled` Cancelado, `expired` Expirou) e o
  `response_note` quando o dono recusou com motivo.
- **Cancelar** (com confirmação): `DELETE /me/requests/{id}`. Mostrar o botão no pendente e no
  aprovado com 2 horas ou mais de antecedência; quem decide é a API (`409 CANNOT_CANCEL`: mostrar
  a `message`). Depois, recarregar a lista.
- Tocar no cartão abre a quadra.

**4. Tela "Notificações"** (sino no topo ou no Menu)
- `GET /me/notifications`: mostrar `title`, `body` e a hora, com destaque nas não lidas
  (`read_at` = `null`). O texto já vem pronto no idioma do cabeçalho `Accept-Language` (mandar o
  idioma do app).
- **Contador no ícone:** `meta.unread`. Buscar ao abrir o app, ao voltar para ele e a cada 60 s
  com o app aberto (sem push, é assim que o aviso chega).
- **Marcar como lidas:** `POST /me/notifications/read` com os `ids` mostrados, ou `{ "all": true }`
  num botão "Marcar todas".
- **Tocar:** `request_*` abre "Meus pedidos" (`data.request_id`); os outros, a quadra
  (`data.venue_id`). Tipo desconhecido: só mostrar o texto.

**5. Mock, textos e testes**
- Mock em `src/api/mocks/` (ex.: `EXPO_PUBLIC_MOCK_REQUESTS`) com os casos acima: sucesso, cada
  erro, limite, cancelar fora do prazo, lista vazia.
- Textos nos 7 idiomas; leitor de tela: cada cartão é um elemento só (quadra, dia, horário e
  situação).
- **Teste de ponta a ponta com o João** (produção, pelo 4G): pedir pelo app → aparece no sino do
  painel → aprovar → o app mostra "Aprovado" e a notificação; recusar com motivo; cancelar um
  aprovado; pedir um horário daqui a 30 minutos e não responder (expira).

**Plano B (17/11):** se o app não ficar pronto, a demonstração usa o painel do site; os pedidos de
exemplo são criados pela API (como no teste de 10/11) e o dono aprova ao vivo.
**Notificação no celular (push):** fica para depois, junto com a do chat (`proposta-chat.md`,
"Notificação no app"); sem push, o contador do item 4 resolve.

## Andamento (06/11)

| Parte | Situação | Site | App (Rafael) |
| :---- | :---- | :---- | :---- |
| Plano do dono (D61) | ✅ pronto, PR `feat/premium-plano` (patch 009 antes) | Configurações > Kolae Premium; admin ativa no painel | Nada a fazer |
| Estatísticas (D62) | ✅ pronto, PR `feat/premium-estatisticas-v2` | Aba Estatísticas da quadra | O `GET /venues/{id}` já conta a visualização; **pode ligar agora** o `POST /venues/{id}/events` nos cliques de WhatsApp, redes e compartilhar (sem esperar a resposta) |
| Anúncios (D63) | ✅ pronto (5 partes, PRs `feat/premium-anuncios-*`) | Aba Anúncio do dono Premium; "Patrocinado" na home, em /quadras e na página pública; admin remove com motivo | **Pode ligar agora:** carrossel "Destaques" (`GET /ads`), faixa `ad` no detalhe e `ad_view`/`ad_click` no `POST /venues/{id}/events` |
| Solicitações e notificações (D64) | ✅ site e API prontos (10/11; PRs `feat/pedidos-regras` e `feat/pedidos-api`) | `/dashboard/notificacoes` com sino; e-mail para o dono | Montar com mock: "Pedir horário", "Meus pedidos", "Notificações" |

Também prontas: animações na troca de abas, de Configurações e de tema (PR `feat/animacoes-troca`).
As PRs são empilhadas: entram na ordem da tabela, e cada uma só depois da anterior.

### Registro do andamento dos anúncios (D63)

Cinco partes, uma PR cada, empilhadas: **A** regras e banco → **B** aba Anúncio do dono → **C**
"Patrocinado" no site → **D** API (`ad` e `GET /ads`) → **E** remoção pelo admin.

Regras combinadas em 09/11: período de **até 30 dias contando o primeiro** (10/11 a 09/12), início
hoje ou depois; **um anúncio "em aberto" por quadra** (ativo ou pausado, ainda no período; encerrado,
removido ou vencido libera outro); com o **Premium vencido**, o anúncio fica salvo e só deixa de
aparecer (volta sozinho se o plano for renovado); quadra em manutenção continua mostrando o anúncio,
só a desativada (D34) esconde. "Vezes que apareceu" e cliques do anúncio saem dos eventos `ad_view` e
`ad_click` da quadra dentro do período dele (a `venue_events` não guarda o id do anúncio, e só há um
por vez).

- **09/11, parte A (`feat/premium-anuncios-regras`):** `App\Core\VenueAds` (validação, quando
  aparece, situação para o dono, ações e sorteio do dia, igual o dia todo) e o model `VenueAd`. Sem
  tela e sem rota. Testes: `tests/venue_ads_test.php` (64 casos, sem banco) e
  `tests/venue_ads_db_test.php` (36, no banco local, deixa tudo como encontrou).
  - **O que deu errado:** o banco local deste PC não tinha o patch 009 (o Premium nem abria aqui);
    aplicado só no local, com backup antes. Nos testes, dois erros meus nos próprios testes (um caso
    de 31 dias tratado como 30, e eventos gravados antes de o anúncio existir); a regra estava certa.
- **09/11, parte B (`feat/premium-anuncios-dono`):** aba **Anúncio** da quadra
  (`{painel}/quadras/{id}/anuncio`, no painel do dono e no do admin), com o card "Patrocinado"
  num partial (`partials/ad_card.php`) que a parte C reaproveita. Premium: formulário com prévia ao
  vivo (título, texto, período em que o fim acompanha o início, foto opcional ou a capa), anúncio no
  ar com a situação, os números (vezes que apareceu, cliques, taxa) e pausar, retomar, editar e
  encerrar (com confirmação); histórico dos anteriores. Grátis: convite para o Premium; o servidor
  também recusa criar. Premium vencido: o anúncio fica salvo, dá para pausar e encerrar, mas não
  para editar nem retomar. Removido pelo admin: o dono vê o motivo. Fotos em `uploads/ads/{id}/`,
  com a mesma validação e otimização das fotos da quadra. Textos nos 7 idiomas. Teste de clique
  `tests/e2e/testes/anuncio.js` (44 passos).
  - **O que deu errado:** a primeira rodada do teste de clique teve 3 falhas, todas do teste: a
    quadra do seed não tem foto (a tela fez certo em esconder a capa; o teste agora põe uma capa e
    tira no fim), uma variável usada dentro do navegador e a URL conferida depois de o aviso limpar
    o `?status=`. Uma rodada interrompida deixou uma pasta vazia em `uploads/ads/`, apagada. O
    teste `estatisticas` não roda neste PC: o `curl` do Windows não aceita `-o /dev/null` (erro 23);
    é do ambiente, não do código.
- **09/11, parte C (`feat/premium-anuncios-site`):** "Patrocinado" para o jogador no site.
  Bloco **Destaques** logo abaixo da abertura da home e no topo da `/quadras` (até 3, sorteados por
  dia, os mesmos nas duas páginas e o dia todo; na `/quadras` aparece com ou sem filtro), com o card
  da parte B (`partials/ad_highlights.php`), e faixa do anúncio na página pública da quadra (sem
  foto, porque a galeria já está ali). `AppCoreAdDisplay` monta os cards e conta: cada card
  mostrado é uma vez que apareceu (`ad_view`, contado no servidor ao montar a página); o card leva a
  `/quadra/{id}?ref=ad`, que conta o clique (`ad_click`) e redireciona para o endereço limpo, para
  atualizar, trocar o idioma ou compartilhar não contar de novo. Mesmos filtros das estatísticas:
  robô, dono, admin e repetição não contam. Anúncio nunca derruba a página: se der erro, ela abre
  sem o bloco. Teste de clique `tests/e2e/testes/patrocinado.js` (28 passos) e 4 casos novos no
  `tests/venue_ads_db_test.php`.
  - **O que deu errado:** a primeira versão buscava o bairro e a cidade em colunas que não existem na
    `venues` (ficam em `addresses`); a home abriu sem o bloco e o erro foi para o log, como
    planejado, e foi corrigido. O teste `quadras` pegou rolagem para o lado no celular: o nome
    comprido da quadra alargava o card (faltava `min-w-0`); na home o card ficava cortado. O teste
    novo, na primeira versão, não pegava isso (as quadras de teste têm nome curto); agora usa um nome
    comprido e foi conferido falhando sem a correção. No `venue_ads_db_test`, um caso exigia que só o
    anúncio do teste estivesse no ar e falhou com um anúncio criado à mão no banco local; agora olha
    só as quadras do teste. O `estatisticas` passou a usar o `devNull` do Node no lugar de
    `/dev/null` e roda neste PC (31 passos).
- **09/11, parte D (`feat/premium-anuncios-api`):** anúncio na API para o app. Campo `ad` (ou
  `null`, sempre presente) em cada item do `GET /venues` e no `GET /venues/{id}` (último campo,
  depois de `socials`), com `image_url` = foto do anúncio, ou a capa, ou `null`.
  `GET /api/v1/ads`: até 5 anúncios no ar, no mesmo sorteio do dia do site, cada um com a quadra
  no formato da lista (sem `ad` e sem `distance_km`). A API não conta a aparição ao responder: o
  app envia `ad_view` e `ad_click` pelo `POST /venues/{id}/events`, que agora só conta esses dois
  se a quadra tiver anúncio no ar (a resposta continua `204`). A página `/quadras` usa a mesma
  lista e não muda. Contrato no `docs/api.md`. Teste `tests/e2e/testes/anuncio_api.js` (25
  passos, só HTTP, sem navegador).
  - **O que deu errado:** ao experimentar a API com o `curl`, o `GET /venues/{id}` contou uma
    visita do app nas quadras 1 e 2 do banco local (o filtro de robôs não vale para o app, de
    propósito); os 2 eventos foram apagados. O teste `redes` conferia que `socials` era o último
    campo do detalhe; agora confere `owner`, `socials` e `ad`. O teste novo passou de primeira,
    então foi conferido falhando com a regra do `ad_view`/`ad_click` desligada. Não deu para testar
    o limite de 5 do `GET /ads` no teste de clique (o seed só tem 4 quadras que podem ter anúncio);
    o corte é o mesmo `dailyPick` coberto no `tests/venue_ads_test.php`.
- **09/11, parte E (`feat/premium-anuncios-admin`):** card **Anúncios** no painel do admin, abaixo
  do Kolae Premium, com os anúncios em aberto (ativos, agendados e pausados; quadra, dono, período e
  situação, com link para a aba Anúncio da quadra no admin). **Remover com motivo**: o motivo é
  obrigatório (5 a 200 caracteres, uma linha; conferido no navegador e no servidor, em
  `VenueAds::cleanReason`), pede confirmação, tira do ar na hora e o dono lê o motivo na aba
  Anúncio (feito na parte B). O dono recebe a notificação `ad_removed` na tabela `notifications`
  (quadra, anúncio e motivo em `data`); ela aparece no sino quando ele existir (D64). Se a
  notificação falhar, a remoção continua valendo. Removido não volta; o dono pode criar outro.
  Avisos nos 7 idiomas. Teste de clique `tests/e2e/testes/anuncio_admin.js` (20 passos) e 8 casos
  novos no `tests/venue_ads_test.php`.
  - **O que deu errado:** nada no código. O teste novo passou de primeira e foi conferido falhando
    com a notificação desligada. **Fica para depois:** e-mail ao dono na remoção (hoje só a
    notificação e o motivo na aba) e o histórico de anúncios removidos no admin (o card mostra só os
    em aberto).

**D63 concluída em 09/11**, antes do prazo de 13/11.

### Registro do andamento das solicitações e notificações (D64)

Retomada em 10/11 (o João escolheu fazer a D64 antes do login com Google, porque o sino também
serve para o chat). Quatro partes: **A** regras e banco, sem rota → **B** API do app (as 5 rotas do
contrato) → **C** painel do dono (sino no cabeçalho, "Notificações e solicitações" com aprovar e
recusar, e-mail ao dono a cada pedido novo) → **D** o que o Rafael precisa fazer no app. **B e C vão
para a `main` juntas**, para um pedido feito pelo app sempre ter onde ser respondido. As tabelas
`match_requests` e `notifications` já estão na produção (patch 009): nenhum patch novo.

- **10/11, parte A (`feat/pedidos-regras`):** `AppCoreMatchRequests` (data e hora do contrato
  com fuso; no futuro e até 30 dias; blocos de 30 minutos; de 30 min a 4 h; funcionamento e
  conflito pela mesma `VenueSchedule::validateMatch` da aba Agenda; esporte que a quadra tem;
  mensagem até 200; limite de 3 pendentes por pessoa e 1 por quadra; cancelar pendente a qualquer
  hora e aprovado até 2 h antes; motivo da recusa opcional; só o primeiro nome de quem pediu) e
  `AppCoreNotificationText` (título e texto montados no idioma de quem lê, pelo
  `Accept-Language` na API; o banco guarda só o tipo e os dados). Model `MatchRequest` com o
  fluxo: pedir (avisa o dono), aprovar (confere o horário de novo, cria a partida com o primeiro
  nome e recusa sozinho os outros pendentes no mesmo horário; dois cliques ao mesmo tempo não
  aprovam duas vezes), recusar, cancelar (o aprovado tira a partida da agenda) e expirar. **Expirar
  não depende de cron** (a HostGator compartilhada não garante): os pendentes que passaram do início
  expiram na próxima leitura. `Notification` ganhou lista, não lidas e marcar como lidas (só as da
  própria pessoa). 16 textos de notificação nos 7 idiomas, incluindo o `ad_removed` da D63, que já
  era gravado e não tinha texto. Testes: `tests/match_requests_test.php` (68 casos, sem banco) e
  `tests/match_requests_db_test.php` (36, no banco do seed; deixa tudo como encontrou).
  - **O que deu errado:** nos textos em português, "na {quadra}" e "da {quadra}" erravam com nomes
    masculinos ("na Clube do Zé"); agora "em {quadra}" e "({quadra})". Os testes passaram de
    primeira, então o do banco foi conferido falhando com a recusa automática desligada (2 falhas).
    A limpeza do teste apagava as notificações novas de qualquer conta; agora só as das três contas
    do teste.
- **10/11, parte B (`feat/pedidos-api`, local, empilhada na A):** `RequestApiController` com as 5
  rotas do contrato: `POST /venues/{id}/requests` (token; `403 EMAIL_NOT_VERIFIED`, `403 OWN_VENUE`,
  `404 NOT_FOUND`, `422` por campo com os textos de `MatchRequests::MESSAGES`, `409 REQUEST_LIMIT`),
  `GET /me/requests` (página, `per_page`, filtro `status`), `DELETE /me/requests/{id}` (`409
  CANNOT_CANCEL`), `GET /me/notifications` (título e texto pelo `Accept-Language`, `data` só com os
  ids, `meta.unread`) e `POST /me/notifications/read` (`ids` ou `all`). O `Router` ganhou o método
  DELETE (não existia). Teste `tests/e2e/testes/pedidos_api.js` (47 passos, só HTTP; a resposta do
  dono é gravada direto no banco até o painel existir).
  - **O que deu errado:** um passo falhou porque o teste gravava no banco uma notificação com
    acento ("manutenção") pelo `lib.sql`, que passa pela linha de comando do Windows (perde os
    acentos) e pelo MySQL (come a barra invertida). O código estava certo; o teste agora grava o
    JSON só com ASCII (o "ç" escrito como código de escape, com a barra dobrada). Não sobe sozinha: vai junto com a parte C.
- **10/11, parte C (`feat/pedidos-api`, local, junto com a B):** **"Notificações e solicitações"**
  no painel do dono (`RequestController`, tela `requests/index`, rota `/dashboard/notificacoes`).
  **Sino** no cabeçalho de todas as páginas do dono (também no celular), com o número do que pede
  atenção: pedidos esperando resposta + avisos não lidos, sem contar o aviso de "novo pedido" (o
  pedido pendente já conta), para o número só sumir quando o dono responde. **Aba Solicitações**
  (abre sozinha quando há pedido esperando): cartão com o primeiro nome, dia e horário, quadra,
  esporte e mensagem; **Aprovar** (com a confirmação do Kolae) vira partida na agenda e recusa
  sozinho os outros no mesmo horário; **Recusar** com motivo opcional (até 200); histórico de 30
  dias com o **WhatsApp de quem pediu só depois de aprovar** (se cadastrou). **Aba Avisos**: textos
  no idioma do painel; abrir marca como lidos; atalho para Mensagens quando há mensagem do chat não
  lida (as mensagens continuam com o próprio número, sem contar duas vezes). Só o dono (o admin é
  levado ao painel dele; outro dono recebe "não encontrado"). **E-mail ao dono** a cada pedido novo
  (`Mailer::sendRequestNew`, modelo `emails/request_new.php`, nunca com o e-mail de quem pediu;
  endereços `.local`/`.test` não recebem). 40 textos nos 7 idiomas. Testes: teste de clique
  `tests/e2e/testes/pedidos_painel.js` (35 passos: pedido pela API → sino → aprovar → agenda →
  app) e 5 casos do e-mail no `tests/match_requests_test.php` (73).
  - **O que deu errado:** o teste de clique do "outro dono" pegava o token de CSRF na página de
    notificações, que não tem formulário quando não há pedido; sem token, o Kolae mostrava "Página
    expirada" (recusa certa, mas não o caso testado). Agora o token vem do perfil. Depois da última
    rodada, o XAMPP ficou desligado neste PC (Apache e MySQL); os testes finais rodam com ele ligado.
    Com ele ligado de novo: 471 casos de PHP e 8 testes de clique (201 passos), todos passando.
- **10/11, parte D (documentação, na `feat/pedidos-api`):** seção "O que o Rafael precisa fazer:
  pedidos de horário e notificações (D64)" (botão, tela do pedido, Meus pedidos, Notificações,
  mock, testes e plano B). No `api.md`: paginação e erros do `GET /me/requests`, como o pedido
  expira, os idiomas do `Accept-Language`, os tipos que um dono pode receber, `data` só com ids e as
  regras do `POST /me/notifications/read`.
  - **O que deu errado:** o `api.md` dizia que confirmar o e-mail "é só informativo: nada fica
    bloqueado", mas pedir horário exige o e-mail confirmado (`403 EMAIL_NOT_VERIFIED`, no próprio
    contrato da D64). Corrigido na seção do objeto `user`.
  - **Teste no PC do João (10/11):** dois pedidos de exemplo na quadra dele (pela API, contas
    `dono@teste.local` e `admin@teste.local`, esta com o e-mail confirmado só no banco local) para
    ver o sino, aprovar e a recusa automática.

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
