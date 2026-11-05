# Proposta: chat entre a pessoa e o responsável pela quadra

**Situação:** proposta (05/11/2026). Decidido tentar antes do congelamento; vira a **D62**
quando o contrato abaixo for aprovado pelo João e pelo Rafael. Muda a **D04** em parte (o chat
deixa de ser vitrine).
**Esforço:** G (8 a 12 dias somando os dois, sem push; mais 2 a 3 com push). **Data de corte:
18/11** (ver "Se der errado").
**Versão:** sai na **v1.7.0** (D59), junto com o login com Google se os dois ficarem prontos.

## O que é

No detalhe da quadra, além do "Falar com o dono" (WhatsApp, D24), a pessoa pode **mandar uma
mensagem pelo próprio Kolae**. O responsável responde **pelo painel do site** (é lá que ele
trabalha, D02). A conversa é sobre aquela quadra: quem tem três quadras vê as conversas
separadas.

**Por quê:** nem todo dono quer expor o WhatsApp; a conversa fica registrada no Kolae; e na
banca mostra o ciclo completo (achar a quadra → ver se está livre → combinar o aluguel).

**Não é:** chat entre jogadores, grupo, envio de foto ou áudio. Só texto, uma conversa por pessoa
e quadra.

## Como as mensagens chegam: consulta periódica (polling)

A HostGator compartilhada não mantém conexão aberta (WebSocket). Então quem está com a conversa
aberta **pergunta à API se há mensagem nova**:

| Onde | De quanto em quanto tempo |
| :---- | :---- |
| App, conversa aberta | 5 s |
| App, lista de conversas | 30 s |
| App em segundo plano | não consulta |
| Site, conversa aberta | 10 s |
| Site, número de não lidas no cabeçalho do painel | ao carregar a página |

A consulta usa `after_id` e só devolve o que é novo, então é uma consulta leve no banco. No
Laravel (depois da entrega) dá para trocar por tempo real de verdade sem mudar as telas.

## Proposta de banco (patch `010_chat.sql`)

```
conversations
  id                      int pk
  venue_id                int  -> venues.id
  user_id                 int  -> users.id   (quem pergunta)
  user_last_read_id       int  null          (última mensagem que a pessoa leu)
  owner_last_read_id      int  null          (última que o responsável leu)
  blocked_at              timestamp null     (responsável bloqueou: a pessoa não envia mais)
  last_message_at         timestamp null
  created_at              timestamp
  unique (venue_id, user_id)

messages
  id                      int pk
  conversation_id         int  -> conversations.id
  sender_id               int  -> users.id
  body                    varchar(1000)
  created_at              timestamp
  index (conversation_id, id)
```

- "Lida" por **última mensagem lida de cada lado**, e não um campo por mensagem: é uma escrita
  só por conversa aberta.
- O responsável é o `owner` da quadra (`venues.user_id`); não precisa guardar de novo.
- Denúncia: nesta versão, o bloqueio resolve. Denúncia com moderação fica para depois.

## Proposta de contrato (para revisar antes de entrar no `api.md`)

Todas as rotas exigem token. Quem não faz parte da conversa recebe **`404`** (não revela que ela
existe).

### `GET /conversations`

Conversas da pessoa logada, a mais recente primeiro.

```json
{
  "data": [
    {
      "id": 12,
      "venue": { "id": 8, "name": "Quadra do Xuxa", "cover_image_url": "https://..." },
      "last_message": { "id": 340, "body": "Tem horário sábado?", "mine": true, "created_at": "2026-11-10T19:02:00-03:00" },
      "unread_count": 0,
      "blocked": false
    }
  ]
}
```

### `POST /venues/{id}/conversations`

Abre a conversa com a quadra, ou devolve a que já existe. `201` (nova) ou `200` (já existia), com
o mesmo objeto da lista. Erros: `404 VENUE_NOT_FOUND`; `422 CHAT_OWN_VENUE` (o responsável
tentando falar com a própria quadra).

### `GET /conversations/{id}/messages`

Parâmetros: `after_id` (só as mais novas que esta, para o polling) **ou** `before_id` (as mais
antigas, para rolar para cima); `per_page` de 1 a 50 (padrão 30).

```json
{
  "data": [
    { "id": 341, "body": "Tem sim, das 8h às 12h.", "mine": false, "created_at": "2026-11-10T19:05:00-03:00" }
  ]
}
```

Em ordem crescente de `id`. `mine` evita o app precisar comparar ids de usuário.

### `POST /conversations/{id}/messages`

Corpo `{ "body": "..." }` → `201` com a mensagem criada.

| Erro | Quando |
| :---- | :---- |
| `422 VALIDATION_ERROR`, `errors.body` | Vazio, mais de 1000 caracteres, ou com link (`http`, `www.`, domínio): mesma regra da Comunidade, sem links |
| `403 CHAT_BLOCKED` | O responsável bloqueou a conversa |
| `429 TOO_MANY_ATTEMPTS` | Mais de 20 mensagens por minuto (com `Retry-After`) |
| `404` | Quadra desativada ou conversa que não é sua |

### `POST /conversations/{id}/read`

Corpo `{ "last_message_id": 341 }` → `204`. Zera o `unread_count` daquele lado.

**Fuso:** datas com `-03:00`, como o resto da API (D16).

## Site: caixa de mensagens no painel (João)

- Item **"Mensagens"** no `owner_header.php`, com o número de não lidas.
- Lista de conversas (filtro por quadra quando houver mais de uma), com o nome e a foto da pessoa.
  **Nunca** o e-mail ou outro dado pessoal.
- Conversa com resposta, consultando a cada 10 s, e o botão **Bloquear**.
- As mesmas regras da API (sem links, limite de envio) numa classe só, como a `VenueSocials`.

## App (Rafael)

- `chat/[id]` (hoje vitrine, recebe o id da **quadra**) passa a abrir ou criar a conversa pela API
  e consultar a cada 5 s com `refetchInterval`, parando com o app em segundo plano.
- Lista **"Mensagens"** no Menu, com o número de não lidas.
- Estados: enviando, falhou (tocar para reenviar), bloqueada, quadra desativada, sem internet.
- Mock completo em `src/api/mocks/` (`EXPO_PUBLIC_MOCK_CHAT`), seguindo o contrato.
- Leitor de tela: mensagem nova anunciada (`accessibilityLiveRegion`); cada balão é um elemento
  só, com "Você" ou o nome da quadra, o texto e o horário. Textos nos 7 idiomas.
- O botão do WhatsApp continua igual (D24).

## Notificação push (fase opcional)

Só se o resto estiver pronto antes de 16/11. Sem push, o responsável vê pelo painel e a pessoa
pelo número de não lidas no app.

- Tabela `push_tokens` (`user_id`, `token`, `created_at`) e `POST /me/push-token`.
- Ao gravar uma mensagem, a API avisa o outro lado pelo serviço de push da Expo (uma chamada
  HTTP, sem servidor próprio).
- App: `expo-notifications` e credenciais do Firebase (FCM) no EAS. Não funciona no Expo Go.

## Privacidade (LGPD)

- Termos e Privacidade: as mensagens ficam guardadas para as duas partes; o Kolae não lê nem
  vende; só texto, sem links.
- Conta excluída: as mensagens dela somem das conversas (apagadas junto com a conta).
- Quadra desativada: as conversas ficam só para leitura.

## Ordem de trabalho e datas

| Quando | O quê | Quem |
| :---- | :---- | :---- |
| 06/11 | Aprovar este contrato | os dois |
| 09/11–12/11 | Patch, API, regras e testes | João |
| 09/11–12/11 | App com mock: conversa, lista, estados | Rafael |
| 13/11–15/11 | Caixa de mensagens no painel do site | João |
| 13/11–15/11 | App na API real; teste ponta a ponta (app ↔ painel) pelo 4G | os dois |
| 16/11–17/11 | Push, se sobrar tempo | os dois |
| **18/11** | **Data de corte**; Termos e Privacidade atualizados | |

A API só sobe na `main` junto com o painel (rota nova entra com a sua tela). Até lá, testar no
backend local (`setup.md`).

## Se der errado

- **Até 18/11 não funcionou de ponta a ponta:** o app volta para a vitrine com o aviso "Prévia"
  (a tela já existe), as rotas não sobem para a produção e esta proposta vira "adiada para o
  Laravel", com o motivo no registro abaixo. O WhatsApp (D24) continua resolvendo o aluguel.
- **Funciona, mas o push não:** entrega sem push; fica anotado.
- **HostGator reclamar de carga:** subir os intervalos (10 s na conversa, 60 s na lista).

## Registro do andamento

Anotar aqui cada passo, inclusive o que não deu certo, com a data.

| Data | O quê | Resultado |
| :---- | :---- | :---- |
| 05/11 | Proposta escrita | Esperando aprovação |
