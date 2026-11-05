# Proposta: partidas com jogadores (Kolae 2.0)

**Situação:** proposta para **depois da entrega** (05/11/2026). Não muda nada na v1.6.x.
Aprovada em partes, cada parte vira uma decisão nova em [`decisoes.md`](decisoes.md) (a partir
da D60) e o contrato entra em [`api.md`](api.md) antes do código.
**Esforço:** G. Pensada para a versão em Laravel (D01).

## Por que mudar

Hoje (D02) só o dono agenda partidas, no site, para quem alugou o horário. O app mostra o status
da quadra e a agenda, e o aluguel é combinado pelo WhatsApp. Funciona para **achar uma quadra
livre**, mas não resolve o outro problema de quem joga: **achar gente para jogar** (falta um
goleiro, o time não fecha, quem acabou de mudar de bairro não conhece ninguém).

A proposta abre as partidas para jogadores **sem tirar o controle do dono**: a quadra continua
sendo dele, e nada entra na agenda sem ele aprovar.

## Princípios

1. **A agenda é do dono.** Nenhuma partida ocupa a quadra sem o dono criar ou aprovar (mantém o
   espírito da D02). O status (D03) continua vindo só da agenda.
2. **Dinheiro fora do app** por enquanto: o aluguel segue combinado com o dono. O app pode
   mostrar a divisão por pessoa como informação.
3. **Privacidade:** na partida aparecem o primeiro nome e a foto de quem entrou, nunca o
   e-mail, a data de nascimento ou o telefone. Quem alugou continua sem aparecer quando a
   partida é fechada (D02, `renter_name` fora da API).
4. **Segurança antes de crescer:** denúncia, bloqueio e limites de uso entram junto com a
   primeira parte, não depois.

## Em três fases

### Fase 1: partida aberta (o dono abre vagas)

A menor mudança, que já resolve "falta gente".

- Ao agendar no site, o dono (ou quem alugou, se ele permitir) marca a partida como **aberta**:
  esporte (obrigatório nesse caso, ao contrário da D25), número de vagas, nível (iniciante,
  intermediário, avançado, livre), quem pode entrar (todos ou por convite) e uma observação.
- No app, a agenda da quadra e uma lista nova **"Partidas abertas perto de você"** mostram essas
  partidas com as vagas ("Futebol society · 3 vagas · hoje 19:00").
- **Entrar:** a pessoa toca em "Quero jogar". Se houver vaga, entra. Se não, vai para a **lista
  de espera** e sobe sozinha quando alguém sai.
- **Sair:** até um prazo antes do início, definido pelo dono (por exemplo, 2 horas). Depois do
  prazo, sair conta como falta.
- **Cancelar:** se o dono cancelar, todo mundo é avisado. Dentro do app enquanto não houver
  push; com push na fase 3.
- O dono vê no painel quem entrou e pode tirar alguém da partida, dizendo o motivo.

### Fase 2: pedido de horário (o jogador propõe)

- No app, a pessoa escolhe uma faixa **livre** da agenda e envia um **pedido**: horário,
  esporte, se a partida é aberta e quantas vagas.
- O dono recebe no painel (e, com push, no celular) e **aprova ou recusa**. Aprovado, vira uma
  partida normal da agenda, já com a pessoa como organizadora.
- Enquanto o pedido não é respondido, o horário **não fica bloqueado** (para não travar a quadra
  com pedidos esquecidos). Ele expira sozinho depois de um prazo, por exemplo 24 horas ou o
  início do horário.
- O "Falar com o dono" continua para combinar o pagamento.

### Fase 3: depois do jogo

- **Presença:** o organizador (ou o dono) confirma quem foi. Falta sem aviso conta contra a pessoa.
- **Placar** (opcional) e **avaliações**: da quadra (estrelas e comentário, substitui a vitrine
  da D04) e, de forma simples, das pessoas ("jogaria de novo?"), visível só como selo e nunca
  como nota pública individual.
- **Ranking:** começar por **presença e participação** (partidas jogadas, sequência de semanas,
  "sempre aparece"), não por vitória. Ranking de vitórias estimula briga e precisa de placar
  confiável. Fica para depois, por esporte e por bairro.
- **Notificações push:** vaga abriu, partida amanhã, partida cancelada, pedido aprovado. Só
  funcionam no APK (o Expo Go no Android não recebe push) e pedem um projeto Firebase (grátis).

## Rascunho de banco

Partindo da `matches` atual (`venue_id, sport_id, creator_user_id, renter_name, status,
start_time, end_time`):

| Mudança | Para quê |
| :---- | :---- |
| `matches.visibility` (`private` \| `open` \| `invite`) | Partida fechada (como hoje), aberta ou por convite |
| `matches.slots` (`INT NULL`) | Vagas para jogadores; `NULL` em partida fechada |
| `matches.level` (`VARCHAR(20) NULL`) | `beginner` \| `intermediate` \| `advanced` \| `any` |
| `matches.leave_deadline_minutes` (`INT`, padrão 120) | Até quando dá para sair sem contar falta |
| `matches.organizer_user_id` (`INT NULL`) | Quem organiza, quando não é o dono (fase 2) |
| `matches.notes` (`VARCHAR(280) NULL`) | Observação visível ("trazer colete escuro") |
| **Nova** `match_players` (`match_id, user_id, status, joined_at, left_at`) | `status`: `joined` \| `waitlist` \| `left` \| `removed` \| `attended` \| `no_show` |
| **Nova** `match_requests` (`venue_id, user_id, start_time, end_time, sport_id, visibility, slots, status, expires_at`) | Fase 2. `status`: `pending` \| `approved` \| `rejected` \| `expired` \| `cancelled` |
| **Nova** `venue_reviews` (`venue_id, user_id, match_id, rating, comment`) | Fase 3. Uma por pessoa por partida jogada (só quem foi avalia) |
| **Novas** `reports` e `user_blocks` | Denúncia (partida, comentário, pessoa) e bloqueio entre pessoas |

As regras atuais continuam: sem sobreposição na mesma quadra, dentro do horário de
funcionamento (D27), avulsa (D39), cancelada não conta para o status.

## Rascunho de endpoints (o formato final vai para o `api.md`)

| Método e rota | O quê | Fase |
| :---- | :---- | :---- |
| `GET /matches/open?lat&lng&sport_id&date` | Partidas abertas com vaga, por distância | 1 |
| `GET /matches/{id}` | Detalhe: quadra, horário, vagas, nível, quem entrou (primeiro nome e foto) | 1 |
| `POST /matches/{id}/join` | Entrar ou ir para a lista de espera (`409` se já está, `422` fora do prazo) | 1 |
| `POST /matches/{id}/leave` | Sair (conta falta depois do prazo) | 1 |
| `GET /me/matches` | Minhas partidas (próximas e jogadas) | 1 |
| `POST /venues/{id}/requests` | Pedido de horário | 2 |
| `GET /me/requests` / `DELETE /requests/{id}` | Acompanhar e desistir do pedido | 2 |
| `POST /matches/{id}/reviews` | Avaliar a quadra depois de jogar | 3 |
| `POST /reports`, `POST /users/{id}/block` | Denunciar e bloquear | 1 |

No site, o painel do dono ganha: campos de partida aberta no "Agendar partida", a lista de
quem entrou (com "Tirar da partida"), a caixa de **pedidos** (fase 2) e as avaliações recebidas
(fase 3).

## Telas novas no app

- **Partidas abertas** (aba ou seção da Pesquisar): cards com esporte, quadra, horário, vagas,
  nível e distância.
- **Detalhe da partida:** fotos de quem entrou, "Quero jogar" ou "Sair", lista de espera e
  "Falar com o organizador".
- **Minhas partidas** (no Menu): próximas, pedidos e histórico.
- **Pedido de horário:** a partir de uma faixa livre da agenda da quadra.
- **Avaliar** depois do jogo.

## O que precisa ser decidido (João e Rafael)

1. A fase 1 entra mesmo com o aluguel fora do app? (A sugestão é sim.)
2. Quem pode abrir uma partida: só o dono, ou também quem alugou o horário?
3. Prazo padrão para sair sem falta: 2 horas? O dono pode mudar?
4. Quantas faltas até a pessoa ficar um tempo sem entrar em partidas abertas?
5. Mostrar a divisão por pessoa (preço da hora × duração ÷ jogadores) ou não falar de dinheiro?
6. Ranking só de participação na primeira versão? (A sugestão é sim.)
7. Avaliação de pessoas: só o selo "jogaria de novo" ou nenhuma?
8. Partidas abertas a menores de idade? Hoje o cadastro exige 18 anos ou mais; a sugestão é
   manter.

## Decisões atuais que mudariam

| Decisão | O que muda |
| :---- | :---- |
| D02 (partidas pelo dono) | Continua valendo para partidas fechadas; partidas abertas e pedidos passam a ter participação do jogador, sempre com aprovação do dono |
| D04 (avaliações são vitrine) | Avaliações da quadra viram de verdade na fase 3 |
| D25 (esporte opcional) | Obrigatório só em partida aberta |
| D26 (`match_participants` removida) | Volta como `match_players`, com outro desenho |

## Ordem sugerida depois da entrega

1. Migração para Laravel (D01) com o contrato atual.
2. Denúncia e bloqueio, junto com a fase 1.
3. Fase 1 (partida aberta) e "Minhas partidas" no app.
4. Push no APK (Firebase).
5. Fase 2 (pedidos) e fase 3 (presença, avaliações, ranking de participação).
