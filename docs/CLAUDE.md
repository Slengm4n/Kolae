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
- Uploads: `uploads/` é ignorado no git, exceto `uploads/.htaccess` (bloqueia scripts e só libera jpg/png/gif). Todo upload de imagem passa por `ImageHelper::validateUpload()`, que valida o conteúdo real e devolve a extensão segura.
- Não há PHP instalado na máquina do João: mudanças não são testadas localmente com `php -l`; revisar com cuidado e testar no ambiente dele.

## Status (atualizado em 27/10/2026)

- Branch `fix/hotfixes-criticos-s1` (commit `854a230`, já no GitHub): corrige os itens 1–3 abaixo. Também corrigiu o upload de avatar (`ImageHelper::processAndSave` confiava no `$file['type']` do cliente e na extensão original). Ainda não está em produção.
- Pendente na S1:
  - Decidir o fluxo do hotfix: PR direto para `main` (único jeito de chegar à produção) e depois sincronizar `develop`, que está atrás da `main`.
  - Corrigir o item 6, que provavelmente quebra editar/atualizar quadra e impede testar o item 3.
  - Conferir por FTP se já existem arquivos não-imagem em `uploads/` na produção (o `.htaccess` impede a execução, mas não os apaga).
  - Itens 4 (login da API) e padrão JSON.

## Problemas conhecidos (prioridade)

1. ~~CRÍTICO: `VenueController::handleImageUploads` não valida o arquivo (permite .php).~~ Corrigido na branch `fix/hotfixes-criticos-s1`.  
2. ~~CRÍTICO: `SMTPDebug = 2` em `AuthController::handleForgotPassword`.~~ Corrigido na mesma branch.  
3. ~~CRÍTICO: IDOR em `VenueController::update` (`delete_images[]` e `address_id` do POST).~~ Corrigido na mesma branch; agora usa o `address_id` da quadra no banco e só apaga imagens com `venue_id` da própria quadra.  
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