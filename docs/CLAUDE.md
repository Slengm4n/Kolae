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

## Situação em 02/11/2026

- **v1.6.0 lançada (02/11), a versão de entrega:** S3 a S6 e o polimento, D29–D58. Antes: v1.4.0 (S1) e v1.5.0 (S2). Correções até a entrega viram v1.6.1, v1.6.2… (patch). `main` e `develop` no mesmo commit; branches remotas: `main`, `develop`, `v2.0-laravel`.
- **Prazo (D28):** congelamento de funcionalidades em **21/11**, entrega em **28/11**. Daqui até lá: dados de demonstração, APK do Rafael, roteiro, vídeo de plano B e ensaio.
- **Produção:** `https://kolae.online` na HostGator (D41), deploy pelo GitHub Actions **só depois dos testes** (D56). O app usa a produção (`https://kolae.online/api/v1`), inclusive na apresentação. E-mails (redefinição de senha e confirmação de e-mail) pelo Titan, `nao-responda@kolae.online` (D55). Contato público: `contato@kolae.online`.
- **Patches na produção:** 001–006 aplicados; 007 (confirmação de e-mail) só garante uma coluna que já existia. 008 (redes sociais, D60) aplicado em 05/11, antes do merge do código que usa as colunas. Depois do deploy da v1.6.0: apertar "Otimizar fotos" no admin (miniaturas das fotos antigas, D58).
- **Na produção, as quadras ainda são de teste:** todas `closed` e sem esportes. Os dados de demonstração precisam de horário, esportes (D29), fotos, WhatsApp e partidas no horário da apresentação. Decidir se a quadra "Paulo" (id 8, antes "Meu pauzao") fica.
- **O que existe (resumo):** API de contas e de quadras com status ao vivo (D03, `docs/modelo-status.md`); painel do dono com abas Visão geral, Dados, Horário, Agenda e Fotos (`partials/venue_top.php`), guia passo a passo (D50) e QR Code (D54); página pública `/quadra/{id}` (D49) e página `/quadras` com mapa Leaflet (D51); CSRF, limite de login, "lembrar de mim" (D43–D45); Termos e Privacidade (D42); páginas de erro novas (D53); telas de entrada redesenhadas, Preferências (tema automático e idioma), avisos e confirmações no visual do Kolae, CNPJ exigido em toda página de gerenciar quadra (D57); confirmação de e-mail leve e miniaturas das fotos (D58).
- **App do Rafael (lido em 02/11):** usa `/termos` e `/privacidade`, mostra a `message` do 429 e valida senha 8 e 18 anos. Falta o primeiro APK com a chave do Maps para Android. O campo `email_verified` (D58) é opcional para o app.

### Pendências fora do código
- **HostGator (ver `docs/hospedagem.md`, "O que falta"):** forçar HTTPS no cPanel; SPF/DKIM; limites de upload do PHP; liberar `kolae.online` na chave do Google Maps; backup antes dos dados de demonstração; o Rafael testar o app pelo 4G; cancelar o InfinityFree.
- **GitHub:** ligar os alertas do Dependabot (Security → Dependabot alerts); opcional: `SECURITY.md`.
- Notebook: serve para testar o app contra mudanças antes do deploy (IP do Wi-Fi muda de rede para rede). Para renovar os status do seed: `teste_status_limpar.sql` e depois `teste_status.sql`.

### Do lado do app (Rafael), combinado em 28/10
Piso com as chaves da API; `icon_path` (Font Awesome) convertido para MaterialCommunityIcons; mock separado por área (auth e quadras pela API real, na produção `https://kolae.online/api/v1`); senha mínima 8; validação local dos 18 anos; deslogar só nos erros `AUTH_*` (D10); data de nascimento só leitura; liberar HTTP no APK; "Mais avaliadas" vira "Disponíveis agora" e distância do detalhe calculada no app (D33); esconder "Falar com o dono" sem WhatsApp (D24).

## Estrutura do código

- `index.php`: front controller, sessão, rotas do site e da `/api/v1`.
- `app/Core`: Router (GET/POST/PUT/PATCH), Database (PDO singleton, sessão MySQL em `-03:00`), AuthHelper, BaseApiController (JWT, `tokenResponse`, `failIfInvalid`, `getJsonInput`), ApiResponse (saída única da API), ApiFormat (datas ISO 8601 e objeto `user`), Mailer (e-mails de redefinição de senha e de confirmação; nunca envia para `.local`/`.test`), EmailVerification (link assinado da confirmação de e-mail, D58), Flash (códigos `?status=`/`?error=` viram avisos, D57), ViewHelper, ImageHelper (valida o conteúdo real do upload, otimiza as fotos e cria as miniaturas `thumb_*.jpg`; sem GD, salva sem otimizar), VenueAccess (acesso às páginas da quadra, CNPJ, status do topo).
- API: `AuthApiController` (login, register, forgot-password), `MeApiController` (`/me`, avatar, senha), `VenueApiController` (lista, detalhe e agenda), `SportApiController`.
- `app/Core/VenueSchedule`: regra do status da quadra e da agenda do dia (D03, `docs/modelo-status.md`), sem acesso ao banco. Models `VenueHour`, `VenueSport`; `Game::findScheduledOn` (partidas agendadas pelo dono, D02).
- `app/Controllers`, `app/Models` (métodos estáticos com PDO), `app/views` (PHP + Tailwind), `lang/` (7 idiomas; chave nova vai nos 7 arquivos).
- `uploads/` fora do git, exceto `uploads/.htaccess` (bloqueia scripts).
- **Visual** (desde a branch `feat/home-redesign`): identidade do app. Cores em `src/input.css`: `brand-*` (500 = `#0084FF` em botões e títulos grandes; 600 = `#0069CC` em texto pequeno e links sobre fundo claro), `status-badge status-badge--available|in-use|closed` para os selos de status, Kanit automática em `h1`–`h3`. Não usar `cyan-*`. Peças reutilizáveis em `app/views/partials/`: `head.php`, `public_header.php`, `public_footer.php`, `lang_modal.php` (site); `auth_layout.php`, `auth_field.php`, `auth_birthdate.php` (telas de entrada); `owner_header.php` (cabeçalho de todo o painel do dono, já inclui os avisos), `config_nav.php`, `venue_top.php`, `venue_gallery.php`, `venue_details.php`, `tour.php`, `venue_qr.php`, `toasts.php`, `error_page.php`. Botões com texto branco usam `bg-brand-600` (contraste mínimo); o `#0084FF` fica em ícones e destaques.
- **CSS/JS compilados:** `npm install` uma vez e `npm run build` a cada mudança de classes ou em `src/`; o resultado (`assets/css/style.css`, `assets/js/bundle.js` e `assets/js/ui.js`) entra no commit. O `ui.js` (avisos com `Kolae.toast` e confirmação com `data-confirm` ou `Kolae.confirm`; nunca `confirm()` do navegador) é separado do bundle para servir também ao admin. No Tailwind v4, as utilitárias perdem para o CSS do Font Awesome: `hidden`, `block` etc. **não funcionam direto num `<i class="fas ...">`**; envolva o ícone num `<span>` e aplique a classe nele.

## Ambiente

- **Local (notebook):** XAMPP com junction `C:\xampp\htdocs\kolae` → repositório. Apache na 8080 (`http://localhost:8080/kolae`), MySQL na 3306, banco `kolae_db` (cópia da produção de 27/10 + patches 001–008 + seed `teste_status.sql`; 005, 006 e 008 aplicados em 05/11). PHP 8.2 no XAMPP (com GD); PHP 8.4 do Laravel Herd no PATH só para `php -l` e `composer` (não usar o Herd para servir o site). `config.php` fora do git, com `SMTP_*`.
- **Local (PC de casa):** XAMPP em `F:\Programas\Xampp`, Apache na **80** (`http://localhost/kolae`), banco `kolae_db` criado do `schema.sql` + patch 004. PHP e Composer fora do PATH: `F:\Programas\Xampp\php\php.exe` e `F:\Programas\Xampp\php\composer.bat`.
- **Cliente `mysql` do XAMPP pela linha de comando:** usar `--default-character-set=utf8mb4`; o padrão (`cp850`) corrompe os acentos. Pelo phpMyAdmin não há esse problema.
- **Produção:** HostGator (Plano Start, cPanel), `https://kolae.online` (D41). Push na `main` → GitHub Actions → FTP (conta `deploy@kolae.online`, que enxerga só a `public_html`; secret `FTP_SERVER_DIR = /`). **A `main` publica direto.** `config.php` e `uploads/` ficam só no servidor (fora do git e do deploy); mudar o `config.php` é pelo Gerenciador de Arquivos do cPanel. Banco `joao5502_kolae` no phpMyAdmin do cPanel: patches novos vão por lá.
- **App:** usa a produção, `https://kolae.online/api/v1`. O notebook (`http://<IP>:8080/kolae/api/v1`, mesma rede Wi-Fi) só para testar uma mudança antes do deploy.
- **Git:** o push funciona deste terminal (credenciais do Windows), mas push, apagar branch remota e sincronizar a `develop` só com pedido do João. Sem `gh`: PR pelo navegador. Mensagens de commit em arquivo UTF-8 **sem BOM**. **Nunca** gravar arquivos do projeto com `Set-Content`/`Out-File` do PowerShell 5: eles colocam BOM, e um BOM num `.php` gera saída antes do `<?php` (quebra cabeçalhos e sessão). Usar o Edit/Write ou `[IO.File]::WriteAllText` com `UTF8Encoding($false)`.
- **Animações:** neste PC as animações do Windows estão desligadas (`MinAnimate = 0`), então o navegador informa "reduzir movimento" e a home aparece parada, de propósito. Para ver as animações: ligar em Configurações → Acessibilidade → Efeitos visuais, ou abrir pelo celular. Nos testes automatizados, emular `prefers-reduced-motion: no-preference` (o Edge invisível herda a configuração do Windows).
- **Telas:** conferir mudanças visuais com capturas de página inteira (Edge + `puppeteer-core`, desktop e celular, claro e escuro), checando também erros de JavaScript e conteúdo vazando para o lado no celular. **Falso alarme conhecido:** nas telas do admin, os cabeçalhos com `animate-up` (`admin/users`, `sports/index`, `venues/index`) saem apagados na captura do navegador (congelados no primeiro quadro da animação), mas aparecem normais na tela de verdade; conferido com foto da tela do Windows em 29/10. Fluxos com formulário: testar no clique (`puppeteer`), não só olhar a captura.
- **MySQL local:** ligar uma vez só no painel do XAMPP e parar antes de desligar o PC. Encerramento forçado corrompe os arquivos de replicação (`multi-master.info`, `relay-log*`); se não subir, rodar `mysqld --console` para ver o erro e mover esses arquivos para `C:\xampp\mysql\data_replicacao_backup`.

## Problemas conhecidos em aberto

1. Painel de admin sem tradução (D19): só em português, de propósito.
2. As quadras da produção ainda não têm horário nem esportes: cadastrar pelo site antes da apresentação.
3. Logout por link GET em `/logout` (um site de fora consegue deslogar a pessoa; baixo risco, em `docs/sugestoes.md`).

Corrigidos e em produção (resumo): segurança da S1 e da S5 (upload, IDOR, CSRF, XSS do mapa, limite de login, "lembrar de mim", senha mínima 8 no site), CNPJ conferido só em "criar" (agora em toda página de gerenciar quadra), cadastro aceitando data de nascimento no futuro, comodidades do cadastro de quadra que não eram salvas, chaves de tradução faltando, fuso das datas, `GET /venues` no formato antigo.

## Convenções

- PHP 8.2, PSR-4 `App\\` → `app/`. SQL sempre com prepared statements. Escapar toda saída das views com `htmlspecialchars`.
- API no padrão Laravel (D06): `{ data }` / `{ message, code, errors }`; detalhes em `docs/api.md`.
- Branch por tarefa e PR para a `main` (D17); versão por sprint (D18).
- Testar endpoints de ponta a ponta no ambiente local antes do PR (scripts de teste no scratchpad, com usuários `@kolae.test` apagados ao final).
- Testes no repositório em `tests/` (fora do deploy): scripts PHP sem PHPUnit (`venue_schedule_test.php`, `whatsapp_test.php`, `agenda_db_test.php`), que o GitHub Actions roda antes de cada deploy, e os testes de clique em `tests/e2e/` (`npm run test:e2e`, 19 roteiros; README lá). Regra nova de status, horário ou partida ganha caso de teste em PHP; tela nova ou mudada ganha teste de clique e entra no mapa do `rodar.js`.
- Página nova do painel: incluir `owner_header.php` (avisos e menu) e, se receber `?status=`/`?error=`, definir `$toastContext` e os códigos no `Flash::MAP`. Página de gerenciar quadra: `VenueAccess::findManageableOrRedirect` (já confere dono e CNPJ). Formulário novo: `Csrf::field()`.
- Rota nova só entra junto com a sua tela, porque a `main` publica direto. Mudança grande pode sair em branches pequenas empilhadas, como a S4 (D35–D40).
