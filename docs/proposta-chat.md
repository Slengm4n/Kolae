# Proposta: chat entre a pessoa e o responsável pela quadra

**Situação:** **concluída e na produção em 10/11/2026** (PRs #87, #88 e #89), testada de ponta a ponta
com o app pelo João. Falta só a fase opcional de notificação no celular (seção "Notificação no
app"). Aprovada em 09/11/2026: é a **D66**. O app do Rafael já foi feito seguindo este
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

## Notificação no app (fase opcional, a fazer)

**Situação em 10/11:** o chat funciona sem notificação. A pessoa vê as respostas pelo número de
não lidas (Menu › Mensagens, consulta a cada 30 s) e o dono pelo número no cabeçalho do painel.
Esta fase faz o **celular avisar** quando o dono responde, mesmo com o app fechado. Entra só se
sobrar tempo antes do congelamento (21/11) e depois de o contrato abaixo ser aprovado pelos dois.

**Para quem é:** só para **quem pergunta** (no app). O dono responde pelo site; o aviso de
mensagem nova para ele vem com o sino da D64 (e, se combinado, por e-mail).

### O que o Rafael precisa fazer (app)

1. **Instalar** `expo-notifications` e `expo-device` (`npx expo install`, conferir a versão do SDK)
   e o plugin no `app.json`.
2. **Firebase (Android):** criar o projeto no Firebase, baixar o `google-services.json` e subir a
   chave do FCM no EAS (`eas credentials`). **Não funciona no Expo Go:** testar num build do EAS
   (APK `preview` ou `development`).
3. **Pedir permissão** depois do login (no Android 13+ é obrigatório, `POST_NOTIFICATIONS`). Se a
   pessoa negar, o app segue normal, só sem aviso.
4. **Pegar o token** com `getExpoPushTokenAsync({ projectId })` e mandar para a API
   (`POST /me/push-token`, abaixo). Mandar de novo quando o token mudar e a cada login.
5. **No logout**, chamar `DELETE /me/push-token` com o mesmo token, para o celular não receber
   aviso de outra conta.
6. **Canal Android** `mensagens` (nome "Mensagens", importância alta), com os textos nos 7 idiomas.
7. **Ao tocar no aviso:** abrir a conversa. O aviso traz em `data`:
   `{ "type": "chat_message", "conversation_id": 12, "venue_id": 8 }` (abrir `chat/[venue_id]`).
8. **Com a conversa aberta**, não mostrar o aviso (`setNotificationHandler`): a mensagem já aparece
   pela consulta de 5 s.
9. **Mock** da rota em `src/api/mocks/` e testar: permissão negada, sem internet, logout, duas
   contas no mesmo celular.

### O que o João faz (site e API)

1. Patch com o próximo número livre: tabela `push_tokens` (`user_id`, `token` único,
   `platform`, `created_at`, `last_used_at`), com a mesma proteção de banco errado do 010.
2. Rotas (com token): `POST /me/push-token` `{ "token": "ExponentPushToken[...]", "platform": "android" }`
   → `204` (o mesmo token em outra conta passa para a conta nova) e `DELETE /me/push-token`
   `{ "token": "..." }` → `204`. Erro: `422` se o token não tiver o formato da Expo.
3. Quando o dono responde no painel, avisar os celulares de quem perguntou pelo serviço da Expo
   (`https://exp.host/--/api/v2/push/send`, uma chamada HTTP com tempo curto). Se falhar, a
   mensagem continua gravada e a resposta do painel não espera. Token que a Expo disser que não
   existe mais (`DeviceNotRegistered`) é apagado.
4. Texto do aviso: título = nome da quadra; corpo = começo da mensagem (até 100 caracteres).
5. Contrato no `api.md`, Política de Privacidade (o token do celular e o envio pela Expo e pelo
   Google Firebase) e testes.

**Esforço:** M (2 a 3 dias somando os dois). **Se não der tempo:** fica como está (sem aviso no
celular), anotado neste registro.

## Privacidade (LGPD)

**Feito em 10/11** (Termos de Uso e Política de Privacidade com data 10/11/2026):

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

## Situação final (10/11)

- **Na produção:** as quatro partes (A #87, B #88, C e D #89) e o patch 010. Testado de ponta a
  ponta com o app pelo João em 10/11. Termos e Privacidade atualizados.
- **Fica para depois (opcional):** notificação no celular (seção "Notificação no app"),
  "carregar mensagens anteriores" na conversa do painel (hoje mostra as 50 últimas) e aviso por
  e-mail ou pelo sino da D64 para o dono.

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
| 09/11 | **Parte D** (`feat/chat-api`): **Mensagens** no painel do dono (`ChatController`, telas `chat/index` e `chat/show`): item no cabeçalho com as não lidas (`Conversation::unreadForOwner`), lista com filtro por quadra (sem as excluídas), conversa com balões por dia, resposta pelo `fetch` (sem JavaScript, o formulário funciona com redirecionamento), consulta a cada 10 s só com a aba visível, bloquear e desbloquear, quadra desativada só leitura (409 no envio direto). Nome e foto de quem pergunta, nunca o e-mail; o admin não lê conversas. Enter envia só com teclado de verdade (no celular, pula linha). Avisos no contexto `chat` do `Flash::MAP`, 29 textos nos 7 idiomas. Teste `tests/e2e/testes/chat_painel.js` (26 passos). **O que deu errado:** o teste não conseguia clicar em "Desbloquear" porque o aviso "Conversa bloqueada." fica por cima do botão (o mesmo do teste do plano); o teste fecha o aviso antes. Neste PC, o Edge dos testes só abre pelo PowerShell (pelo Git Bash ele fecha na hora) | Próximo: PR das partes C e D; teste com o Rafael |
| 10/11 | **Partes C e D na `main`** (PR #89, feito no PC de casa na noite de 09/11): deploy com os testes passando no Actions. Conferido no PC da escola: os 362 casos de PHP e os testes de clique do chat (`chat_api` 46, `chat_painel` 26) e do cabeçalho do dono passando; produção respondendo nas rotas do chat (`/api/v1/conversations` dá 401 sem token, como no contrato) | ✅ |
| 10/11 | **Teste de ponta a ponta com o app**, pelo João: mensagem pelo app, resposta pelo painel e resposta chegando no app | ✅ Funcionou |
| 10/11 | **Termos de Uso e Política de Privacidade** atualizados (`docs/chat-legal`, data 10/11/2026): mensagens (só texto, sem links nem e-mails, limite, bloqueio, quem vê, guarda e exclusão), e também o Kolae Premium, os anúncios "Patrocinado" e as estatísticas (código do dia, sem IP), que já estavam no ar e não apareciam nos documentos. Notificação no app documentada para o Rafael. **O que deu errado:** a linha da parte D tinha ficado fora da tabela deste registro (aparecia quebrada no GitHub) e foi movida. O teste de clique `entrada` falha em 2 passos do botão de tema também na `main`, sem relação com o chat (fica para conferir à parte) | Chat concluído; falta só a notificação no app (opcional) |

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
