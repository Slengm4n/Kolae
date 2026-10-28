# Status da quadra, horário de funcionamento e agenda

> **Estado: PROPOSTA (28/10/2026), aguardando revisão do João e do Rafael.**
> Depois de aprovada, vira: mudanças no `database/kolae.dbml` + patch `004`, contrato da S3/S4 em `docs/api.md` e as telas do dono no site.
> As perguntas em aberto estão no fim do documento.

Base: decisões D02 (partidas agendadas pelo dono, no site) e D03 (status calculado pela API) em `docs/decisoes.md`.

## 1. O que o app precisa mostrar

| Status | Texto no app | Quando |
| :---- | :---- | :---- |
| `available` | Disponível | Dentro do horário de funcionamento e sem partida agora |
| `in_use` | Em uso | Há uma partida agendada neste horário |
| `closed` | Fechada | Fora do horário de funcionamento, num dia sem funcionamento, ou fechada pelo dono |

Junto com o status vem `status_until`: **quando o status atual muda** (fim da partida, fechamento ou próxima abertura). Pode ser `null` quando não há previsão (ex: o dono fechou a quadra sem data para reabrir).

Além disso, a agenda do dia (`/venues/{id}/schedule?date=`) mostra os horários livres e ocupados.

## 2. Mudanças no banco

### 2.1 Nova tabela `venue_hours` (horário de funcionamento)

Uma linha por dia da semana em que a quadra abre. **Dia sem linha = fechada o dia todo.**

```
Table venue_hours {
  id int [pk, increment]
  venue_id int [not null]
  weekday tinyint [not null, note: '0 = domingo ... 6 = sábado (igual ao date("w") do PHP)']
  opens_at time [not null, note: 'ex: 08:00']
  closes_at time [not null, note: 'ex: 22:00; precisa ser depois de opens_at']

  indexes {
    (venue_id, weekday) [unique]
  }
}
Ref: venue_hours.venue_id > venues.id [delete: cascade]
```

- Um intervalo por dia (sem pausa de almoço) e sem virar a meia-noite. Ver as perguntas em aberto.
- Quadra sem nenhuma linha em `venue_hours` = sem horário cadastrado. Proposta: tratar como `closed` e mostrar no site um aviso para o dono cadastrar.

### 2.2 Dono fecha a quadra: usa o `venues.status` que já existe

- `available`: segue o horário de funcionamento.
- `unavailable` ou `maintenance`: **sempre `closed`**, com `status_until = null`.

Não cria tabela nova. Fechamento com data marcada (ex: "fechada de 10 a 12/11") fica como melhoria futura.

### 2.3 Revisão da tabela `matches` (partidas)

Hoje: `venue_id, sport_id, creator_user_id, status, max_players, start_time, end_time` (+ datas). A tabela está **vazia** na produção, então dá para ajustar sem migrar dados.

| Coluna | Proposta |
| :---- | :---- |
| `creator_user_id` | Quem agendou (o dono ou um admin) |
| `sport_id` | Passa a ser **opcional** (o dono pode não saber o esporte de quem alugou) |
| `renter_name` | **Nova**, texto opcional: para quem foi alugado (ex: "Time do Carlos"). Só aparece no site |
| `start_time`, `end_time` | Obrigatórios, horário de Brasília. `end_time` depois de `start_time`, no mesmo dia, dentro do horário de funcionamento |
| `status` | Só `scheduled` ou `cancelled`. "Em andamento" e "terminada" são **calculados pelo horário**, não gravados |
| `max_players` | **Removida** (era do modelo antigo de participantes) |

- Duas partidas **não podem se sobrepor** na mesma quadra (validado no site ao salvar).
- Partida cancelada não conta para o status nem para a agenda.

### 2.4 Remover `match_participants`

Era do modelo antigo (entrar/sair de partida). Está vazia. Proposta: apagar a tabela no patch `004`.

### 2.5 WhatsApp do dono

```
users.whatsapp varchar(20) [null, note: 'Só dígitos, com DDI e DDD: 5511999999999']
```

- Preenchido pelo dono no site (perfil). Obrigatório para cadastrar quadra? Ver perguntas.
- Vai na API dentro de `owner.whatsapp` (D12). O app monta o link `https://wa.me/<número>`.

## 3. Regra do status (calculada pela API)

Sempre no horário de Brasília (D16). Para uma quadra, no instante `agora`:

1. Se `venues.status` não é `available` → **`closed`**, `status_until = null`.
2. Pega o horário de funcionamento de hoje (`venue_hours` do dia da semana).
   - Não abre hoje, ou já passou do `closes_at` → **`closed`**, `status_until` = próxima abertura (procura nos próximos 7 dias; se não houver, `null`).
   - Ainda não abriu (`agora` < `opens_at`) → **`closed`**, `status_until` = `opens_at` de hoje.
3. Dentro do horário: existe partida `scheduled` com `start_time <= agora < end_time`?
   - Sim → **`in_use`**, `status_until` = fim da partida. Se outra partida começa exatamente quando essa termina, emenda: o `status_until` vai até o fim da última partida em sequência.
   - Não → **`available`**, `status_until` = o que vier primeiro: início da próxima partida de hoje ou `closes_at`.

### Exemplos (quadra abre 08:00–22:00, partida 19:00–21:00)

| Agora | Status | `status_until` |
| :---- | :---- | :---- |
| 07:30 | `closed` | 08:00 de hoje |
| 10:00 | `available` | 19:00 de hoje |
| 19:30 | `in_use` | 21:00 de hoje |
| 21:15 | `available` | 22:00 de hoje |
| 23:00 | `closed` | 08:00 do próximo dia que abre |
| qualquer hora, `venues.status = maintenance` | `closed` | `null` |

## 4. Agenda do dia (`GET /venues/{id}/schedule?date=`)

Divide o horário de funcionamento do dia em faixas livres e ocupadas:

```json
{
  "data": {
    "date": "2026-10-28",
    "opens_at": "08:00",
    "closes_at": "22:00",
    "slots": [
      { "start": "2026-10-28T08:00:00-03:00", "end": "2026-10-28T19:00:00-03:00", "status": "available" },
      { "start": "2026-10-28T19:00:00-03:00", "end": "2026-10-28T21:00:00-03:00", "status": "in_use" },
      { "start": "2026-10-28T21:00:00-03:00", "end": "2026-10-28T22:00:00-03:00", "status": "available" }
    ]
  }
}
```

- Dia sem funcionamento, ou quadra fechada pelo dono: `opens_at` e `closes_at` = `null` e `slots` = `[]`.
- Faixas do passado continuam aparecendo (o app pode escurecer as que já passaram).
- O `renter_name` **não** vai para a API: quem alugou não é informação pública.

## 5. Telas novas no site (S4)

Para o dono (e o admin):

1. **Horário de funcionamento**, dentro da edição da quadra: os 7 dias da semana, cada um com "abre / fecha" ou "fechado".
2. **Agenda da quadra**: lista das partidas por dia, com "Agendar partida" (dia, início, fim, esporte opcional, para quem) e "Cancelar". Avisa se o horário se sobrepõe a outra partida ou fica fora do funcionamento.
3. **WhatsApp** no perfil do dono.
4. **Status da quadra** (disponível, manutenção, indisponível) na edição da quadra, se ainda não estiver visível lá.

## 6. Para a apresentação (S6)

O status depende do relógio. Nos dados de demonstração, cadastrar partidas que começam e terminam **durante a apresentação**, para a banca ver a quadra mudar de "Disponível" para "Em uso" ao vivo. Combinar o horário exato da apresentação antes.

## 7. Perguntas em aberto (responder na revisão)

1. **Um intervalo por dia basta?** Quadra com pausa (ex: 08–12 e 14–22) precisaria de mais de uma linha por dia. Proposta: um intervalo só, para caber no prazo.
2. **Funcionamento que passa da meia-noite** (ex: 18:00–02:00)? Proposta: não suportar agora; fecha no máximo às 23:59.
3. **Quadra sem horário cadastrado** aparece como `closed` (proposta) ou some do mapa?
4. **WhatsApp obrigatório** para o dono cadastrar quadra? Proposta: obrigatório ao cadastrar ou editar quadra; sem ele, o botão "Falar com o dono" não aparece no app.
5. **Esporte da partida** opcional (proposta) ou obrigatório?
6. **Apagar `match_participants`** (proposta) ou manter vazia para o futuro?
7. Partidas sempre dentro do horário de funcionamento (proposta), ou o dono pode agendar fora dele?
