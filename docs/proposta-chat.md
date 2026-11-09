# Proposta: chat entre a pessoa e o responsável pela quadra

**Situação:** **aprovada em 09/11/2026: é a D66.** O app do Rafael já foi feito seguindo este
contrato (com mock), e as 5 dúvidas dele foram respondidas pelo João (seção "Respostas às dúvidas
do app"). Contrato final no [`api.md`](api.md#chat-v170-d66). Muda a **D04** em parte (o chat
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

## Banco (patch `010_chat.sql`)

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

**Partes do site (João), uma PR cada, empilhadas:** **A** documentação (estas respostas e o
contrato no `api.md`) → **B** banco e regras, sem rota (patch `010_chat.sql` aplicado na produção
antes do merge) → **C** API → **D** Mensagens no painel do dono. **C e D vão para a `main` juntas**,
para o jogador nunca mandar mensagem sem o dono ter onde ler.

## Se der errado

- **Até 18/11 não funcionou de ponta a ponta:** o app volta para a vitrine com o aviso "Prévia"
  (a tela já existe), as rotas não sobem para a produção e esta proposta vira "adiada para o
  Laravel", com o motivo no registro abaixo. O WhatsApp (D24) continua resolvendo o aluguel.
- **Funciona, mas o push não:** entrega sem push; fica anotado.
- **HostGator reclamar de carga:** subir os intervalos (10 s na conversa, 60 s na lista).

## Onde parei (09/11, fim do dia)

- **Na `main` e na produção:** partes A (#87, contrato) e B (#88, banco e regras). Patch 010
  aplicado e conferido na produção. Nada do chat aparece ainda para quem usa o site ou o app.
- **Só no PC do João, sem push:** branch `feat/chat-api` com a correção do patch (`5cac54e`) e
  a parte C, a API (`b438ac9`, teste `chat_api` com 46 passos passando).
- **Próximo: parte D, "Mensagens" no painel do dono** (na mesma branch ou empilhada nela; C e D
  vão juntas para a `main`):
  1. Item "Mensagens" no `owner_header.php` com o número de não lidas
     (`Conversation::unreadForOwner`), em todas as páginas do dono.
  2. Rotas `/dashboard/mensagens` (lista, `Conversation::forOwner`, filtro por quadra quando o dono
     tem mais de uma), `/dashboard/mensagens/{id}` (conversa; ao abrir, `markRead` do lado
     `owner`), `POST .../{id}` (responder, com `ChatRules::cleanBody` e `Message::waitSeconds`),
     `POST .../{id}/bloquear` e `/desbloquear` (`Conversation::setBlocked`), e um JSON
     `.../{id}/novas?after_id=` para a consulta a cada 10 s.
  3. Mostrar o nome e a foto de quem pergunta, **nunca o e-mail**. Quadra desativada: só leitura.
  4. Textos nos 7 idiomas; avisos pelo `Flash::MAP` (contexto novo); teste de clique
     `tests/e2e/testes/chat_painel.js` e, no fim, um teste ponta a ponta: mensagem pela API →
     aparece no painel → resposta no painel → aparece na API.
  5. CHANGELOG, `api.md` (nada muda no contrato) e este registro. Depois: push das partes C e D e
     o teste com o Rafael pelo app (data de corte: 18/11).

## Registro do andamento

Anotar aqui cada passo, inclusive o que não deu certo, com a data.

| Data | O quê | Resultado |
| :---- | :---- | :---- |
| 05/11 | Proposta escrita | Esperando aprovação |
| 09/11 | Renumerada de D62 para **D66** (D61–D64 foram para o Kolae Premium) e patch sem número fixo (o 009 é o do Premium) | — |
| 09/11 | **App pronto com mock** (Rafael, PR #12 do `kolae-mobile`): as 5 rotas, conversa com polling, Menu › Mensagens, estados de envio, bloqueio e quadra desativada; `EXPO_PUBLIC_MOCK_CHAT=true` até a API subir. Ainda não testado no celular | Falta a API e o painel. O Rafael anotou 5 dúvidas do contrato (abaixo) |
| 09/11 | **Proposta aprovada (D66)**; as 5 dúvidas do app respondidas: todas as suposições do app estavam certas, nada muda no app. Contrato final no `api.md`. Parte A do site (`docs/chat-contrato`) | Próximo: parte B (banco e regras) |
| 09/11 | **Parte B** (`feat/chat-banco`): patch `010_chat.sql` (no `schema.sql` e no DBML), regras em `App\Core\ChatRules` (texto até 1000, sem links, quebras de linha mantidas, parâmetros da lista, limite de 20 por minuto, "última lida" que só avança) e os models `Conversation` e `Message`. Sem rota. Testes: `tests/chat_rules_test.php` (56 casos, sem banco) e `tests/chat_db_test.php` (49, no banco do seed, deixa tudo como encontrou). **O que deu errado:** a primeira versão da regra de links tratava como link frases com o espaço esquecido depois do ponto ("Pode ser.De tarde", "Tá bom.Com certeza"); agora o domínio só conta em minúsculas e os domínios curtos que são palavras em português (`.de`, `.to`, `.es`...) saíram da lista. E-mail na mensagem também conta como link (fica bloqueado, o que protege o dado pessoal). Descoberto ao fazer: **conta excluída no Kolae só fica inativa**, então "as mensagens somem com a conta" não acontecia sozinho; as consultas agora escondem as conversas e as mensagens de conta inativa (e a cascata apaga se a conta for apagada de verdade) | Antes do merge: patch 010 na produção. Próximo: parte C (API) |
| 09/11 | **Patch 010 na produção, 1ª tentativa: erro.** O patch foi rodado com o `information_schema` escolhido (o topo do phpMyAdmin mostrava só "Server: localhost:3306"), e o `CREATE TABLE` falhou ali; nada foi criado nem mudado. O aviso do patch só pegava "nenhum banco". **Correção:** o patch agora só cria as tabelas se o banco escolhido tiver `venues` e `users`; em qualquer outro, mostra "Patch 010 NÃO aplicado" sem erro. Testado em 5 situações (banco certo, de novo, `information_schema`, sem banco, banco vazio). A produção é MySQL 5.7.44 | Rodar de novo no banco `joao5502_kolae` |
| 09/11 | Patch 010 rodado de novo no banco certo: `conversations` e `messages` conferidas na produção (`SHOW TABLES`). Partes A (#87) e B (#88) na `main`; deploy com os testes do chat passando no Actions | ✅ |
| 09/11 | **Parte C** (`feat/chat-api`, local): `ChatApiController` com as 5 rotas do contrato e a correção do patch. Pela API, a pessoa só acessa as conversas em que é quem pergunta (o dono e o admin recebem 404). Teste `tests/e2e/testes/chat_api.js` (46 passos, só HTTP; a resposta do dono é gravada direto no banco até o painel existir). **O que deu errado:** na primeira rodada 3 passos falharam porque o teste esperava `errors.body` como texto; o contrato (e o resto da API) usa lista de textos (`"body": ["..."]`), então o teste estava errado | Não sobe sozinha: vai junto com a parte D (painel) |

### Respostas às dúvidas do app (09/11)

O app assumiu o que estava entre parênteses; **as cinco suposições valem**, então o app não muda.

1. **`last_message` de uma conversa sem mensagens:** `null`. Na prática só aparece na resposta do
   `POST /venues/{id}/conversations`, porque a lista esconde as conversas vazias (item 4).
2. **`GET /conversations/{id}/messages` sem `after_id` nem `before_id`:** as `per_page` mais
   recentes (padrão 30), em ordem crescente de `id`. Os dois juntos: `422 VALIDATION_ERROR`.
3. **Códigos dos 404:** conversa que não é sua (ou que não existe): `404 NOT_FOUND`. Enviar
   mensagem com a quadra desativada: `404 VENUE_NOT_FOUND`. O app pode continuar olhando só o
   status. A conversa de quadra desativada continua na lista e dá para ler; só não dá para enviar.
4. **Conversas vazias:** ficam fora do `GET /conversations` e do painel do dono e não contam como
   não lidas. Não são apagadas: abrir de novo devolve a mesma conversa.
5. **`Retry-After` do 429:** o servidor manda o cabeçalho (em segundos); o app pode ignorar e
   mostrar só a mensagem.

**Também ficou definido:** o dono responde só pelo painel do site (D02). No app, o
`GET /conversations` traz só as conversas em que a pessoa logada é quem pergunta (um dono que
pergunta na quadra de outro vê essa conversa no app). Quem envia já conta como tendo lido a própria
mensagem. O bloqueio pode ser desfeito pelo dono no painel. As mensagens novas para o dono têm o
próprio contador em "Mensagens" no painel; quando o sino da D64 existir, passam a aparecer nele
também.
