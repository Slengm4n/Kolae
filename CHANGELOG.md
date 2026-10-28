# Changelog

Mudanças relevantes do Kolae, da mais recente para a mais antiga.

O formato segue o [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/) e o projeto usa [Versionamento Semântico](https://semver.org/lang/pt-BR/):

- **minor** (`1.5.0`): uma por sprint, com as funcionalidades entregues;
- **patch** (`1.4.1`): correções urgentes entre sprints;
- **major** (`2.0.0`): reservada para o Kolae 2.0 em Laravel.

Cada versão tem uma tag `vX.Y.Z` e uma release no GitHub com o texto da seção correspondente.

## [Não lançado]

### Adicionado
- Banco (patch `database/patches/004_horario_whatsapp_agenda.sql`): horário de funcionamento das quadras (`venue_hours`), esportes de cada quadra (`venue_sports`), WhatsApp do dono (`users.whatsapp`) e partidas no modelo novo, agendadas pelo dono (`matches` com esporte opcional e `renter_name`).

### Removido
- Tabela `match_participants` e coluna `matches.max_players`, do modelo antigo de entrar e sair de partida (vazias).

### Documentação
- Contrato da S3 em `docs/api.md` (proposta): `GET /venues` com filtros, paginação e status; `GET /venues/{id}` com fotos, endereço, horário e dono; `GET /venues/{id}/schedule`.

## [1.5.0] - 2026-10-28

Sprint 2 (adiantada): API de contas para o app no padrão do Laravel, papel `owner` por CNPJ, fuso de Brasília e correções no painel. Inclui também o prazo novo da entrega (28/11) e o modelo de status da quadra aprovado.

### Adicionado
- API da Sprint 2 para o app: cadastro (`POST /auth/register`, já devolve o token), esqueci a senha (`POST /auth/forgot-password`), perfil (`GET` e `PATCH /me`, que edita só o nome: a data de nascimento não é editável, como no site), foto de perfil (`POST /me/avatar`) e troca de senha (`PUT /me/password`).
- Quem cadastra CNPJ no site passa a ser `owner` (responsável por quadra). O patch `database/patches/003_owner_por_cnpj.sql` converte quem já tinha CNPJ.

### Alterado
- **A API muda de formato** (padrão do Laravel): sucesso `{ "data": ... }` e erro `{ "message", "code", "errors": { "campo": ["..."] } }`. Datas em ISO 8601 com fuso. Detalhes em `docs/api.md`.
- O token de login passa a valer 30 dias (antes, 8 horas).
- `GET /api/v1/sports` devolve só `id`, `name`, `description` e `icon_path`.
- O e-mail de recuperação de senha é montado num lugar só (`App\Core\Mailer`), usado pelo site e pela API.
- A conexão com o banco usa `utf8mb4` e mostra uma mensagem genérica (sem detalhes do servidor) quando o banco está fora do ar.

### Corrigido
- Painel de admin: o seletor de papel do usuário só tinha "user" (valor que não existe no banco) e "admin", então salvar a edição de um responsável por quadra o rebaixava para `player`. Agora oferece `player`, `owner` e `admin`, na criação e na edição.
- Datas com fuso diferente entre a produção (UTC) e o ambiente local (Brasília): a sessão do MySQL passa a usar o horário de Brasília, o mesmo do PHP.
- Upload de imagem quebrava com erro fatal e deixava o arquivo para trás quando a extensão GD do PHP estava desligada; agora salva a imagem (já validada) sem otimizar.
- Painel de admin: ao editar ou criar uma quadra, "Cancelar" e o link "Painel" levavam para o painel do usuário (`/dashboard`). Agora voltam para a lista de quadras do admin. O destino passou a ser decidido pela rota atual, e não pelo Referer do navegador.
- A tela de criar quadra ignorava o painel de origem e sempre enviava o formulário pela rota do usuário.
- As mensagens de "quadra cadastrada" e "dados da quadra atualizados" não apareciam na lista do admin (o código de status enviado não era o esperado).
- Traduções faltando: a mensagem de "senhas não conferem" ficava vazia na redefinição de senha em português, italiano e japonês, e no cadastro e na troca de senha em inglês, espanhol, hindi e chinês. O título de erro da edição de quadra também faltava nesses quatro idiomas.

### Documentação
- Documentação reorganizada: `docs/README.md` (índice, cronograma do backend e do app, e como uma mudança anda até a produção), `docs/decisoes.md` (registro das decisões D01–D20, com data e motivo) e `docs/modelo-status.md` (proposta de status da quadra, horário de funcionamento e agenda para as S3/S4). O `docs/CLAUDE.md` ficou só com o contexto e aponta para esses documentos.
- Prazo da entrega antecipado para 28/11 (congelamento em 21/11), com o cronograma refeito em `docs/README.md` e a troca de e-mail com código fora do escopo (D28). Modelo de status da quadra, horário de funcionamento e agenda aprovado: `docs/modelo-status.md` com as respostas da revisão e as decisões D21–D27.
- `docs/api.md`: contrato da Sprint 2 (cadastro, esqueci a senha, perfil, avatar e troca de senha) e novo formato das respostas no padrão do Laravel (`{ data }` / `{ message, code, errors }`), com datas ISO 8601, dinheiro em centavos e coordenadas numéricas. Plano das S3 a S5 atualizado para o novo modelo: partidas agendadas pelo dono no site e status da quadra calculado pela API.
- `config.example.php` passa a trazer o bloco de e-mail (`SMTP_*`) usado pelo "esqueci a senha". Antes, quem montava o ambiente pelo exemplo ficava sem essas constantes e a recuperação de senha parava com erro fatal.
- `docs/setup.md`: passo para ligar as extensões `gd` e `zip` do PHP, como instalar as dependências sem o Composer global, como configurar o SMTP (inclusive senha de app do Gmail) e novos problemas comuns.

## [1.4.0] - 2026-10-27

Sprint 1: correções críticas de segurança, banco alinhado ao código e API pronta para o app mobile.

### Segurança
- Upload de imagens de quadras validava só o tipo informado pelo navegador e aceitava qualquer arquivo, inclusive `.php`. Agora todo upload (quadras e avatar) é validado pelo conteúdo real e salvo com extensão segura.
- `uploads/.htaccess` bloqueia a execução de scripts e só libera imagens.
- Removido o modo debug do SMTP no "esqueci a senha", que expunha o diálogo com o servidor de e-mail.
- Corrigido acesso indevido (IDOR) na edição de quadras: não é mais possível alterar o endereço ou apagar fotos de quadras de outras pessoas.
- Corrigido XSS pelo nome da quadra na tela de edição.

### Adicionado
- API: login (`POST /api/v1/auth/login`) devolve token JWT e os dados do usuário.
- API: todas as respostas seguem o envelope `{ success, data }` / `{ success, error: { message, code, fields } }`, inclusive 404, 405 e erros internos.
- Contrato da API em `docs/api.md` e guia de ambiente local em `docs/setup.md`.
- Banco documentado: diagrama em `database/kolae.dbml`, estrutura em `database/schema.sql` e scripts de mudança em `database/patches/`.
- Tabela `user_tokens` (base para o "lembrar de mim") e coluna `matches.max_players`.
- O ambiente local aceita qualquer porta e o IP da rede, para o app no celular acessar a API do notebook.

### Corrigido
- Login da API nunca funcionava (lia uma coluna que não existe).
- Editar quadra quebrava desde a normalização do endereço.
- A tela de edição não mostrava as fotos atuais da quadra.
- Editar uma quadra inativa a reativava.
- Falha na geocodificação ao salvar apagava as coordenadas e a quadra sumia do mapa.
- Tipo de piso gravado em formatos diferentes na criação e na edição (e cortado no banco); agora usa valores padronizados.

### Removido
- Phinx e as migrations antigas, que estavam fora de sincronia com o banco.
- `database/Kolae_db_new.sql`, substituído por `database/schema.sql`.

## [1.3.0] - 2026-02-09

> Publicada com a tag `v1.3`.

### Adicionado
- Integração com Vite e Tailwind CSS v4, com redesign da interface.
- Alternância entre tema claro e escuro.
- Internacionalização (seleção de idioma).

## [1.2.2] - 2025-11-20

### Segurança
- Proteção preventiva contra roubo de sessão.

### Corrigido
- Diferença de maiúsculas/minúsculas em nomes de pastas no servidor.

## [1.2.1] - 2025-11-19

### Corrigido
- Correções no cadastro de quadras.

### Alterado
- Refatoração do model de endereço e melhorias na visão de usuários do admin.
- Páginas de erro personalizadas.

## [1.2.0] - 2025-11-18

> Publicada com a tag `1.2.0` (sem o `v`).

### Adicionado
- Novo visual com dark mode, animações e layout mobile first.
- Mapa com Google Maps vetorial, busca flutuante e painel lateral do local.

### Alterado
- Tailwind compilado localmente em vez do CDN.

## [1.1.0] - 2025-11-16

> A release "v1.1.0" do GitHub aponta para a tag `v1.0.0`; não existe tag `v1.1.0`.

### Adicionado
- Primeira versão da API para comunicação externa.
- Recuperação de senha por e-mail.

### Alterado
- Cadastro de gestores passa a usar CNPJ em vez de CPF.

## [1.0.0] - 2025-10-22

- Lançamento inicial.

[Não lançado]: https://github.com/Slengm4n/Kolae/compare/v1.5.0...HEAD
[1.5.0]: https://github.com/Slengm4n/Kolae/compare/v1.4.0...v1.5.0
[1.4.0]: https://github.com/Slengm4n/Kolae/compare/v1.3...v1.4.0
[1.3.0]: https://github.com/Slengm4n/Kolae/compare/v1.2.2...v1.3
[1.2.2]: https://github.com/Slengm4n/Kolae/compare/v1.2.1...v1.2.2
[1.2.1]: https://github.com/Slengm4n/Kolae/compare/1.2.0...v1.2.1
[1.2.0]: https://github.com/Slengm4n/Kolae/releases/tag/1.2.0
[1.1.0]: https://github.com/Slengm4n/Kolae/releases/tag/v1.0.0
[1.0.0]: https://github.com/Slengm4n/Kolae/releases/tag/v1.0.0
