# Kolae — contexto para o Claude

Plataforma web (PHP puro, MVC próprio) que conecta atletas a quadras e partidas. Projeto integrador FATEC. Equipe: João (backend \+ site) e um colega (app mobile React Native \+ Expo).

## Objetivo atual (até 05/12/2026)

Plano A: manter o PHP atual, aplicar hotfixes e consertar/ampliar a `/api/v1` para o app mobile consumir. O Kolae 2.0 em Laravel \+ Sanctum só começa depois da entrega. Não migrar para Laravel agora.

- Plano completo: [https\://claude.ai/artifact/JSdR7eYfrkZFTk5qcVAv4R](https://claude.ai/artifact/JSdR7eYfrkZFTk5qcVAv4R)  
- Contrato da API: `docs/api.md` (fonte da verdade; endpoint novo entra lá antes do código)  
- Setup local: `docs/setup.md`  
- Tarefas: issues do GitHub, milestones S1–S6

## Cronograma

| Semana | Backend (João) | Pronto quando |
| :---- | :---- | :---- |
| S1 26/10–01/11 | 3 hotfixes críticos, consertar login da API, padrão JSON | Hotfix em produção |
| S2 02/11–08/11 | register, /me, avatar, forgot-password | Login ponta a ponta no celular |
| S3 09/11–15/11 | /venues com filtros, /venues/{id}, /sports | Quadra do site aparece no app |
| S4 16/11–22/11 | match\_participants, /games, entrar/sair | Partida completa pelo app |
| S5 23/11–29/11 | CSRF, XSS, rate limit, "lembrar de mim" | Congelamento de features 29/11 |
| S6 30/11–05/12 | Só bugs, dados de demo | Entrega 05/12 |

## Estrutura

- `index.php`: front controller, sessão, rotas (web e `/api/v1`)  
- `app/Core`: Router, Database (PDO singleton), AuthHelper, BaseApiController (JWT), ViewHelper, ImageHelper  
- `app/Controllers`, `app/Models` (métodos estáticos com PDO), `app/views` (PHP \+ Tailwind), `lang/` (i18n)  
- Banco: desenho em `database/kolae.dbml` (dbdiagram, fonte da verdade); estrutura pronta em `database/schema.sql`. Mudança de banco = editar o DBML + criar `database/patches/NNN_*.sql` (idempotente, testado no local) e aplicar na produção pelo phpMyAdmin. As migrations Phinx estão abandonadas (não usar).  
- Ambiente local: XAMPP com junction `C:\xampp\htdocs\kolae` → repositório. Apache na porta 8080 (`http://localhost:8080/kolae`), MySQL na 3306, banco `kolae_db` (cópia da produção de 27/10/2026 + patches 001/002). `config.php` local não versionado. `Database.php` ignora `DB_PORT`.  
- Deploy: push na `main` → GitHub Actions → FTP para InfinityFree. Cuidado: `main` vai direto para produção.
- Uploads: `uploads/` é ignorado no git, exceto `uploads/.htaccess` (bloqueia scripts e só libera jpg/png/gif). Todo upload de imagem passa por `ImageHelper::validateUpload()`, que valida o conteúdo real e devolve a extensão segura.
- PHP: o XAMPP roda PHP 8.2 (o que serve o site); no PATH há PHP 8.4 do Laravel Herd, que serve para `php -l` e `composer`. Não usar o Herd para servir o site (vira modo produção).

## Status (atualizado em 27/10/2026)

- Hotfix S1 (itens 1–3 + upload de avatar) **em produção** desde 27/10/2026 (PR #30, `fix/hotfixes-criticos-s1` → `main`).
- Branches empilhadas, ainda não mergeadas: `fix/banco-divergencias` (DER/patches/floor_type, publicada) → `fix/editar-quadra` (item 6). Um PR só das duas para `main`, com o patch 002 no deploy.
- Pendente na S1:
  - Sincronizar `develop`, que está atrás da `main`.
  - Conferir por FTP se já existem arquivos não-imagem em `uploads/` na produção (o `.htaccess` impede a execução, mas não os apaga).
  - Itens 4 (login da API) e padrão JSON.
  - Banco: aplicar `database/patches/001` na produção (pode ir antes de qualquer deploy) e o `002` junto com o deploy do código de `Venue::FLOOR_TYPES`.

## Problemas conhecidos (prioridade)

1. ~~CRÍTICO: `VenueController::handleImageUploads` não valida o arquivo (permite .php).~~ Corrigido (em produção).  
2. ~~CRÍTICO: `SMTPDebug = 2` em `AuthController::handleForgotPassword`.~~ Corrigido (em produção).  
3. ~~CRÍTICO: IDOR em `VenueController::update` (`delete_images[]` e `address_id` do POST).~~ Corrigido (em produção); usa o `address_id` da quadra no banco e só apaga imagens com `venue_id` da própria quadra. Testado ponta a ponta no local em 27/10.  
4. API: `AuthApiController` usa `$user['password_hash']`, a coluna é `password`.  
5. API: `Game::create` insere `duration_minutes`, a tabela `matches` tem `end_time`.  
6. ~~`Venue::findById` lê `a.street`, mas o schema normaliza ruas.~~ Corrigido em `fix/editar-quadra` (também passou a carregar as fotos na edição e a manter lat/lng quando a geocodificação falha).  
7. Sem CSRF; `json_encode` em `<script>` sem `JSON_HEX_TAG` em admin/map.php (venues/edit.php já corrigido em `fix/editar-quadra`).  
8. "Lembrar de mim": login grava `users.remember_token`, `AuthHelper::checkRememberMe` lê `user_tokens` (selector:validator). A tabela `user_tokens` é criada pelo patch 001; falta o login passar a gravar nela (S5) e depois remover `users.remember_token`.
9. Valores padronizados (ver `kolae.dbml`): `venues.status` = available/unavailable/maintenance; `venues.floor_type` = chaves de `Venue::FLOOR_TYPES`; `matches.status` default `scheduled`. A API devolve as chaves de piso; o app traduz.

## Convenções

- PHP 8.2, PSR-4 `App\\` → `app/`. SQL sempre com prepared statements.  
- Escapar toda saída nas views com `htmlspecialchars`.  
- Respostas da API: `{ "success": true, "data": ... }` / `{ "success": false, "error": { "message", "code", "fields" } }`.  
- Branch por tarefa (`feat/...`, `fix/...`), PR para `develop`, revisão do outro membro.  
- Responder e comentar em português.