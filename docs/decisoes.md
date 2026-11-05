# Decisões do projeto

Registro do que foi decidido, quando e por quê. Antes de mudar algo que está aqui, combine com a outra pessoa e registre uma nova decisão (não apague a antiga: marque como **substituída** e aponte para a nova).

Cada decisão tem: **o quê**, **por quê** e **onde isso aparece** (código ou documento afetado).

## Produto

### D01 · Manter o PHP até a entrega; Laravel só depois (26/10/2026)
- **O quê:** até 05/12 o backend continua em PHP puro; o Kolae 2.0 em Laravel + Sanctum começa depois da entrega, mantendo o mesmo contrato da API.
- **Por quê:** não há tempo para migrar e entregar as funcionalidades.
- **Onde:** `docs/CLAUDE.md`; a branch `v2.0-laravel` fica parada.

### D02 · Partidas são agendadas pelo dono, no site (28/10/2026)
- **O quê:** a pessoa não cria nem entra em partida pelo app. O dono da quadra (`owner`) agenda as partidas no site, para quem alugou o horário. O app mostra o status da quadra (`available`, `in_use`, `closed`), a agenda do dia e um botão "Falar com o dono" (WhatsApp).
- **Por quê:** é o fluxo real de aluguel de quadra particular e cabe no prazo.
- **Onde:** substitui o plano antigo de `match_participants` e entrar/sair de partida. `POST /api/v1/games` será removido. Proposta de banco e regras em `docs/modelo-status.md`.

### D03 · O status da quadra é calculado pela API (28/10/2026)
- **O quê:** o app nunca calcula status; só exibe o que a API manda (`status` e `status_until`).
- **Por quê:** uma regra só, num lugar só; app e site sempre concordam.
- **Onde:** `docs/modelo-status.md`, `docs/api.md`.

### D04 · Avaliações, favoritos, chat e duas etapas são vitrine (28/10/2026)
- **O quê:** existem só no app, com dados falsos e o aviso "Prévia". Não há backend para eles.
- **Por quê:** mostrar o futuro na feira sem gastar o prazo.

### D05 · `owner` = quem cadastrou CNPJ (28/10/2026)
- **O quê:** cadastrar CNPJ no site troca o papel de `player` para `owner`. Admin continua admin. Todo cadastro novo começa como `player`.
- **Onde:** `UserController::storeCnpj`, `database/patches/003_owner_por_cnpj.sql`. Na produção, os papéis foram ajustados pelo painel de admin em 28/10, com o phpMyAdmin do InfinityFree fora do ar. O patch 003 foi aplicado pelo phpMyAdmin no mesmo dia, quando ele voltou.

## API

### D06 · Formato das respostas no padrão do Laravel (28/10/2026)
- **O quê:** sucesso `{ "data": ... }`; erro `{ "message", "code", "errors": { "campo": ["..."] } }`. Datas ISO 8601 com fuso, dinheiro em centavos (`_cents`), coordenadas numéricas, booleanos `true/false`, paginação com `meta`.
- **Por quê:** o app já esperava esse formato, e o Kolae 2.0 em Laravel herda sem mudar o app (D01).
- **Onde:** `app/Core/ApiResponse.php`, `app/Core/ApiFormat.php`, `docs/api.md`. Em produção desde a S2 (PR #39).

### D07 · Token de login de 30 dias, sem renovação (28/10/2026)
- **Por quê:** no celular, 8 horas obrigaria a logar todo dia.
- **Onde:** `BaseApiController::TOKEN_TTL_SECONDS`.

### D08 · Senha mínima de 8 caracteres (28/10/2026)
- **Onde:** `User::PASSWORD_MIN_LENGTH` (API). **Pendente:** o site ainda aceita qualquer tamanho (cadastro, redefinir e trocar senha).

### D09 · Data de nascimento não é editável (28/10/2026)
- **O quê:** nem no app nem no site. `PATCH /me` com `birthdate` responde 422.
- **Por quê:** impede contornar a regra dos 18 anos depois do cadastro.

### D10 · O app só desloga em erro de token (28/10/2026)
- **O quê:** desloga em `AUTH_MISSING_TOKEN`, `AUTH_INVALID_FORMAT`, `AUTH_TOKEN_EXPIRED`, `AUTH_INVALID_TOKEN` e `AUTH_INVALID_USER`. Nunca em `INVALID_CREDENTIALS` (senha errada no login).

### D11 · Troca de e-mail com código fica para a S5 (28/10/2026) · **substituída pela D28**
- **Por quê:** libera a S2 para o essencial; até lá a tela fica no mock do app.

### D12 · Dono da quadra como objeto `owner` (28/10/2026)
- **O quê:** `GET /venues/{id}` traz `owner: { name, avatar_url, member_since, whatsapp }` e o endereço em texto, no lugar de `owner_whatsapp` solto.
- **Onde:** contrato da S3 em `docs/api.md` (a escrever).

## Infraestrutura

### D13 · Opção C: o app usa o backend do notebook do João (27/10/2026) · **substituída pela D41**
- **O quê:** o InfinityFree devolve um desafio JavaScript (`/aes.js`) a clientes que não são navegador, então o app não consegue usar a API da produção. Até migrar de hospedagem, o app usa `http://<IP do notebook>:8080/kolae/api/v1` na mesma rede Wi-Fi.
- **Pendente:** escolher uma hospedagem que aceite API (prevista para novembro). Isso decide se a apresentação usa a produção ou o notebook.
- **Onde:** `docs/setup.md`, `index.php` (IP de rede privada conta como ambiente local).

### D14 · Banco: DER em DBML e patches SQL; Phinx removido (27/10/2026)
- **O quê:** o desenho do banco fica em `database/kolae.dbml` (dbdiagram). Toda mudança = editar o DBML + criar `database/patches/NNN_*.sql` (pode rodar mais de uma vez, testado no local) e aplicar na produção pelo phpMyAdmin.
- **Por quê:** as migrations Phinx estavam fora de sincronia com a produção.

### D15 · Valores padronizados no banco (27/10/2026)
- `venues.status`: `available | unavailable | maintenance`.
- `venues.floor_type`: `synthetic | court | sand | clay | grass | wood` (`Venue::FLOOR_TYPES`). A API devolve a chave; o app traduz.

### D16 · Fuso de Brasília em tudo (28/10/2026)
- **O quê:** a sessão do MySQL usa `-03:00` (o mesmo do PHP). Offset fixo porque o InfinityFree pode não ter tabelas de fuso nomeado.
- **Onde:** `app/Core/Database.php`.

## Fluxo de trabalho

### D17 · Branches, PR e `develop` (27/10/2026)
- Uma branch por tarefa (`feat/`, `fix/`, `docs/`, `chore/`), PR para a `main` (que publica direto na produção), revisão da outra pessoa. A `develop` é só espelho da `main`.

### D18 · Versões: uma minor por sprint (27/10/2026)
- SemVer com tags `vX.Y.Z`: S1 = v1.4.0, S2 = v1.5.0, e assim por diante; patch para correção urgente; v2.0.0 reservada para o Laravel. Mudanças vão para `[Não lançado]` no `CHANGELOG.md` e viram versão no fim da sprint.
- Datas das versões a partir da S2: ver a D28.

### D19 · Tradução do painel de admin fica para depois da entrega (28/10/2026)
- **O quê:** `admin/*`, `sports/*`, `venues/index.php` e as páginas de erro 403/404 continuam com texto fixo em português. Toda chave nova das telas públicas entra nos 7 idiomas.

### D20 · "Cancelar" na edição de quadra pelo celular fica para a S6 (28/10/2026)
- Polimento, se sobrar tempo.

## Status, horário de funcionamento e agenda

Respostas às perguntas do `docs/modelo-status.md`, que passa a valer como modelo aprovado. Base: D02 e D03.

### D21 · Um intervalo de funcionamento por dia (28/10/2026)
- **O quê:** cada dia da semana tem no máximo um "abre / fecha" (`venue_hours` com `(venue_id, weekday)` único). Sem pausa de almoço.
- **Por quê:** cabe no prazo; mais de um intervalo por dia complica a tela e o cálculo do status.

### D22 · Funcionamento não passa da meia-noite (28/10/2026)
- **O quê:** `closes_at` precisa ser depois de `opens_at`, no mesmo dia (no máximo 23:59).
- **Por quê:** virar o dia exige tratar dois dias de uma vez no status e na agenda; fica para depois da entrega.

### D23 · Quadra sem horário cadastrado aparece como `closed` (28/10/2026)
- **O quê:** quadra sem nenhuma linha em `venue_hours` continua no mapa como `closed`, e o site mostra um aviso para o dono cadastrar o horário.
- **Por quê:** sumir do mapa esconderia quadras na demonstração.

### D24 · WhatsApp obrigatório para o dono cadastrar ou editar quadra (28/10/2026)
- **O quê:** `users.whatsapp` (só dígitos, com DDI e DDD). O site exige o WhatsApp de quem tem papel `owner` ao cadastrar ou editar quadra; o admin não é obrigado. Na API vai em `owner.whatsapp` (D12) e pode vir `null` (donos antigos, quadras do admin): **o app esconde o botão "Falar com o dono" quando vier vazio**.
- **Por quê:** o contato com o dono é o fluxo principal do app (D02).

### D25 · Esporte da partida é opcional (28/10/2026)
- **O quê:** `matches.sport_id` aceita `NULL`. O dono pode não saber o esporte de quem alugou.

### D26 · `match_participants` é apagada (28/10/2026)
- **O quê:** o patch 004 remove a tabela e a coluna `matches.max_players`.
- **Por quê:** estão vazias e são do modelo antigo (entrar/sair de partida, substituído pela D02). O histórico do Git guarda o desenho, se um dia precisar.

### D27 · Partidas só dentro do horário de funcionamento (28/10/2026)
- **O quê:** o site recusa partida que comece antes da abertura ou termine depois do fechamento do dia, ou que se sobreponha a outra partida da quadra. Se o dono mudar o horário depois e alguma partida futura ficar de fora, o site **avisa** quais são, sem bloquear a mudança (nesse período o status calculado já é `closed`).
- **Por quê:** deixa o status coerente com a agenda.

## Prazo

### D28 · Entrega antecipada para 28/11/2026 (28/10/2026)
- **O quê:** a entrega na FATEC passou de 05/12 para **28/11/2026**, com **congelamento de funcionalidades em 21/11**. Novo cronograma em `docs/README.md`:
  - **S3 · 29/10–08/11** (antecipada, porque a S2 do backend terminou antes): contrato, patch 004, `/venues` com status e `owner`, `/venues/{id}`, `/schedule`.
  - **S4 · 09/11–15/11:** telas do dono no site (horário, agenda, WhatsApp).
  - **S5 · 16/11–21/11:** segurança essencial (CSRF, XSS no `admin/map.php`, limite de tentativas no login) e "lembrar de mim".
  - **S6 · 22/11–28/11:** só correções, dados de demonstração e ensaio.
- **Corte:** a troca de e-mail com código (D11) sai do escopo da entrega; a tela fica só no mock do app, como vitrine (D04).
- **Versões (D18):** v1.5.0 = S2, lançada ao abrir a S3; v1.6.0 = S3 (08/11); v1.7.0 = S4 (15/11); v1.8.0 = S5 (21/11); v1.9.0 = versão da entrega (28/11).
- **Por quê:** o prazo encurtou uma semana; o backend do status vem para a S3 para a S4 ficar só com as telas, que são o maior risco.
- **Hospedagem:** resolvida em 29/10 pela D41 (HostGator); a apresentação usa a produção.

## Contrato da S3

Revisão do Rafael em 28/10/2026, cruzando o contrato com as telas do app (mapa, filtros, card, informações da quadra e agenda).

### D29 · Esportes de cada quadra (`venue_sports`) (28/10/2026)
- **O quê:** tabela `venue_sports` (quadra × esporte) no patch 004. Na S4, o formulário de quadra do site ganha caixas de seleção dos esportes. A API devolve `sports` em cada quadra e aceita o filtro `sport_id`.
- **Por quê:** o banco não ligava quadra a esporte, e o filtro por esporte é o primeiro chip do mapa e da home do app.
- **Cuidado:** até o dono marcar, `sports` vem vazio e a quadra some do filtro. **Os dados de demonstração (S6) precisam ter os esportes marcados.**

### D30 · Descrição da quadra (28/10/2026)
- **O quê:** `venues.description` (texto opcional, até 1000 caracteres) no patch 004 e um campo de texto no formulário de quadra do site (S4). Vai no detalhe da API; `null` esconde a seção "Sobre a quadra" no app.
- **Por quê:** a tela de informações da quadra tem essa seção e ficaria vazia sem ela.

### D31 · Preço `0` = grátis, `null` = não informado (28/10/2026)
- **O quê:** `price_per_hour_cents: 0` é quadra grátis; `null` é "Preço não informado". O filtro `max_price_cents` inclui as grátis (`max_price_cents=0` = só grátis); as sem preço ficam de fora. No site (S4), o preço pode ficar vazio (não informado) ou ser `0`.
- **Por quê:** o app mostrava `null` como "Grátis" e o filtro tem um chip "Grátis"; os dois casos precisam ser diferentes.

### D32 · Filtro de área de lazer (28/10/2026)
- **O quê:** `has_leisure_area` vai no resumo da quadra e é aceito como filtro (`has_leisure_area=true`), igual a `is_covered`.
- **Por quê:** o design de filtros do app tem o chip "Área de lazer".

### D33 · Avaliação e distância no detalhe resolvidas no app (28/10/2026)
- **O quê:** a seção "Mais avaliadas" da home do app vira "Disponíveis agora" (`GET /venues?status=available`), porque avaliação é vitrine (D04) e não há como ordenar por ela. A distância no detalhe é calculada pelo app, com a posição da pessoa e as coordenadas da quadra.
- **Por quê:** distância não é regra de negócio como o status, então calcular no app não contraria a D03. Nenhuma mudança no contrato.

### D34 · `unavailable` = quadra desativada, `maintenance` = fechada pelo dono (28/10/2026)
- **O quê:** no site, "Desativar quadra" grava `venues.status = 'unavailable'` (nunca preencheu `deleted_at`) e promete ao dono que a quadra "ficará invisível". Então `unavailable` fica **fora da API** (não aparece na lista; detalhe e agenda respondem 404). O fechamento temporário pelo dono passa a ser só `maintenance`, que aparece como `closed` com `status_until = null`.
- **Por quê:** o site nunca teve outro jeito de fechar uma quadra, então todo `unavailable` na produção veio do "Desativar". Mostrar essas quadras como fechadas contrariaria o que o site diz ao dono. Não precisa migrar dados.
- **Onde:** `Venue::searchForApi` / `findForApi`, `docs/modelo-status.md` (seção 2.2). Na S4, o seletor de status da edição de quadra oferece "Disponível" e "Em manutenção"; "Desativar" continua sendo o botão.

### D35 · Horário e agenda em abas dentro da quadra (29/10/2026)
- **O quê:** a página da quadra no painel do dono ganha três abas: **Dados** (o formulário de edição atual), **Horário** (os 7 dias, cada um com "abre / fecha" ou "fechado") e **Agenda**. Cada aba salva sozinha.
- **Por quê:** o formulário de edição já é longo; separar evita um "Salvar" que mexe em tudo de uma vez e deixa cada parte testável por conta própria.
- **Onde:** `app/views/venues/` e as rotas `/dashboard/quadras/...` (S4). Vale para o dono e para o admin.

### D36 · Cadastro de quadra: descrição e esportes no passo a passo, horário depois (29/10/2026)
- **O quê:** o passo a passo de cadastro ganha descrição (D30), esportes (D29) e preço opcional (D31). O horário **não** entra: ao terminar, o dono vai para a aba Horário com o aviso da D23.
- **Por quê:** o passo a passo já é grande; o horário tem a sua aba (D35) e a quadra sem horário já tem comportamento definido (`closed` + aviso).

### D37 · Agenda dia a dia, com linha do tempo (29/10/2026)
- **O quê:** a aba Agenda mostra um dia por vez (setas para o dia anterior e o seguinte), uma barra com as faixas livres e ocupadas (a mesma divisão do `GET /venues/{id}/schedule`), a lista das partidas do dia com "Cancelar" e o formulário "Agendar partida" (início, fim, esporte opcional, para quem).
- **Por quê:** funciona bem no celular e mostra ao dono o mesmo que o app mostra. Calendário semanal ficou de fora pelo prazo.

### D38 · WhatsApp pedido no próprio formulário da quadra (29/10/2026)
- **O quê:** se o dono (`owner`) ainda não tem WhatsApp, o formulário da quadra (cadastro e edição) mostra o campo, obrigatório, e ao salvar grava em `users.whatsapp`. O perfil também tem o campo, para mudar depois.
- **Por quê:** cumpre a D24 sem tirar o dono do fluxo.

### D39 · Só partida avulsa (29/10/2026)
- **O quê:** sem partidas que se repetem ("toda terça por 4 semanas"). Cada partida é agendada uma a uma.
- **Por quê:** prazo da S4 (até 15/11). Fica como melhoria futura.

### D40 · Status ao vivo nos cards do painel do dono (29/10/2026)
- **O quê:** os cards das quadras no painel mostram o selo de status (Disponível / Em uso / Fechada), calculado pela mesma regra da API (`VenueSchedule`), e o aviso "sem horário cadastrado" (D23).
- **Por quê:** o dono vê na hora o que o app está mostrando.

## Hospedagem

### D41 · Produção na HostGator, em `kolae.online` (29/10/2026)
- **O quê:** a produção saiu do InfinityFree e foi para a HostGator (Plano Start, cPanel), com domínio próprio: **`https://kolae.online`**. A API de produção, `https://kolae.online/api/v1`, responde JSON direto, então **o app passa a usar a produção** (inclusive na apresentação). O notebook continua só para desenvolvimento e testes locais.
- **Por quê:** o InfinityFree respondia ao app com um desafio JavaScript em vez do JSON (D13). Na HostGator isso não acontece, e o domínio próprio fica melhor para a banca.
- **Como ficou:** PHP 8.2; banco `joao5502_kolae` (MySQL local do servidor, `DB_HOST = localhost`), importado do InfinityFree; fotos de `uploads/` copiadas; `config.php` com as mesmas `ENCRYPTION_KEY`, `ENCRYPTION_IV` e `JWT_SECRET` (CNPJs e logins continuam valendo) e banco com senha nova. Deploy pelo GitHub Actions com uma conta FTP só para isso (`deploy@kolae.online`, enxerga só a `public_html`; secret `FTP_SERVER_DIR = /`).
- **Onde:** `docs/hospedagem.md` (passo a passo e o que falta), `.github/workflows/deploy.yml`, `docs/api.md` (base URL).
- **InfinityFree:** fica no ar alguns dias, sem receber deploy, até a HostGator se provar estável; depois é cancelado.

## Documentos legais

### D42 · Termos de Uso e Política de Privacidade em páginas do site, só em português (29/10/2026)
- **O quê:** duas páginas públicas, `/termos` e `/privacidade`, com índice e texto conforme a LGPD. O site linka as duas no rodapé e no cadastro (abrem em nova aba); **o app abre as mesmas URLs** (navegador ou WebView), sem tela própria. O texto fica só em português; título, menu e um aviso ("disponível apenas em português") seguem o idioma escolhido.
- **Por quê:** um texto só, mantido num lugar, vale para site e app. Traduzir texto jurídico para 7 idiomas é caro e arriscado; a versão que vale pela lei brasileira é a em português.
- **Onde:** `app/Controllers/LegalController.php` (data da versão em `UPDATED_AT`, e-mail de contato em `CONTACT_EMAIL`), `app/views/legal/` (`terms_content.php`, `privacy_content.php`, `page.php`). Ao mudar o texto, atualizar `UPDATED_AT`.
- **Contato:** `contato@kolae.online`.

## Segurança (S5)

### D43 · CSRF em todos os formulários do site (29/10/2026)
- **O quê:** um token por sessão (`App\Core\Csrf`), no campo escondido `_csrf` de todo `<form method="POST">`, conferido em `index.php` antes do roteador. Sem token ou com token errado: **403** e a página "Página expirada" com botão Voltar. A API (`/api/v1`) fica de fora, porque autentica pelo cabeçalho `Authorization`, que outro site não consegue enviar.
- **Por quê 403 e não 419 (o do Laravel):** o Apache troca status que ele não conhece por 500.
- **Regra para telas novas:** todo formulário POST leva `<?php echo \App\Core\Csrf::field(); ?>` logo depois do `<form>`. Esquecer o campo dá 403 na hora do teste, então não passa despercebido.
- **Efeito colateral:** envio maior que o `post_max_size` do PHP chega com o `$_POST` vazio e também cai na página "Página expirada", cujo texto cita arquivos grandes demais.

### D44 · "Lembrar de mim" com `user_tokens` (29/10/2026)
- **O quê:** o login grava em `user_tokens` (selector + hash SHA-256 do validator, 30 dias) e o cookie `remember_me` leva `selector:validator`, HttpOnly, SameSite=Lax e Secure no HTTPS. Antes o login gravava em `users.remember_token` e a leitura procurava em `user_tokens`, então nunca funcionava. Logout apaga o token daquele navegador. Conta inativa não entra pelo cookie.
- **`users.remember_token`:** não é mais usada; fica no banco até a migração para o Laravel.

### D45 · Limite de tentativas de login, no site e na API (29/10/2026)
- **O quê:** numa janela de 15 minutos, 5 erros na mesma conta a partir do mesmo IP, ou 20 erros do mesmo IP em qualquer conta, bloqueiam o login até o erro mais antigo vencer. Bloqueado, nem a senha certa entra. Login certo zera os erros daquela conta e IP. Site: mensagem com os minutos e a sugestão de "Esqueci a senha". API: `429 TOO_MANY_ATTEMPTS` com o cabeçalho `Retry-After` (segundos).
- **Onde:** `App\Core\LoginThrottle`, tabela `login_attempts` (patch 005; o e-mail fica só como hash). Sem a tabela, o login funciona sem limite, então o deploy não quebra se o patch atrasar.
- **Por quê por conta + IP, e não só por conta:** bloquear só pela conta deixaria qualquer pessoa trancar a conta de outra errando a senha de propósito.

## Página da quadra no painel

### D46 · Aba Fotos: ordem, capa e limite de 10 (02/11/2026)
- **O quê:** as fotos ganham ordem (`venues_images.position`, patch 006) e a **primeira é a capa** em todo lugar: painel, mapa do admin, home e API (`cover_image_url` e a ordem de `images`). Na aba Fotos o dono (ou o admin) envia fotos (prévia, arrastar, JPG/PNG/WebP até 10 MB), escolhe "Tornar capa", move para a esquerda/direita e apaga. Máximo de **10 fotos por quadra**, conferido no navegador e no servidor (`VenueImage::MAX_PER_VENUE`).
- **Por quê:** antes a capa era sempre a primeira enviada, sem como trocar, e o upload ficava no meio da aba Dados (onde remover uma foto nova antes de salvar não funcionava). A aba Dados agora só mostra a prévia e o atalho.
- **Mover com botões, não arrastar:** funciona igual no celular, com teclado e leitor de tela, e sem biblioteca. Arrastar para reordenar fica como melhoria.

### D47 · Aba Visão geral: a quadra como o atleta vê (02/11/2026)
- **O quê:** primeira aba da quadra e destino do card no painel. Galeria (grade com uma foto grande e quatro menores no computador, carrossel de arrastar no celular, tela cheia com setas, teclado e Esc), esportes, estrutura, "Sobre a quadra", horário da semana, endereço com link para o Google Maps e, na lateral, preço, o dia de hoje (horário e partidas, com "Agora"), o WhatsApp que aparece no app e a lista **Quadra completa** (3 fotos, horário, esportes, descrição, preço, WhatsApp), cada item pendente levando à aba que resolve.
- **Por quê:** o dono confere o que está publicado sem abrir o app, e a lista mostra o que falta para a quadra ficar boa na apresentação.
- **Abas:** Visão geral, Dados, Fotos, Horário e Agenda. No celular cada aba mostra o ícone em cima do nome, para as cinco caberem sem rolagem.

### D48 · Fotos otimizadas no servidor (02/11/2026)
- **O quê:** toda foto de quadra enviada vira **JPEG de até 1600 px** no lado maior (qualidade 82, carregamento progressivo), girada pela orientação do celular (EXIF). Uma foto de celular de ~6 MB fica com ~250 KB. Se o servidor não conseguir processar (sem GD, memória), guarda a original para o envio não falhar. As fotos que já estavam no servidor são otimizadas pelo botão **"Otimizar fotos"** no painel do admin, que pula as que já estão boas, mantém ordem e capa e pode rodar mais de uma vez.
- **Por quê:** o app carrega as fotos pelo 4G; 10 fotos de 4 MB eram 40 MB por quadra.
- **Avatar:** o redimensionamento dele nunca funcionava (o código calculava a imagem menor e gravava a original). Corrigido, e com a rotação do EXIF.
- **Onde:** `ImageHelper::savePhotoAsJpeg`, `VenuePhotosController::storeUploads` e `optimizeExisting`, card "Fotos das quadras" em `admin/dashboard.php`.

### D49 · Página pública da quadra (02/11/2026)
- **O quê:** `kolae.online/quadra/{id}`, aberta sem login: galeria, as mesmas informações do app, selo de status ao vivo, horários ocupados de hoje (só os horários, **nunca quem alugou**), "Falar com o dono" (WhatsApp com mensagem pronta citando a quadra), responsável pela quadra e "Compartilhar" (menu do celular ou cópia do link). O link compartilhado mostra nome, descrição e capa na prévia (Open Graph). Os cards da home abrem nela, e a Visão geral tem "Ver página pública".
- **Regra de visibilidade:** a mesma da API (D34): quadra desativada ou excluída dá 404.
- **Por quê:** o site não tinha como ver uma quadra; agora dá para mostrar no computador na apresentação, e o dono pode divulgar o link.
- **Onde:** `PublicVenueController`, `views/venues/public.php`. Galeria e informações são os partials `venue_gallery.php` e `venue_details.php`, os mesmos da Visão geral.

### D50 · Guia passo a passo nas abas Horário e Agenda (02/11/2026)
- **O quê:** um guia que escurece a página, destaca um elemento por vez e explica em um balão ("Passo 1 de 5", Pular / Voltar / Próximo / Entendi). **Horário** (5 passos): ligar os dias, abre e fecha (um horário por dia, sem passar da meia-noite), preencher rápido, como aparece no app, salvar. **Agenda** (4 passos): um dia por vez, linha do tempo, agendar partida, partidas do dia e cancelar. Abre sozinho na primeira visita (inclusive logo depois de cadastrar a quadra) e pelo botão **"Como funciona?"**. Esc ou clicar fora fecha; setas do teclado mudam de passo; respeita "reduzir movimento".
- **"Já viu" fica no navegador** (`localStorage`, chaves `kolae_tour_hours` e `kolae_tour_agenda`), não no banco: sem patch, e o pior caso é o guia aparecer de novo em outro aparelho.
- **Onde:** `partials/tour.php` (componente, sem biblioteca), configurado em `venues/hours.php` e `venues/agenda.php`. Para outra tela: ids nos elementos, lista de passos e o include.
- **Testes de clique de outras telas** precisam marcar o guia como visto depois do login, senão ele cobre os botões.

### D51 · Página pública "Quadras" com mapa e filtros (02/11/2026)
- **O quê:** `kolae.online/quadras`, sem login: busca pelo nome, filtros (esporte, "Disponível agora", coberta, com iluminação, preço máximo por hora), "Perto de mim" (localização do navegador; ordena por distância e mostra os km), lista de cards e mapa com um ponto por quadra colorido pelo status. No celular, botões "Lista" e "Mapa". Cada quadra abre a página pública (D49). O menu "Quadras" do cabeçalho e do rodapé e o "Ver todas as quadras" da home e da página da quadra levam para ela.
- **Mesma lista da API:** o filtro e o cálculo do status saíram do `index` da API para `VenueApiController::listVenues`, usado pelos dois. A resposta da API não mudou.
- **Mapa com Leaflet e OpenStreetMap**, sem chave: funciona igual no local e na produção e não depende da chave do Google Maps (que ainda precisa ser liberada para `kolae.online`). O mapa do admin continua no Google.
- **Filtros na URL** (`?agora=1&coberta=1&preco=100...`): dá para compartilhar uma busca; valor inválido é ignorado em vez de dar erro.

### D52 · Limite no "esqueci a senha" (02/11/2026)
- **O quê:** no máximo **um e-mail a cada 5 minutos por conta** (`PasswordReset::RESEND_MINUTES`), no site e na API. Pedido repetido dentro do prazo não envia nada e vai para o log.
- **Como:** usa a hora do token em `password_reset_tokens`, que guarda um token por e-mail (o último). Sem patch de banco.
- **A resposta é sempre a mesma** (site: "se o e-mail existir, um link foi enviado"; API: `200`), exista a conta ou não, enviado ou não: o limite não revela quem tem cadastro. O site avisa para conferir o spam e esperar 5 minutos.
- **Por quê:** sem limite, qualquer pessoa disparava e-mails sem parar para um endereço, o que incomoda o dono da conta e pode fazer a HostGator bloquear a conta de e-mail do Kolae.

### D53 · Páginas de erro no visual novo (02/11/2026)
- **O quê:** 404 ("Página não encontrada", com "Ir para o início" e "Ver quadras"; quadra inexistente com o título "Quadra não encontrada"), 403 ("Acesso negado", com "Voltar ao painel" ou "Entrar") e a "Página expirada" do CSRF usam a mesma base, `partials/error_page.php`: cartão com tema claro/escuro, o nome Kolae levando à home, textos nos 7 idiomas e `noindex`. A API continua respondendo JSON nos erros.

### D54 · QR Code da quadra (02/11/2026)
- **O quê:** na Visão geral, o botão "QR Code" abre um cartaz pronto para imprimir (faixa azul do Kolae, nome da quadra, QR para a página pública, "Escaneie para ver os horários e falar com o dono" e o endereço) e o botão "Baixar imagem" (PNG). Só para quadra com página pública.
- **Como:** o QR é gerado no navegador (`qrcode-generator`, cdnjs) e desenhado num canvas, sem biblioteca no servidor. Desenhar em pixel inteiro importa: com meio pixel apareciam linhas brancas entre os quadradinhos e o leitor não reconhecia o código.
- **Onde:** `partials/venue_qr.php`.

### D55 · E-mail do Kolae pelo Titan, com modelo próprio (02/11/2026)
- **Envio:** `nao-responda@kolae.online` pelo Titan (`smtp.titan.email`, porta 465, SSL), o e-mail que vem com o plano da HostGator. Precisou ligar "Enable Titan on Other Apps" no webmail. Passo a passo em `docs/hospedagem.md`.
- **Modelo:** o e-mail de redefinição de senha passa a ter o visual do Kolae (faixa azul, botão "Criar nova senha", validade de 60 minutos, uso único, "não foi você?", link escrito para copiar, rodapé) e uma **versão em texto puro** junto, que ajuda a não cair no spam. Feito com tabelas e estilos inline, porque clientes de e-mail ignoram CSS moderno.
- **Onde:** base reutilizável `views/emails/layout.php` e o e-mail em `views/emails/password_reset.php`, montados por `Mailer::passwordResetMessage`. Um e-mail novo no futuro usa a mesma base.
- **Idioma:** o e-mail sai em português, como as mensagens da API.

## Testes

### D56 · Testes antes do deploy e testes de clique no repositório (02/11/2026)
- **No GitHub Actions:** o workflow passa a ter o job `testes`, com um banco MariaDB descartável: confere a sintaxe de todo o PHP, carrega `database/schema.sql` e o seed de teste e roda os testes de `tests/*_test.php`. **O deploy só roda se os testes passarem.** Em PR para a `main`, roda só os testes (o PR mostra ✓ ou ✗ antes do merge). O `config.php` dos testes é criado na hora, sem nenhum dado da produção.
- **Testes de clique em `tests/e2e/`:** os 15 roteiros que conferiram as S4, S5 e o que veio depois (cerca de 390 passos) saíram do ambiente do Claude e entraram no repositório, com `npm run test:e2e`. O `rodar.js` recria os dados de teste antes de cada um (`preparar_banco.php`, que recusa banco que não seja local) e mostra um resumo. Caminhos configuráveis por variáveis de ambiente, para rodar em outro PC.
- **Por que os de clique não rodam no Actions:** precisam do site inteiro no ar (Apache, PHP, MySQL, navegador) e levam uns 10 minutos; ficam para antes de cada PR, no PC de quem mexeu.
- **Achado no caminho:** o `schema.sql` não tinha a coluna `position` do patch 006; uma instalação nova ficaria quebrada nas fotos. Corrigido, e agora o Actions carrega o `schema.sql` a cada PR, então isso não passa de novo.

## Visual

### D57 · Telas de entrada redesenhadas (02/11/2026)
- **O quê:** login, cadastro, esqueci a senha e nova senha passam a usar a mesma base, `partials/auth_layout.php`. No computador: foto com a frase da marca e três pontos do Kolae à esquerda (fixa enquanto o formulário rola) e o formulário à direita. No celular: a foto vira uma faixa no topo e o formulário fica num cartão logo abaixo. Botões de tema e idioma em todas as telas. A tela de nova senha, que só tinha tema escuro e textos fixos, entrou no mesmo visual.
- **Campos:** `partials/auth_field.php` (rótulo, ícone, mostrar/esconder senha, aviso de Caps Lock) e as classes `.auth-*` em `src/input.css`. No cadastro e na nova senha, a dica "Pelo menos 8 caracteres" fica verde ao chegar no mínimo e a confirmação avisa enquanto digita se as senhas coincidem, sem deixar enviar diferente. Depois de um erro, o e-mail digitado volta preenchido (guardado só na aba, em `sessionStorage`).
- **JS:** um módulo só, `src/js/pages/auth_form.js`, no lugar de `login.js` e `register.js` (que tinham "Aguarde..." fixo em português) e de `forgot_password.js` (que nunca era carregado). Tudo é aprimoramento: sem JS os formulários funcionam igual e o servidor continua validando tudo.
- **Idioma:** o seletor virou `partials/lang_modal.php`, usado também no cabeçalho público, e o link de idioma passou a manter os outros parâmetros da página: antes, trocar o idioma apagava os filtros de `/quadras` e, na nova senha, o token.
- **Teste:** `tests/e2e/testes/entrada.js`.
- **Varredura depois do redesign:** todas as páginas (pública, dono e admin) abertas no PC e no celular, em pt-BR e hindi, procurando aviso do PHP, erro de JS, imagem quebrada e rolagem horizontal: nenhum. Os achados foram de tradução, de título de aba e dois erros reais (comodidades que não eram salvas e a edição de esporte), todos no CHANGELOG.
- **Comodidades:** o assistente mostra só as 3 que existem no banco. Ter Wi-Fi, estacionamento etc. de verdade pede colunas novas, API e app; ficou em `docs/sugestoes.md`.
- **Data de nascimento:** três listas (`partials/auth_birthdate.php`) no lugar do calendário nativo, que mostrava "dd/mm/aaaa" e obrigava a voltar ano a ano até 1990. Anos só de quem tem entre 18 e 100. O JS monta o campo `birthdate`; sem JS, o servidor monta a partir das listas. Depois de um erro, nome, e-mail e data voltam preenchidos (`data-remember`, nunca senha).
- **Tema automático:** `localStorage.theme` vazio quer dizer "seguir o sistema". Antes, o `initTheme` gravava o tema em toda página aberta, e o automático nunca valia depois da primeira visita.
- **Contraste:** botões com texto branco usam `bg-brand-600` (#0069CC, 5,6:1); o `#0084FF` (3,6:1) fica para ícones, destaques e fundos claros. Para voltar ao azul do app nos botões, é trocar de volta (perde o contraste mínimo).
- **Teste:** `tests/e2e/testes/preferencias.js` (menu e tema em todas as páginas do dono, tema e idioma nas Preferências).
- **Avisos (toast):** os controllers continuam mandando `?status=` / `?error=`; `App\Core\Flash::fromQuery(contexto)` traduz cada código por página (painel, perfil, segurança) e `partials/toasts.php` entrega ao `src/js/ui.js`, que mostra e limpa a URL. Página nova: definir `$toastContext` antes do `owner_header.php` e acrescentar os códigos no `Flash::MAP`. O `ui.js` é um arquivo à parte do `bundle.js` para servir também ao admin, que tem script de tema próprio.
- **Confirmação:** `data-confirm="texto"` no formulário (mais `data-confirm-danger`, `data-confirm-ok`, `data-confirm-cancel`) ou `await Kolae.confirm(...)` no JS. Não usar `confirm()` do navegador.
- **CNPJ:** dono sem CNPJ válido não cria nem gerencia quadra, em nenhuma página; o admin continua sem precisar. Um CNPJ salvo que não abre com a chave atual conta como ausente.

### D58 · Confirmação de e-mail (versão leve) e miniaturas das fotos (02/11/2026)
- **Confirmação de e-mail:** no cadastro (site e `POST /auth/register`) o Kolae manda "Confirme seu e-mail" pelo Titan, no visual do e-mail de senha. **Ninguém é bloqueado:** quem não confirmou vê um aviso no painel com "Reenviar" (um envio a cada 5 minutos) e, no perfil, "Ainda não confirmado"; depois de abrir o link, o aviso some e o perfil mostra "E-mail confirmado". Grava em `users.email_verified_at`, que já existia sem uso (patch 007 só garante a coluna).
- **Link sem tabela nova:** `EmailVerification::link` leva o id, a validade (24 h) e uma assinatura HMAC com o `JWT_SECRET` que inclui o e-mail. Não dá para forjar e perde a validade sozinho se o e-mail mudar. Link vencido ou adulterado mostra o aviso certo; usar de novo não dá erro. Funciona logado (volta ao painel) ou não (vai ao login).
- **API:** o objeto `user` ganha `email_verified` (só informativo; o app pode ignorar).
- **Por que leve:** bloquear até confirmar exigiria mudar o app e travaria quem se cadastra na apresentação esperando o e-mail. A versão completa fica para o Kolae 2.0 (`docs/sugestoes.md`).
- **Endereços de teste:** `Mailer::isTestAddress` não envia para `.local`, `.test`, `.example`, `.invalid` (só registra no log). Os testes criam contas como `dono@teste.local`; mandar pelo Titan só geraria devoluções e prejudicaria o remetente.
- **Miniaturas:** cada foto de quadra ganha `thumb_<nome>.jpg` (até 640 px, qualidade 75) ao ser enviada; uma foto de 240 KB vira uma miniatura de ~10 KB. Home, `/quadras`, painel e as fotos pequenas da galeria usam a miniatura (`ImageHelper::thumbUrl`, que cai na foto inteira se a miniatura não existir); a foto grande, o carrossel do celular, a tela cheia e a API continuam com a foto inteira. Apagar a foto apaga a miniatura. **Fotos antigas:** o botão "Otimizar fotos" do admin cria as miniaturas que faltam, e o card mostra quantas fotos estão sem.
- **Testes:** `tests/e2e/testes/confirmacao.js` (aviso, reenviar com espera, link válido, vencido e adulterado, selo, API) e as miniaturas em `fotos.js`. `tests/e2e/link_confirmacao.php` gera o link no banco local.

## Versões

### D59 · v1.6.0 como versão de entrega, juntando as S3 a S6 (02/11/2026)
- **O quê:** as S3, S4, S5 e S6 e o polimento (D29–D58) saem juntos na **v1.6.0**, a versão de entrega, em vez de uma versão por sprint como pede a D18. O `[Não lançado]` do CHANGELOG virou a seção `[1.6.0]`, com um resumo no topo.
- **Por quê:** o backend andou semanas adiantado e as releases de cada sprint não foram feitas na hora; marcar quatro versões retroativas exigiria separar os itens por sprint e marcar commits antigos, sem ganho para a entrega. Para a banca, o histórico fica "v1.6.0: versão de entrega" mais as correções.
- **Daqui até a entrega:** correções viram **v1.6.1, v1.6.2…** (patch); funcionalidade nova só depois do congelamento, se for decidido, como v1.7.0. A D18 continua valendo para o Kolae 2.0.
- **Passo a passo de cada release:** `docs/hospedagem.md`, "Checklist de cada release".

## Depois da v1.6.0

### D60 · Redes sociais da quadra (05/11/2026)
- **O quê:** o dono informa até quatro perfis por quadra: Instagram, TikTok, Facebook e site. O app (no detalhe da quadra, abaixo do "Falar com o dono") e as páginas públicas mostram um ícone para cada perfil informado. Proposta em [`proposta-redes-sociais.md`](proposta-redes-sociais.md), aprovada pelo João e pelo Rafael.
- **Na quadra, não no dono:** um dono pode ter várias quadras, cada uma com o seu perfil. São quatro colunas opcionais em `venues` (`NULL` = não informado): `instagram` e `tiktok` (só o usuário, sem `@`, até 30 caracteres), `facebook_url` e `website_url` (endereço completo com `https://`, até 255).
- **O painel aceita o que a pessoa colar:** Instagram e TikTok aceitam `@usuario`, `usuario` ou o link do perfil e guardam só o usuário (letras, números, `.` e `_`). Facebook e site precisam de `https://`; sem ele, o site completa. O Facebook precisa ser de `facebook.com` ou `fb.com`.
- **API:** `GET /venues/{id}` ganha o objeto `socials`, **sempre presente**, com `null` no que não foi informado; com os quatro `null`, o app esconde a seção (como o `description`, D30). Fica **fora** do resumo de `GET /venues`: a lista não precisa disso. O app monta os links do Instagram (`https://instagram.com/<usuario>`) e do TikTok (`https://www.tiktok.com/@<usuario>`) e abre o Facebook e o site como vieram.
- **Por quê:** é como o atleta confere fotos recentes, eventos e a "cara" do lugar antes de alugar; para o dono, é divulgação de graça; na banca, mostra a quadra como um negócio de verdade.
- **Fora, por enquanto:** outras redes (YouTube, X, Kwai) e os ícones no card do mapa (o card já está cheio). Uma rede nova é um campo a mais no `socials`, sem quebrar o app.
- **Versão:** funcionalidade nova depois da v1.6.0, então sai na **v1.7.0** (D59).
- **Onde:** `docs/api.md` (`GET /venues/{id}`); a seguir, o patch do banco, a seção "Redes sociais" na aba Dados (item opcional da "Quadra completa", D47), o `VenueApiController` e as páginas públicas (D49, D51). No app, já pronto com dados de exemplo (PR #7 do `kolae-mobile`).
