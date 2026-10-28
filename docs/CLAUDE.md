# Kolae — contexto para o Claude

Plataforma web (PHP puro, MVC próprio) que conecta atletas a quadras particulares. Projeto Integrador FATEC Ferraz de Vasconcelos, entrega em **05/12/2026** (congelamento de funcionalidades em 29/11).

- **João:** backend PHP, `/api/v1` e o site (este repositório).
- **Rafael:** app mobile React Native + Expo (`Thesatiru/kolae-mobile`), que consome a API.

Responder e comentar em português.

## Documentação (ler antes de mudar algo)

O índice está em [`docs/README.md`](README.md). Resumo:

- **`docs/decisoes.md`**: todas as decisões com data e motivo (D01...). Não contrariar uma decisão sem combinar e registrar uma nova.
- **`docs/api.md`**: contrato da API. Endpoint novo ou mudança de formato entra lá **antes** do código, e o Rafael revisa.
- **`docs/modelo-status.md`**: proposta de status da quadra, horário de funcionamento e agenda (S3/S4), **aguardando revisão**.
- **`docs/setup.md`**: ambiente local e conexão do app ao notebook.
- **`database/kolae.dbml`**: banco como ele é hoje. Mudança = DBML + `database/patches/NNN_*.sql`.
- **`CHANGELOG.md`**: mudanças em `[Não lançado]` até o fim da sprint.
- Plano original (pode estar desatualizado; o que vale é `docs/`): https://claude.ai/artifact/JSdR7eYfrkZFTk5qcVAv4R

## Situação em 28/10/2026

- **S1 concluída**, lançada como **v1.4.0**.
- **S2 do backend concluída e em produção**, adiantada (PRs #34–#40): formato Laravel, cadastro, esqueci a senha, perfil (só nome), avatar, troca de senha, `owner` por CNPJ, fuso de Brasília, seletor de papel do admin corrigido. Vira a **v1.5.0** em 08/11.
- **Agora:** revisão do `docs/modelo-status.md` com o Rafael; depois, contrato da S3 em `docs/api.md`.
- **Maior risco de prazo:** as telas do dono no site (horário de funcionamento e agenda) e o cálculo de status, na S4.

### Pendências fora do código
- Trocar a senha do MySQL da produção e, logo em seguida, o `DB_PASS` do `config.php` da produção pelo FTP (senão o site cai). Bloqueado: o phpMyAdmin do InfinityFree (`php-myadmin.net`) está apontando para `127.0.0.1` no DNS público desde 28/10; é falha deles. Patches de banco na produção dependem dele.
- Escolher a hospedagem nova (D13), em novembro.
- Branches antigas no GitHub já mergeadas: `fix/admin-roles`, `feat/s2-api`, `feat/s2-contrato-api`, `docs/changelog-e-status`.

### Do lado do app (Rafael), combinado em 28/10
Piso com as chaves da API; `icon_path` (Font Awesome) convertido para MaterialCommunityIcons; mock separado por área (auth real, quadras no mock até a S3); senha mínima 8; validação local dos 18 anos; deslogar só nos erros `AUTH_*` (D10); data de nascimento só leitura; liberar HTTP no APK.

## Estrutura do código

- `index.php`: front controller, sessão, rotas do site e da `/api/v1`.
- `app/Core`: Router (GET/POST/PUT/PATCH), Database (PDO singleton, sessão MySQL em `-03:00`), AuthHelper, BaseApiController (JWT, `tokenResponse`, `failIfInvalid`, `getJsonInput`), ApiResponse (saída única da API), ApiFormat (datas ISO 8601 e objeto `user`), Mailer (e-mail de redefinição de senha), ViewHelper, ImageHelper (valida o conteúdo real do upload; sem GD, salva sem otimizar).
- API: `AuthApiController` (login, register, forgot-password), `MeApiController` (`/me`, avatar, senha), `VenueApiController`, `SportApiController`, `GameApiController` (sai na S4, D02).
- `app/Controllers`, `app/Models` (métodos estáticos com PDO), `app/views` (PHP + Tailwind), `lang/` (7 idiomas; chave nova vai nos 7 arquivos).
- `uploads/` fora do git, exceto `uploads/.htaccess` (bloqueia scripts).

## Ambiente

- **Local:** XAMPP com junction `C:\xampp\htdocs\kolae` → repositório. Apache na 8080 (`http://localhost:8080/kolae`), MySQL na 3306, banco `kolae_db` (cópia da produção de 27/10 + patches 001–003). PHP 8.2 no XAMPP (com GD); PHP 8.4 do Laravel Herd no PATH só para `php -l` e `composer` (não usar o Herd para servir o site). `config.php` fora do git, com `SMTP_*`.
- **Produção:** InfinityFree, `https://kolae.gamer.gd`. Push na `main` → GitHub Actions → FTP. **A `main` publica direto.**
- **App:** usa o backend do notebook pela rede Wi-Fi (D13): `http://<IP>:8080/kolae/api/v1`.
- **Git:** o push funciona deste terminal (credenciais do Windows), mas push, apagar branch remota e sincronizar a `develop` só com pedido do João. Sem `gh`: PR pelo navegador. Mensagens de commit em arquivo UTF-8 **sem BOM** (o `Set-Content` do PowerShell 5 grava BOM).
- **MySQL local:** ligar uma vez só no painel do XAMPP e parar antes de desligar o PC. Encerramento forçado corrompe os arquivos de replicação (`multi-master.info`, `relay-log*`); se não subir, rodar `mysqld --console` para ver o erro e mover esses arquivos para `C:\xampp\mysql\data_replicacao_backup`.

## Problemas conhecidos em aberto

1. Sem CSRF nos formulários do site (S5); `json_encode` sem `JSON_HEX_TAG` em `admin/map.php` (XSS).
2. "Lembrar de mim": o login grava `users.remember_token`, mas `AuthHelper::checkRememberMe` lê `user_tokens` (S5).
3. Senha mínima de 8 só na API; o site aceita qualquer tamanho (D08).
4. `GET /api/v1/venues` não filtra por `status` e está no formato antigo (S3).
5. `Game::create` grava `duration_minutes`, que não existe em `matches` (some na S4 com o novo modelo).
6. Painel de admin e páginas de erro sem tradução (D19).

Corrigidos e em produção: upload sem validação, `SMTPDebug`, IDOR na edição de quadra, login da API, edição de quadra (`Venue::findById`), fuso das datas, chaves de tradução faltando, admin voltando ao painel errado, seletor de papel do admin.

## Convenções

- PHP 8.2, PSR-4 `App\\` → `app/`. SQL sempre com prepared statements. Escapar toda saída das views com `htmlspecialchars`.
- API no padrão Laravel (D06): `{ data }` / `{ message, code, errors }`; detalhes em `docs/api.md`.
- Branch por tarefa e PR para a `main` (D17); versão por sprint (D18).
- Testar endpoints de ponta a ponta no ambiente local antes do PR (scripts de teste no scratchpad, com usuários `@kolae.test` apagados ao final).
