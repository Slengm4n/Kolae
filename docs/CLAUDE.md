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
- Banco: usar `database/Kolae_db_new.sql`; as migrations Phinx estão fora de sincronia (não confiar nelas)  
- Deploy: push na `main` → GitHub Actions → FTP para InfinityFree. Cuidado: `main` vai direto para produção.

## Problemas conhecidos (prioridade)

1. CRÍTICO: `VenueController::handleImageUploads` não valida o arquivo (permite .php). Validar MIME real, gerar extensão, `.htaccess` em `uploads/` bloqueando PHP.  
2. CRÍTICO: `SMTPDebug = 2` em `AuthController::handleForgotPassword` → 0\.  
3. CRÍTICO: IDOR em `VenueController::update`: `delete_images[]` e `address_id` vindos do POST sem checar dono.  
4. API: `AuthApiController` usa `$user['password_hash']`, a coluna é `password`.  
5. API: `Game::create` insere `duration_minutes`, a tabela `matches` tem `end_time`.  
6. `Venue::findById` lê `a.street`, mas o schema normaliza ruas em `streets/neighborhoods/cities/states`.  
7. Sem CSRF; `json_encode` em `<script>` sem `JSON_HEX_TAG` (admin/map.php, venues/edit.php).  
8. "Lembrar de mim": login grava `users.remember_token`, `AuthHelper::checkRememberMe` lê `user_tokens` (selector:validator).

## Convenções

- PHP 8.2, PSR-4 `App\\` → `app/`. SQL sempre com prepared statements.  
- Escapar toda saída nas views com `htmlspecialchars`.  
- Respostas da API: `{ "success": true, "data": ... }` / `{ "success": false, "error": { "message", "code", "fields" } }`.  
- Branch por tarefa (`feat/...`, `fix/...`), PR para `develop`, revisão do outro membro.  
- Responder e comentar em português.