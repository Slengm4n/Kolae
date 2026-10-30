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
- **`docs/setup.md`**: ambiente local e como testar o app contra o notebook antes do deploy.
- **`docs/hospedagem.md`**: produção na HostGator (D41): como foi montada e o que falta configurar.
- **`database/kolae.dbml`**: banco como ele é hoje. Mudança = DBML + `database/patches/NNN_*.sql`.
- **`CHANGELOG.md`**: mudanças em `[Não lançado]` até o fim da sprint.
- Plano original (pode estar desatualizado; o que vale é `docs/`): https://claude.ai/artifact/JSdR7eYfrkZFTk5qcVAv4R

## Situação em 29/10/2026

- **S1 concluída**, lançada como **v1.4.0**.
- **S2 do backend concluída**, lançada como **v1.5.0** (tag em 28/10).
- **Prazo novo (D28):** entrega em 28/11, congelamento em 21/11. S3 = 29/10–08/11, S4 = 09–15/11, S5 = 16–21/11, S6 = 22–28/11. Troca de e-mail com código saiu do escopo.
- **S3 do backend concluída e em produção em 28/10**, uma semana adiantada (PRs #43–#45): contrato revisado pelo Rafael (D29–D33), patch 004 aplicado na produção, `GET /venues` (filtros, paginação, status), `GET /venues/{id}`, `GET /venues/{id}/schedule`, `POST /games` removido, D34 (quadra desativada fora da API), números decimais curtos no JSON. Vira a **v1.6.0** em 08/11.
- **Na produção, todas as quadras estão `closed` e com `sports` vazio**: nenhuma tem horário nem esportes ainda (as quadras atuais são de teste). Os dados de demonstração da S6 precisam de horário, esportes (D29) e partidas no horário da apresentação.
- **Redesign em produção desde 29/10 (PR #47):** identidade do app no site inteiro, nova home com animações, partials públicos. Na conferência (72 capturas + teste de clique) saíram duas correções que já estavam na produção: comodidades do cadastro de quadra e script do perfil. CSS e JS com versão no endereço (`ViewHelper::asset`, `?v=`), para o navegador não usar o arquivo antigo depois de um deploy. Página de antes e depois: https://claude.ai/artifact/XCBNXHDsiwAcfwrjpn4cFF
- **Produção na HostGator desde 29/10 (D41): `https://kolae.online`.** A API de produção responde JSON direto, então **o app usa a produção** (`https://kolae.online/api/v1`), inclusive na apresentação. Banco, fotos e `config.php` migrados do InfinityFree (mesmas chaves), deploy por FTP com a conta `deploy@kolae.online`. O InfinityFree fica no ar alguns dias, sem deploy, e depois é cancelado.
- **S4 concluída no código em 29/10 (D35–D40), uma branch por etapa, empilhadas:** `feat/s4-aba-horario` → `feat/s4-aba-agenda` → `feat/s4-aba-dados` → `feat/s4-cadastro` → `feat/s4-painel` (merge nessa ordem). Base da agenda (#51) e WhatsApp no perfil (#52) já estavam na `main`. Cada etapa tem teste de clique (scratchpad, `teste_*.js`) além dos `tests/`. Peças comuns: `VenueAccess` (acesso, painel, status, texto do selo), `partials/venue_top.php` (topo com selo e abas; variáveis com prefixo `top` para não sobrescrever as da página) e `partials/owner_header.php`.
- **Login e cadastro:** sem rework antes da entrega; os ajustes deles (senha mínima de 8, "lembrar de mim", limite de tentativas) entram na S5, e o acabamento visual na S6, se sobrar tempo.
- **Próximo:** merges da S4, dados de demonstração na produção (horário, esportes e partidas nas quadras reais) e a S5 (segurança: CSRF, XSS no `admin/map.php`, limite de tentativas; "lembrar de mim"; senha mínima no site).

### Pendências fora do código
- **HostGator (ver `docs/hospedagem.md`, "O que falta"):** forçar HTTPS no cPanel; e-mail próprio `@kolae.online` para o "esqueci a senha"; limites de upload do PHP; SPF/DKIM; liberar `kolae.online` na chave do Google Maps; o Rafael testar o app pelo 4G; cancelar o InfinityFree depois de alguns dias.
- Notebook (29/10): `main` atualizada, patch 004 e `database/seeds/teste_status.sql` aplicados no `kolae_db`; serve para testar o app contra mudanças antes do deploy (IP do Wi-Fi `192.168.0.172`, muda de rede para rede). As partidas do seed são relativas à hora em que ele roda: para renovar os status, rodar `teste_status_limpar.sql` e depois `teste_status.sql`.
- Atualizar a cópia do plano no Google Drive ("Kolae 2.0 — Plano da equipe") com o prazo novo: precisa do conector Google Docs autorizado.

### Do lado do app (Rafael), combinado em 28/10
Piso com as chaves da API; `icon_path` (Font Awesome) convertido para MaterialCommunityIcons; mock separado por área (auth e quadras pela API real, na produção `https://kolae.online/api/v1`); senha mínima 8; validação local dos 18 anos; deslogar só nos erros `AUTH_*` (D10); data de nascimento só leitura; liberar HTTP no APK; "Mais avaliadas" vira "Disponíveis agora" e distância do detalhe calculada no app (D33); esconder "Falar com o dono" sem WhatsApp (D24).

## Estrutura do código

- `index.php`: front controller, sessão, rotas do site e da `/api/v1`.
- `app/Core`: Router (GET/POST/PUT/PATCH), Database (PDO singleton, sessão MySQL em `-03:00`), AuthHelper, BaseApiController (JWT, `tokenResponse`, `failIfInvalid`, `getJsonInput`), ApiResponse (saída única da API), ApiFormat (datas ISO 8601 e objeto `user`), Mailer (e-mail de redefinição de senha), ViewHelper, ImageHelper (valida o conteúdo real do upload; sem GD, salva sem otimizar).
- API: `AuthApiController` (login, register, forgot-password), `MeApiController` (`/me`, avatar, senha), `VenueApiController` (lista, detalhe e agenda), `SportApiController`.
- `app/Core/VenueSchedule`: regra do status da quadra e da agenda do dia (D03, `docs/modelo-status.md`), sem acesso ao banco. Models `VenueHour`, `VenueSport`; `Game::findScheduledOn` (partidas agendadas pelo dono, D02).
- `app/Controllers`, `app/Models` (métodos estáticos com PDO), `app/views` (PHP + Tailwind), `lang/` (7 idiomas; chave nova vai nos 7 arquivos).
- `uploads/` fora do git, exceto `uploads/.htaccess` (bloqueia scripts).
- **Visual** (desde a branch `feat/home-redesign`): identidade do app. Cores em `src/input.css`: `brand-*` (500 = `#0084FF` em botões e títulos grandes; 600 = `#0069CC` em texto pequeno e links sobre fundo claro), `status-badge status-badge--available|in-use|closed` para os selos de status, Kanit automática em `h1`–`h3`. Não usar `cyan-*`. Peças públicas reutilizáveis em `app/views/partials/` (`head.php`, `public_header.php`, `public_footer.php`); a home já usa, login e cadastro ainda têm o `<head>` próprio.
- **CSS/JS compilados:** `npm install` uma vez e `npm run build` a cada mudança de classes ou em `src/`; o resultado (`assets/css/style.css`, `assets/js/bundle.js`) entra no commit. No Tailwind v4, as utilitárias perdem para o CSS do Font Awesome: `hidden`, `block` etc. **não funcionam direto num `<i class="fas ...">`**; envolva o ícone num `<span>` e aplique a classe nele.

## Ambiente

- **Local (notebook):** XAMPP com junction `C:\xampp\htdocs\kolae` → repositório. Apache na 8080 (`http://localhost:8080/kolae`), MySQL na 3306, banco `kolae_db` (cópia da produção de 27/10 + patches 001–004 + seed `teste_status.sql`, desde 29/10). PHP 8.2 no XAMPP (com GD); PHP 8.4 do Laravel Herd no PATH só para `php -l` e `composer` (não usar o Herd para servir o site). `config.php` fora do git, com `SMTP_*`.
- **Local (PC de casa):** XAMPP em `F:\Programas\Xampp`, Apache na **80** (`http://localhost/kolae`), banco `kolae_db` criado do `schema.sql` + patch 004. PHP e Composer fora do PATH: `F:\Programas\Xampp\php\php.exe` e `F:\Programas\Xampp\php\composer.bat`.
- **Cliente `mysql` do XAMPP pela linha de comando:** usar `--default-character-set=utf8mb4`; o padrão (`cp850`) corrompe os acentos. Pelo phpMyAdmin não há esse problema.
- **Produção:** HostGator (Plano Start, cPanel), `https://kolae.online` (D41). Push na `main` → GitHub Actions → FTP (conta `deploy@kolae.online`, que enxerga só a `public_html`; secret `FTP_SERVER_DIR = /`). **A `main` publica direto.** `config.php` e `uploads/` ficam só no servidor (fora do git e do deploy); mudar o `config.php` é pelo Gerenciador de Arquivos do cPanel. Banco `joao5502_kolae` no phpMyAdmin do cPanel: patches novos vão por lá.
- **App:** usa a produção, `https://kolae.online/api/v1`. O notebook (`http://<IP>:8080/kolae/api/v1`, mesma rede Wi-Fi) só para testar uma mudança antes do deploy.
- **Git:** o push funciona deste terminal (credenciais do Windows), mas push, apagar branch remota e sincronizar a `develop` só com pedido do João. Sem `gh`: PR pelo navegador. Mensagens de commit em arquivo UTF-8 **sem BOM**. **Nunca** gravar arquivos do projeto com `Set-Content`/`Out-File` do PowerShell 5: eles colocam BOM, e um BOM num `.php` gera saída antes do `<?php` (quebra cabeçalhos e sessão). Usar o Edit/Write ou `[IO.File]::WriteAllText` com `UTF8Encoding($false)`.
- **Animações:** neste PC as animações do Windows estão desligadas (`MinAnimate = 0`), então o navegador informa "reduzir movimento" e a home aparece parada, de propósito. Para ver as animações: ligar em Configurações → Acessibilidade → Efeitos visuais, ou abrir pelo celular. Nos testes automatizados, emular `prefers-reduced-motion: no-preference` (o Edge invisível herda a configuração do Windows).
- **Telas:** conferir mudanças visuais com capturas de página inteira (Edge + `puppeteer-core`, desktop e celular, claro e escuro), checando também erros de JavaScript e conteúdo vazando para o lado no celular. **Falso alarme conhecido:** nas telas do admin, os cabeçalhos com `animate-up` (`admin/users`, `sports/index`, `venues/index`) saem apagados na captura do navegador (congelados no primeiro quadro da animação), mas aparecem normais na tela de verdade; conferido com foto da tela do Windows em 29/10. Fluxos com formulário: testar no clique (`puppeteer`), não só olhar a captura.
- **MySQL local:** ligar uma vez só no painel do XAMPP e parar antes de desligar o PC. Encerramento forçado corrompe os arquivos de replicação (`multi-master.info`, `relay-log*`); se não subir, rodar `mysqld --console` para ver o erro e mover esses arquivos para `C:\xampp\mysql\data_replicacao_backup`.

## Problemas conhecidos em aberto

1. Sem CSRF nos formulários do site (S5); `json_encode` sem `JSON_HEX_TAG` em `admin/map.php` (XSS).
2. "Lembrar de mim": o login grava `users.remember_token`, mas `AuthHelper::checkRememberMe` lê `user_tokens` (S5).
3. Senha mínima de 8 só na API; o site aceita qualquer tamanho (D08).
4. Painel de admin e páginas de erro sem tradução (D19).
5. As quadras da produção ainda não têm horário nem esportes: com a S4 no ar, cadastrar pelo site (abas Horário e Dados) antes da apresentação.

Corrigidos e em produção: upload sem validação, `SMTPDebug`, IDOR na edição de quadra, login da API, edição de quadra (`Venue::findById`), fuso das datas, chaves de tradução faltando, admin voltando ao painel errado, seletor de papel do admin, `GET /venues` no formato antigo e sem status, `Game::create` quebrado (removido), coordenadas com dígitos demais na produção.

## Convenções

- PHP 8.2, PSR-4 `App\\` → `app/`. SQL sempre com prepared statements. Escapar toda saída das views com `htmlspecialchars`.
- API no padrão Laravel (D06): `{ data }` / `{ message, code, errors }`; detalhes em `docs/api.md`.
- Branch por tarefa e PR para a `main` (D17); versão por sprint (D18).
- Testar endpoints de ponta a ponta no ambiente local antes do PR (scripts de teste no scratchpad, com usuários `@kolae.test` apagados ao final).
- Testes no repositório em `tests/` (fora do deploy), scripts PHP simples sem PHPUnit: `venue_schedule_test.php` (sem banco) e `agenda_db_test.php` (banco local + seed). Como rodar: `docs/setup.md`. Regra nova de status, horário ou partida ganha caso de teste lá.
- A S4 sai em branches pequenas, uma por etapa (D35–D40): `feat/s4-base-agenda` (regras e models, sem tela), depois as abas Horário, Agenda e Dados, o cadastro e o painel. Rota nova só entra junto com a sua tela, porque a `main` publica direto.
