# Kolae — contexto para o Claude

Plataforma web (PHP puro, MVC próprio) que conecta atletas a quadras particulares. Projeto Integrador FATEC Ferraz de Vasconcelos, entrega em **28/11/2026** (congelamento de funcionalidades em **21/11**; antecipada pela D28).

- **João:** backend PHP, `/api/v1` e o site (este repositório).
- **Rafael:** app mobile React Native + Expo (`Thesatiru/kolae-mobile`), que consome a API.

Responder e comentar em português.

## Documentação (ler antes de mudar algo)

O índice está em [`docs/README.md`](README.md). Resumo:

- **`docs/decisoes.md`**: todas as decisões com data e motivo (D01...). Não contrariar uma decisão sem combinar e registrar uma nova.
- **`docs/api.md`**: contrato da API. Endpoint novo ou mudança de formato entra lá **antes** do código, e o Rafael revisa.
- **`docs/modelo-status.md`**: modelo **aprovado** de status da quadra, horário de funcionamento e agenda (D21–D27), base do contrato da S3 e das telas da S4.
- **`docs/setup.md`**: ambiente local e conexão do app ao notebook.
- **`database/kolae.dbml`**: banco como ele é hoje. Mudança = DBML + `database/patches/NNN_*.sql`.
- **`CHANGELOG.md`**: mudanças em `[Não lançado]` até o fim da sprint.
- Plano original (pode estar desatualizado; o que vale é `docs/`): https://claude.ai/artifact/JSdR7eYfrkZFTk5qcVAv4R

## Situação em 28/10/2026

- **S1 concluída**, lançada como **v1.4.0**.
- **S2 do backend concluída**, lançada como **v1.5.0** (tag em 28/10).
- **Prazo novo (D28):** entrega em 28/11, congelamento em 21/11. S3 = 29/10–08/11, S4 = 09–15/11, S5 = 16–21/11, S6 = 22–28/11. Troca de e-mail com código saiu do escopo.
- **S3 do backend concluída e em produção em 28/10**, uma semana adiantada (PRs #43–#45): contrato revisado pelo Rafael (D29–D33), patch 004 aplicado na produção, `GET /venues` (filtros, paginação, status), `GET /venues/{id}`, `GET /venues/{id}/schedule`, `POST /games` removido, D34 (quadra desativada fora da API), números decimais curtos no JSON. Vira a **v1.6.0** em 08/11.
- **Na produção, todas as quadras estão `closed` e com `sports` vazio**: nenhuma tem horário nem esportes ainda (as quadras atuais são de teste). Os dados de demonstração da S6 precisam de horário, esportes (D29) e partidas no horário da apresentação.
- **Próximo: S4 (telas do dono no site)**: horário de funcionamento e agenda de partidas (D21–D27), WhatsApp no perfil (D24), e no formulário de quadra: descrição (D30), preço vazio ou `0` (D31), esportes (D29) e status "Disponível / Em manutenção" (D34). É o maior risco de prazo.

### Pendências fora do código
- Trocar a senha do MySQL da produção e, logo em seguida, o `DB_PASS` do `config.php` da produção pelo FTP (senão o site cai).
- Escolher a hospedagem nova (D13) até 07/11: decide onde a apresentação roda.
- Preparar o notebook para o Rafael integrar a S3: branch atualizada, patch 004 e `database/seeds/teste_status.sql` (renova os três status; limpar com `teste_status_limpar.sql`).
- Atualizar a cópia do plano no Google Drive ("Kolae 2.0 — Plano da equipe") com o prazo novo: precisa do conector Google Docs autorizado.

### Do lado do app (Rafael), combinado em 28/10
Piso com as chaves da API; `icon_path` (Font Awesome) convertido para MaterialCommunityIcons; mock separado por área (auth real, quadras pela API da S3 no notebook); senha mínima 8; validação local dos 18 anos; deslogar só nos erros `AUTH_*` (D10); data de nascimento só leitura; liberar HTTP no APK; "Mais avaliadas" vira "Disponíveis agora" e distância do detalhe calculada no app (D33); esconder "Falar com o dono" sem WhatsApp (D24).

## Estrutura do código

- `index.php`: front controller, sessão, rotas do site e da `/api/v1`.
- `app/Core`: Router (GET/POST/PUT/PATCH), Database (PDO singleton, sessão MySQL em `-03:00`), AuthHelper, BaseApiController (JWT, `tokenResponse`, `failIfInvalid`, `getJsonInput`), ApiResponse (saída única da API), ApiFormat (datas ISO 8601 e objeto `user`), Mailer (e-mail de redefinição de senha), ViewHelper, ImageHelper (valida o conteúdo real do upload; sem GD, salva sem otimizar).
- API: `AuthApiController` (login, register, forgot-password), `MeApiController` (`/me`, avatar, senha), `VenueApiController` (lista, detalhe e agenda), `SportApiController`.
- `app/Core/VenueSchedule`: regra do status da quadra e da agenda do dia (D03, `docs/modelo-status.md`), sem acesso ao banco. Models `VenueHour`, `VenueSport`; `Game::findScheduledOn` (partidas agendadas pelo dono, D02).
- `app/Controllers`, `app/Models` (métodos estáticos com PDO), `app/views` (PHP + Tailwind), `lang/` (7 idiomas; chave nova vai nos 7 arquivos).
- `uploads/` fora do git, exceto `uploads/.htaccess` (bloqueia scripts).

## Ambiente

- **Local (notebook):** XAMPP com junction `C:\xampp\htdocs\kolae` → repositório. Apache na 8080 (`http://localhost:8080/kolae`), MySQL na 3306, banco `kolae_db` (cópia da produção de 27/10 + patches 001–003; **falta o 004**). PHP 8.2 no XAMPP (com GD); PHP 8.4 do Laravel Herd no PATH só para `php -l` e `composer` (não usar o Herd para servir o site). `config.php` fora do git, com `SMTP_*`.
- **Local (PC de casa):** XAMPP em `F:\Programas\Xampp`, Apache na **80** (`http://localhost/kolae`), banco `kolae_db` criado do `schema.sql` + patch 004. PHP e Composer fora do PATH: `F:\Programas\Xampp\php\php.exe` e `F:\Programas\Xampp\php\composer.bat`.
- **Cliente `mysql` do XAMPP pela linha de comando:** usar `--default-character-set=utf8mb4`; o padrão (`cp850`) corrompe os acentos. Pelo phpMyAdmin não há esse problema.
- **Produção:** InfinityFree, `https://kolae.gamer.gd`. Push na `main` → GitHub Actions → FTP. **A `main` publica direto.**
- **App:** usa o backend do notebook pela rede Wi-Fi (D13): `http://<IP>:8080/kolae/api/v1`.
- **Git:** o push funciona deste terminal (credenciais do Windows), mas push, apagar branch remota e sincronizar a `develop` só com pedido do João. Sem `gh`: PR pelo navegador. Mensagens de commit em arquivo UTF-8 **sem BOM** (o `Set-Content` do PowerShell 5 grava BOM).
- **MySQL local:** ligar uma vez só no painel do XAMPP e parar antes de desligar o PC. Encerramento forçado corrompe os arquivos de replicação (`multi-master.info`, `relay-log*`); se não subir, rodar `mysqld --console` para ver o erro e mover esses arquivos para `C:\xampp\mysql\data_replicacao_backup`.

## Problemas conhecidos em aberto

1. Sem CSRF nos formulários do site (S5); `json_encode` sem `JSON_HEX_TAG` em `admin/map.php` (XSS).
2. "Lembrar de mim": o login grava `users.remember_token`, mas `AuthHelper::checkRememberMe` lê `user_tokens` (S5).
3. Senha mínima de 8 só na API; o site aceita qualquer tamanho (D08).
4. Painel de admin e páginas de erro sem tradução (D19).
5. Não há como cadastrar horário, partidas, esportes da quadra, descrição nem WhatsApp pelo site até a S4 (só por SQL).

Corrigidos e em produção: upload sem validação, `SMTPDebug`, IDOR na edição de quadra, login da API, edição de quadra (`Venue::findById`), fuso das datas, chaves de tradução faltando, admin voltando ao painel errado, seletor de papel do admin, `GET /venues` no formato antigo e sem status, `Game::create` quebrado (removido), coordenadas com dígitos demais na produção.

## Convenções

- PHP 8.2, PSR-4 `App\\` → `app/`. SQL sempre com prepared statements. Escapar toda saída das views com `htmlspecialchars`.
- API no padrão Laravel (D06): `{ data }` / `{ message, code, errors }`; detalhes em `docs/api.md`.
- Branch por tarefa e PR para a `main` (D17); versão por sprint (D18).
- Testar endpoints de ponta a ponta no ambiente local antes do PR (scripts de teste no scratchpad, com usuários `@kolae.test` apagados ao final).
