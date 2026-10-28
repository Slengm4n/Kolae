# Kolae — contexto para o Claude

Plataforma web (PHP puro, MVC próprio) que conecta atletas a quadras e partidas. Projeto integrador FATEC. Equipe: João (backend \+ site) e um colega (app mobile React Native \+ Expo).

## Objetivo atual (até 05/12/2026)

Plano A: manter o PHP atual, aplicar hotfixes e consertar/ampliar a `/api/v1` para o app mobile consumir. O Kolae 2.0 em Laravel \+ Sanctum só começa depois da entrega. Não migrar para Laravel agora.

**Modelo do produto (decidido em 28/10/2026 com o Rafael, do app):** a pessoa não cria nem entra em partida pelo app. O dono da quadra (`owner`) agenda as partidas **no site**, para quem alugou o horário. O app mostra o status da quadra (`available`, `in_use`, `closed`), sempre **calculado pela API**, a agenda do dia e um botão "Falar com o dono" (WhatsApp). Isso substitui o plano antigo de `match_participants` e entrar/sair de partida. Avaliações e favoritos são só vitrine no app. O app é React Native + Expo (Rafael) e segue o contrato de `docs/api.md`.

**Limitação de hospedagem:** o InfinityFree responde a clientes que não são navegador com um desafio JavaScript (`/aes.js`) em vez do JSON, então o app **não consegue usar a API da produção** (confirmado em 27/10/2026). Decisão atual (opção C): o app usa o backend rodando no notebook do João (XAMPP) na mesma rede Wi-Fi; ver `docs/setup.md`. Migração para uma hospedagem que aceite API prevista para novembro. O site no navegador funciona normalmente.

- Plano completo: [https\://claude.ai/artifact/JSdR7eYfrkZFTk5qcVAv4R](https://claude.ai/artifact/JSdR7eYfrkZFTk5qcVAv4R)  
- Contrato da API: `docs/api.md` (fonte da verdade; endpoint novo entra lá antes do código)  
- Setup local: `docs/setup.md`  
- Tarefas: issues do GitHub, milestones S1–S6

## Cronograma

| Semana | Backend (João) | Pronto quando |
| :---- | :---- | :---- |
| S1 26/10–01/11 | 3 hotfixes críticos, consertar login da API, padrão JSON | Hotfix em produção |
| S2 02/11–08/11 | Formato Laravel na API; register, forgot-password, GET/PATCH /me, avatar, PUT /me/password; CNPJ → `owner` | Login ponta a ponta no celular |
| S3 09/11–15/11 | /venues com filtros, paginação, status e WhatsApp do dono; /venues/{id}; /sports | Quadra do site aparece no app |
| S4 16/11–22/11 | Horário de funcionamento e partidas agendadas pelo dono **no site**; status calculado; /venues/{id}/schedule | Status muda sozinho no app |
| S5 23/11–29/11 | CSRF, XSS, rate limit, "lembrar de mim", troca de e-mail com código | Congelamento de features 29/11 |
| S6 30/11–05/12 | Só bugs, dados de demo | Entrega 05/12 |

## Estrutura

- `index.php`: front controller, sessão, rotas (web e `/api/v1`)  
- `app/Core`: Router (GET/POST/PUT/PATCH), Database (PDO singleton; sessão do MySQL em `-03:00`), AuthHelper, BaseApiController (JWT, `tokenResponse`, `failIfInvalid`, `getJsonInput`), ApiResponse (saída única da API: `{ data }` / `{ message, code, errors }`, 404/405 e erros do PHP em JSON), ApiFormat (datas ISO 8601 e objeto `user` da API), Mailer (e-mail de redefinição de senha, usado pelo site e pela API), ViewHelper, ImageHelper  
- API: `AuthApiController` (login, register, forgot-password), `MeApiController` (`/me`, avatar, senha), `VenueApiController`, `SportApiController`, `GameApiController` (sai na S4)  
- `app/Controllers`, `app/Models` (métodos estáticos com PDO), `app/views` (PHP \+ Tailwind), `lang/` (i18n)  
- Banco: desenho em `database/kolae.dbml` (dbdiagram, fonte da verdade); estrutura pronta em `database/schema.sql`. Mudança de banco = editar o DBML + criar `database/patches/NNN_*.sql` (idempotente, testado no local) e aplicar na produção pelo phpMyAdmin. O Phinx foi removido do projeto (v1.4.0).  
- Dependências: `vendor/` não é versionado (o deploy roda `composer install`). O `composer.json` fixa a plataforma em PHP 8.2, a versão do servidor; manter assim mesmo usando o PHP 8.4 do Herd.  
- Ambiente local: XAMPP com junction `C:\xampp\htdocs\kolae` → repositório. Apache na porta 8080 (`http://localhost:8080/kolae`), MySQL na 3306, banco `kolae_db` (cópia da produção de 27/10/2026 + patches 001/002). `config.php` local não versionado (tem o bloco `SMTP_*` com o host do Gmail; e-mail e senha de app ficam por conta do João). `Database.php` ignora `DB_PORT`.  
- Deploy: push na `main` → GitHub Actions → FTP para InfinityFree (`https://kolae.gamer.gd`). Cuidado: `main` vai direto para produção.
- Git: o terminal do Claude usa as credenciais salvas no Windows (desde 27/10 o `push` funciona daqui). Ainda assim, push, apagar branch remota e sincronizar a `develop` só com pedido do João. O `gh` (GitHub CLI) não está instalado: PR é aberto pelo navegador.
- Uploads: `uploads/` é ignorado no git, exceto `uploads/.htaccess` (bloqueia scripts e só libera jpg/png/gif). Todo upload de imagem passa por `ImageHelper::validateUpload()`, que valida o conteúdo real e devolve a extensão segura.
- PHP: o XAMPP roda PHP 8.2 (o que serve o site); no PATH há PHP 8.4 do Laravel Herd, que serve para `php -l` e `composer`. Não usar o Herd para servir o site (vira modo produção).

## Status (atualizado em 28/10/2026)

- **S1 concluída e lançada como v1.4.0** (27/10/2026), tudo na `main` e em produção: hotfixes 1–3 (PR #30), banco alinhado ao DER + editar quadra (PR #31), login da API + padrão JSON + `docs/api.md` (PR da `fix/api-login`), IP de rede local + `docs/setup.md` (PR #32), organização do repositório e versionamento (PR #33). Patches 001 e 002 aplicados na produção. `uploads/` da produção conferida por FTP em 27/10: só imagens.
- Depois da v1.4.0 (em `[Não lançado]` no CHANGELOG, vão para a v1.5.0): chaves de tradução faltando (PR #34), admin volta para o painel de admin ao editar/criar quadra (PR #35), bloco SMTP no `config.example.php` e extensões do PHP no setup (PR #36).
- Pendente:
  - Avisar o colega do app: login pronto, contrato em `docs/api.md`, usar a API local (opção C).
- S2 do backend pronta na branch `feat/s2-api` (contrato aprovado pelo Rafael em 28/10 + implementação, num PR só para a `main`; o PR da `feat/s2-contrato-api` pode ser fechado sem merge): formato Laravel, register, forgot-password, GET/PATCH /me (só nome), avatar, PUT /me/password, CNPJ → owner (patch 003), fuso corrigido. Testes de ponta a ponta passando no local. Token de 30 dias em `BaseApiController::TOKEN_TTL_SECONDS`, senha mínima em `User::PASSWORD_MIN_LENGTH`.
- Deploy da v1.5.0: aplicar o patch 003 na produção (antes ou junto) e avisar o Rafael, porque o formato da API muda.
- XAMPP do João está com a extensão GD desligada (`extension=gd` no `php.ini`, ver `docs/setup.md`): uploads funcionam, mas sem otimizar a imagem.
- Risco de prazo: o novo modelo traz telas novas no site para o dono (horário de funcionamento, agendar partidas, WhatsApp) na S3/S4, e o status depende de resolver o fuso das datas (item 10).

## Alinhamento com o app (CLAUDE.md do Rafael, 28/10/2026)

Comparação entre o contrato que o app espera e o `docs/api.md`. **Revisado pelo Rafael em 28/10/2026**: contrato da S2 aprovado; os ajustes do lado do app (piso com as chaves da API, conversão `icon_path` Font Awesome → MaterialCommunityIcons, mock separado por área, senha mínima 8, validação local dos 18 anos, deslogar só nos erros de token, tipos novos, PATCH /me e avatar) ficam com ele.

**Igual ao app**
- Formato Laravel: `{ data }` no sucesso e `{ message, errors: { campo: [...] } }` no erro, com status 401/403/422 corretos.
- Datas ISO 8601 com fuso, dinheiro em centavos, imagens como URL completa, `Authorization: Bearer`.
- Rotas e corpos da S2: `POST /auth/login`, `POST /auth/register` (devolve `{ token, user }`), `POST /auth/forgot-password` (mesma resposta exista ou não o e-mail), `GET /me`, `PATCH /me` (só `name`), `POST /me/avatar` (multipart `avatar`), `PUT /me/password`.
- Objeto `user`: `id, name, email, birthdate, role, avatar_url, created_at`; `role` = `admin` | `owner` | `player`.
- Modelo do produto: status `available` / `in_use` / `closed` calculado pela API, `status_until`, agenda do dia em `/venues/{id}/schedule`, filtros e paginação em `/venues`, avaliações e favoritos só como vitrine.

**Diferente do app (o app precisa ajustar ou só saber)**
- Erros trazem também `code` (ex: `VALIDATION_ERROR`, `AUTH_TOKEN_EXPIRED`), um extra que o app pode usar ou ignorar.
- Login e cadastro devolvem também `token_type` e `expires_at`, além de `token` e `user`.
- Paginação segue o Laravel: `{ data: [...], meta: { current_page, last_page, per_page, total } }`.
- Sem conteúdo, a resposta é `{ "data": null }` (o app esperava "vazio").
- Troca de e-mail com código (`POST /me/email` e `/me/email/confirm`) foi para a **S5**: na S2 do app, essa tela fica no mock.
- `GET /venues` continua no formato antigo até a **S3** (na S2 só muda o envelope); `/venues/{id}` chega na S3 e `/venues/{id}/schedule` na S4.
- `POST /games` sai da API (partidas só pelo site).
- Avatar: JPG, PNG ou GIF de até 10 MB.

**Decidido em 28/10/2026 (João e Rafael)**
- Token de 30 dias, sem renovação.
- Senha mínima de 8 caracteres. Na API já vale; **o site ainda aceita qualquer tamanho** (cadastro, redefinir e trocar senha): alinhar, com as mensagens nos 7 idiomas.
- `birthdate` **não é editável** pelo app nem pelo site; `PATCH /me` com `birthdate` responde 422.
- Deslogar só nos erros de token (`AUTH_*`); `INVALID_CREDENTIALS` é só senha errada no login.
- O primeiro APK de teste (S2 do app) usa a API local em `http://`: liberar tráfego HTTP no build.

**Para o contrato da S3 (antes de implementar)**
- `GET /venues/{id}` com o endereço em texto e um objeto `owner: { name, avatar_url, member_since, whatsapp }` no lugar de `owner_whatsapp` solto.
- WhatsApp do dono: coluna nova em `users` (patch) e campo no site.
- Futuro, sem data: mensagens de erro da API no idioma do `Accept-Language` (o app já envia).

## Problemas conhecidos (prioridade)

1. ~~CRÍTICO: `VenueController::handleImageUploads` não valida o arquivo (permite .php).~~ Corrigido (em produção).  
2. ~~CRÍTICO: `SMTPDebug = 2` em `AuthController::handleForgotPassword`.~~ Corrigido (em produção).  
3. ~~CRÍTICO: IDOR em `VenueController::update` (`delete_images[]` e `address_id` do POST).~~ Corrigido (em produção); usa o `address_id` da quadra no banco e só apaga imagens com `venue_id` da própria quadra. Testado ponta a ponta no local em 27/10.  
4. ~~API: `AuthApiController` usa `$user['password_hash']`, a coluna é `password`.~~ Corrigido (em produção); login devolve token + usuário.  
5. API: `Game::create` insere `duration_minutes`, a tabela `matches` tem `end_time`. O `POST /api/v1/games` sai com o novo modelo (partidas só pelo site); a tabela `matches` é revista na S4.  
6. ~~`Venue::findById` lê `a.street`, mas o schema normaliza ruas.~~ Corrigido (em produção; também carrega as fotos na edição e mantém lat/lng quando a geocodificação falha).  
7. Sem CSRF; `json_encode` em `<script>` sem `JSON_HEX_TAG` em admin/map.php (venues/edit.php já corrigido).  
8. "Lembrar de mim": login grava `users.remember_token`, `AuthHelper::checkRememberMe` lê `user_tokens` (selector:validator). A tabela `user_tokens` é criada pelo patch 001; falta o login passar a gravar nela (S5) e depois remover `users.remember_token`.
9. Valores padronizados (ver `kolae.dbml`): `venues.status` = available/unavailable/maintenance; `venues.floor_type` = chaves de `Venue::FLOOR_TYPES`; `matches.status` default `scheduled`. A API devolve as chaves de piso; o app traduz.
10. ~~Fuso das datas: a produção parece devolver UTC e o local, horário de Brasília.~~ Corrigido em `feat/s2-api`: o `Database` fixa a sessão do MySQL em `-03:00` (offset fixo; o InfinityFree pode não ter tabelas de fuso). Depois do deploy, as datas exibidas no site da produção passam a sair no horário de Brasília (3 horas a menos que antes).
11. `GET /api/v1/venues` não filtra por `status` (mostra quadras indisponíveis). Resolver na S3 junto com os filtros.
12. Tradução (`lang/*.php`, 7 idiomas): toda chave nova entra nos 7 arquivos. Ficou para depois da entrega (decidido em 28/10/2026): traduzir o painel de admin (`admin/*`, `sports/*`, `venues/index.php`, `errors/403` e `404` estão com texto fixo em português) e limpar ~43 chaves sem uso.
13. Edição de quadra no mobile não tem botão "Cancelar" (só o "Salvar" fixo embaixo). Polimento para a S6, se sobrar tempo.

## Convenções

- PHP 8.2, PSR-4 `App\\` → `app/`. SQL sempre com prepared statements.  
- Escapar toda saída nas views com `htmlspecialchars`.  
- Respostas da API (a partir da v1.5.0, padrão Laravel): sucesso `{ "data": ... }`; erro `{ "message", "code", "errors": { "campo": ["..."] } }` (`errors` só no 422). Datas ISO 8601 com fuso, dinheiro em centavos (`_cents`), coordenadas numéricas, booleanos `true/false`. Detalhes em `docs/api.md`.  
- `role`: `owner` = quem cadastrou CNPJ no site (decidido em 28/10); cadastro pelo app ou site começa como `player`.  
- Branch por tarefa (`feat/...`, `fix/...`, `docs/...`, `chore/...`), PR para `main` (deploy), revisão do outro membro. A `develop` é só espelho: depois de cada merge, `git push origin main:develop`.  
- Versões: SemVer, tags `vX.Y.Z`. Uma minor por sprint (S1 = v1.4.0, S2 = v1.5.0, ...), patch para hotfix, v2.0.0 reservada para o Laravel. Durante a sprint, registrar mudanças em `## [Não lançado]` do `CHANGELOG.md`. No fechamento: mover para a nova versão, atualizar `version` do `package.json` (e a raiz do `package-lock.json`), criar a tag no commit da `main` e a release no GitHub com o texto do CHANGELOG. O `composer.json` não tem `version`. Tags antigas irregulares (`1.2.0`, `v1.3`) ficam como estão; não reescrever.  
- Responder e comentar em português.