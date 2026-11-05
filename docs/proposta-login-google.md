# Proposta: entrar com Google

**Situação:** proposta (05/11/2026). Decidido fazer antes do congelamento, se der tempo; vira a
**D61** quando o contrato abaixo for aprovado pelo João e pelo Rafael.
**Esforço:** M (3 a 5 dias somando os dois). **Data de corte: 12/11** (ver "Se der errado").
**Versão:** funcionalidade nova depois da v1.6.0, sai na **v1.7.0** (D59).

## O que é

Um botão **"Entrar com Google"** no login e no cadastro do app (e, se sobrar tempo, do site). A
pessoa escolhe a conta Google e entra sem digitar senha. Se for a primeira vez, o app pede só a
**data de nascimento** (18 anos ou mais, D08), porque o Google não informa a idade.

**Por quê:** cadastro em dois toques, menos senha esquecida, e na banca mostra um app "de
verdade". Apple fica de fora: a v1 é só APK para Android (decidido em 05/11).

## Como funciona (resumo)

```
App                      Google                      API (kolae.online)
 │ toca "Entrar com Google"  │                              │
 │──────────────────────────>│ escolhe a conta              │
 │<──────── id_token ────────│ (JWT assinado pelo Google)   │
 │──────────── POST /auth/google { id_token } ─────────────>│ confere assinatura, aud, exp
 │<──────────── token do Kolae (igual ao login) ────────────│ acha, vincula ou pede a data
```

O app **nunca** manda senha nem dados do Google por conta própria: só o `id_token`. Quem confere
se ele é verdadeiro é a API, com as chaves públicas do Google.

## Proposta de banco (patch `009_login_google.sql`)

| Coluna | Tipo | O que guarda |
| :---- | :---- | :---- |
| `users.google_sub` | `VARCHAR(255) NULL UNIQUE` | O id fixo da pessoa no Google (`sub` do token). `NULL` = nunca entrou com Google |

- `users.birthdate` já aceita `NULL` no banco, mas o cadastro pelo Google **exige** a data, como
  o cadastro normal.
- `users.password` é `NOT NULL`: conta criada pelo Google ganha um hash de senha aleatória
  (como o `UserController` já faz em outro fluxo). Para ter senha, a pessoa usa "Esqueci a senha".

## Proposta de contrato (para revisar antes de entrar no `api.md`)

### `POST /auth/google`

Corpo:

```json
{ "id_token": "eyJhbGciOi...", "birthdate": "2004-05-17" }
```

`birthdate` só é preciso na primeira vez (conta nova).

A API confere o token com `firebase/php-jwt` (já instalado) e as chaves de
`https://www.googleapis.com/oauth2/v3/certs` (guardadas em cache pelo tempo que o Google manda):
assinatura, `iss` (`accounts.google.com` ou `https://accounts.google.com`), `aud` igual ao
**ID de cliente Web** do Kolae, `exp`, e `email_verified = true`.

| Caso | Resposta |
| :---- | :---- |
| Já entrou com Google antes (`google_sub` encontrado) | `200` com o mesmo corpo do login (`token`, `token_type`, `expires_at`, `user`) |
| Primeira vez, mas o e-mail já tem conta no Kolae | Vincula (`google_sub` gravado; `email_verified_at` preenchido se estava vazio) e `200` igual ao login |
| Conta nova, **sem** `birthdate` | `422`, `code: "GOOGLE_BIRTHDATE_REQUIRED"`, `errors.birthdate`. O app mostra a roleta e reenvia o mesmo `id_token` com a data |
| Conta nova, com `birthdate` válida (18+) | Cria como `player`, nome do Google, e `201` igual ao cadastro |
| Conta nova, `birthdate` inválida ou menor de 18 | `422`, `code: "VALIDATION_ERROR"`, `errors.birthdate` (mesma mensagem do cadastro) |
| Token falso, vencido ou de outro app | `401`, `code: "GOOGLE_INVALID_TOKEN"` |
| Conta do Kolae inativa ou suspensa | `403`, `code: "ACCOUNT_DISABLED"` |
| Erro ao falar com o Google ou no banco | `500`, `code: "GOOGLE_LOGIN_FAILED"` |

O `id_token` vale 1 hora: dá tempo de a pessoa escolher a data e reenviar.

**Não muda:** o formato do `user`, o token de 30 dias (D07), o "Lembrar de mim" e os erros de
token (D10). O vínculo pelo e-mail só acontece porque o Google garante o e-mail
(`email_verified`).

## O que precisa fora do código

1. **Google Cloud** (projeto "Kolae", conta do João):
   - Tela de consentimento OAuth: nome Kolae, logo, e-mail de suporte, links dos Termos e da
     Privacidade do site. Escopos só `openid`, `email` e `profile` (não pedem verificação do
     Google). Publicar como "Em produção"; em "Teste" só entram até 100 e-mails cadastrados.
   - **ID de cliente Web**: é o `aud` que a API confere, e o que o app manda como `webClientId`.
   - **ID de cliente Android**: pacote do app + **SHA-1 da keystore do EAS** (o mesmo SHA-1 da
     chave do Google Maps). Sem ele o Google devolve `DEVELOPER_ERROR`.
2. **Servidor:** `GOOGLE_WEB_CLIENT_ID` no `config.php` (pelo cPanel; fica fora do git).
3. **App:** `EXPO_PUBLIC_GOOGLE_WEB_CLIENT_ID` e `EXPO_PUBLIC_GOOGLE_LOGIN` (`false` esconde o botão)
   no `.env`/EAS.

## App (Rafael)

- Biblioteca: `@react-native-google-signin/google-signin` (login nativo, com plugin do Expo).
  Conferir a compatibilidade com o SDK 58 antes de instalar (`npx expo install`).
- **Não roda no Expo Go:** precisa de um build do EAS (APK `preview` ou `development`). No Expo Go
  o botão fica escondido, para o resto do app continuar testável.
- Botão "Entrar com Google" no login e no cadastro (`AuthScaffold`), no padrão de marca do Google.
- Tela "Falta pouco": a roleta de data (`DateField`) quando vier `GOOGLE_BIRTHDATE_REQUIRED`.
- Mock de `POST /auth/google` com os casos da tabela (`EXPO_PUBLIC_MOCK_AUTH`).
- Textos nos 7 idiomas.
- Conta só do Google em "Alterar senha": aviso para usar "Esqueci a senha" primeiro.

## Site (João)

1. Patch 009, `App\Core\GoogleIdToken` (confere o token) e `AuthApiController::google()`, com
   teste em `tests/` (token falso, `aud` errado, vencido, conta nova sem data, vínculo por e-mail).
2. Contrato no `api.md` e a D61 em `decisoes.md`.
3. **Opcional (+1 dia):** botão "Entrar com Google" no login do site (Google Identity Services),
   usando a mesma `GoogleIdToken`.

## Ordem de trabalho e datas

| Quando | O quê | Quem |
| :---- | :---- | :---- |
| 06/11 | Aprovar este contrato; criar o projeto no Google Cloud e os dois IDs | os dois |
| 06/11–08/11 | API, patch e testes (sobe na `main` só junto com o app pronto para testar) | João |
| 06/11–08/11 | App com mock: botão, tela da data, erros | Rafael |
| 09/11–10/11 | Build do EAS e teste ponta a ponta pelo 4G (conta nova, conta existente, menor de 18) | os dois |
| **12/11** | **Data de corte** | |

## Se der errado

- **Até 12/11 não funcionou de ponta a ponta:** o botão sai do app (`EXPO_PUBLIC_GOOGLE_LOGIN=false`),
  a rota fica na API sem uso e esta proposta vira "adiada", com o motivo anotado no registro
  abaixo. O login por e-mail e senha não é tocado em nenhum momento.
- **Problemas conhecidos:** `DEVELOPER_ERROR` no app (SHA-1 ou pacote errado no ID Android);
  `aud` diferente na API (app usando o ID Android em vez do Web como `webClientId`); horário do
  servidor fora (o `exp` falha; dar 60 s de folga).

## Registro do andamento

Anotar aqui cada passo, inclusive o que não deu certo, com a data.

| Data | O quê | Resultado |
| :---- | :---- | :---- |
| 05/11 | Proposta escrita | Esperando aprovação |
