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
- **Onde:** `UserController::storeCnpj`, `database/patches/003_owner_por_cnpj.sql`. Na produção, o patch 003 foi feito pelo painel de admin em 28/10 (phpMyAdmin do InfinityFree fora do ar).

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

### D11 · Troca de e-mail com código fica para a S5 (28/10/2026)
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

### D19 · Tradução do painel de admin fica para depois da entrega (28/10/2026)
- **O quê:** `admin/*`, `sports/*`, `venues/index.php` e as páginas de erro 403/404 continuam com texto fixo em português. Toda chave nova das telas públicas entra nos 7 idiomas.

### D20 · "Cancelar" na edição de quadra pelo celular fica para a S6 (28/10/2026)
- Polimento, se sobrar tempo.
