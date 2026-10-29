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

### D13 · Opção C: o app usa o backend do notebook do João (27/10/2026)
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
- **Pendente:** escolher a hospedagem (D13) na primeira semana de novembro, porque ela decide onde a apresentação roda.

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
