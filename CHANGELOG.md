# Changelog

Mudanças relevantes do Kolae, da mais recente para a mais antiga.

O formato segue o [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/) e o projeto usa [Versionamento Semântico](https://semver.org/lang/pt-BR/):

- **minor** (`1.5.0`): uma por sprint, com as funcionalidades entregues;
- **patch** (`1.4.1`): correções urgentes entre sprints;
- **major** (`2.0.0`): reservada para o Kolae 2.0 em Laravel.

Cada versão tem uma tag `vX.Y.Z` e uma release no GitHub com o texto da seção correspondente.

## [Não lançado]

**Banco:** patch 008 (`database/patches/008_redes_sociais_da_quadra.sql`), aplicado na produção em 05/11 antes do merge.

### Adicionado
- **Redes sociais da quadra** (D60): o dono informa Instagram, TikTok, Facebook e site na aba Dados, colando o `@`, o usuário ou o link do perfil; o site guarda só o usuário no Instagram e no TikTok e completa o `https://` no Facebook e no site. Prévia ao vivo de como aparece no app, a mesma validação no navegador e no servidor e textos nos 7 idiomas. Entra na "Quadra completa" da Visão geral como item opcional, sem contar no total.
- **API:** `GET /venues/{id}` ganha o objeto `socials` (`instagram`, `tiktok`, `facebook_url`, `website_url`), sempre presente, com `null` no que não foi informado. A lista `GET /venues` não muda.
- **Visão geral e página pública da quadra:** seção "Redes sociais" abaixo do endereço, com os links. Na página pública, sem redes, a seção some (como no app).
- Testes: `tests/venue_socials_test.php` (53 casos, sem banco) e o de clique `tests/e2e/testes/redes.js` (40 passos: painel, API, Visão geral, página pública, celular, inglês e admin).

## [1.6.0] - 2026-11-02

Versão de entrega: Sprints 3 a 6 e o polimento antes do congelamento. Quadras com status ao vivo na API, telas do dono no site (horário, agenda, dados, fotos e visão geral), página pública da quadra, página Quadras com mapa, QR Code, segurança essencial (CSRF, limite de login, "lembrar de mim"), Termos e Privacidade, produção na HostGator com e-mail pelo Titan, testes antes de cada deploy e, por fim, telas de entrada redesenhadas, avisos no visual do Kolae, confirmação de e-mail, miniaturas das fotos e ajustes de desempenho e acessibilidade.

**Banco:** patches 004 a 007 (`database/patches/`). **Depois do deploy:** apertar "Otimizar fotos" no painel do admin para criar as miniaturas das fotos antigas.

### Adicionado
- **Confirmação de e-mail** (D58): e-mail "Confirme seu e-mail" no cadastro (site e app), aviso no painel com "Reenviar" e selo no perfil. Não bloqueia nada. A API devolve `email_verified`. Aplicar o patch 007 antes (só garante a coluna; normalmente não muda nada).
- **Miniaturas das fotos** (D58): cards e grades usam uma versão de até 640 px (~10 KB no lugar de ~240 KB). Depois do deploy, apertar "Otimizar fotos" no admin para criar as miniaturas das fotos antigas.
- **Avisos e confirmações no visual do Kolae** (D57): os avisos depois de salvar ou de um erro aparecem como pop-up animado (ícone, barra de tempo que pausa com o mouse, botão de fechar, lido por leitores de tela) e somem da URL, então recarregar não repete. As janelas cinzas do navegador (apagar foto, cancelar partida, excluir no admin) viraram uma janela do Kolae, com o foco no "Voltar" em ação perigosa. Base em `src/js/ui.js`, `App\Core\Flash` e `partials/toasts.php`.
- **Configurações > Preferências** (D57): tema Claro, Escuro ou Automático (segue o celular ou o computador, inclusive se mudar com a página aberta) e idioma, sem precisar do seletor da home.
- **Testes antes do deploy** (D56): o GitHub Actions confere a sintaxe do PHP e roda os testes de PHP num banco de teste em todo PR e antes de publicar; se algum falhar, a produção não muda.
- **Testes de clique no repositório** (D56): `npm run test:e2e` roda os 15 roteiros de navegador em `tests/e2e/`, com o banco de teste preparado automaticamente.
- **E-mail de redefinição de senha no visual do Kolae** (D55), enviado por `nao-responda@kolae.online` (Titan), com botão, validade do link e versão em texto puro.
- **QR Code da quadra** (D54): cartaz pronto para imprimir na Visão geral, com o QR para a página pública e o botão de baixar em PNG.
- **Páginas de erro novas** (D53): 404, 403 e "Página expirada" no visual do site, com tema claro/escuro e botões para seguir.
- **Página Quadras** (D51): `kolae.online/quadras` com busca, filtros (esporte, disponível agora, coberta, iluminação, preço), "Perto de mim" e mapa com o status de cada quadra; no celular, lista e mapa alternados. O menu "Quadras" agora abre essa página.
- **Guia passo a passo nas abas Horário e Agenda** (D50): abre sozinho na primeira visita e pelo botão "Como funciona?", destacando cada parte da tela com uma explicação curta, nos 7 idiomas.
- **Página pública da quadra** (D49): `kolae.online/quadra/{id}`, sem login, com galeria, informações, status ao vivo, horários ocupados de hoje (sem nomes), "Falar com o dono" pelo WhatsApp e "Compartilhar". O link mostra foto e nome na prévia do WhatsApp. Os cards da home abrem nela.
- **Fotos otimizadas** (D48): fotos novas viram JPEG de até 1600 px, giradas pelo celular (uma foto de 6 MB fica com ~250 KB). Botão "Otimizar fotos" no painel do admin para as antigas.
- **Aba Visão geral da quadra** (D47): a quadra como aparece no app, com galeria (grade, carrossel no celular e tela cheia), esportes, estrutura, descrição, horário, endereço, o dia de hoje, o contato e a lista "Quadra completa". O card do painel abre nela.
- **Aba Fotos da quadra** (D46): enviar fotos com prévia, escolher a capa, mudar a ordem e apagar, até 10 por quadra. A capa escolhida vale no painel, no mapa e na API. Aceita WebP. Precisa do patch 006.
- **Proteção contra CSRF** (S5, D43): todos os formulários do site levam um token da sessão; envio sem ele mostra a página "Página expirada" (403), nos 7 idiomas.
- **Limite de tentativas de login** (S5, D45), no site e na API: 5 erros seguidos na mesma conta em 15 minutos bloqueiam o login até vencer o prazo. A API responde `429 TOO_MANY_ATTEMPTS` com `Retry-After`. Precisa do patch 005.
- **Termos de Uso e Política de Privacidade** (D42): páginas `/termos` e `/privacidade`, com índice, texto conforme a LGPD, tema claro/escuro e versão para celular. Ficam no rodapé do site e no cadastro, e o app abre as mesmas URLs. O texto é só em português; título e menu seguem o idioma, com um aviso nos outros idiomas.
- **Aba Horário da quadra** (S4, D35), para o dono e o admin: os 7 dias com interruptor aberto/fechado e "abre / fecha", preenchimento rápido (seg a sex, sáb e dom, todos os dias), validação na hora e no servidor, resumo "Como aparece no app", aviso de quadra sem horário (D23) e lista de partidas futuras que ficaram fora do horário (D27). As páginas da quadra ganham um topo comum com o selo de status ao vivo e as abas Dados, Horário e Agenda. Textos nos 7 idiomas.
- **Aba Agenda da quadra** (S4, D37, D39), para o dono e o admin: um dia por vez (setas, "Hoje" e escolher data), linha do tempo com as faixas livres e ocupadas e a marca do "agora", lista das partidas do dia com "Cancelar" (com confirmação) e "Agendar partida" (início, fim, atalhos de duração, esporte e "para quem" opcionais). Valida no navegador e no servidor: fora do horário, conflito com outra partida, horário que já passou, dia fechado. "Para quem" fica só no site; o app vê só o horário ocupado. As partidas fora do horário na aba Horário levam direto ao dia na Agenda.
- **Aba Dados da quadra** (S4): preço como "Valor por hora", "Grátis" ou "Não informar" (D31), "Sobre a quadra" com contador (D30), esportes da quadra em chips (D29), situação "Disponível" ou "Em manutenção" (D34) e o WhatsApp pedido no próprio formulário quando o dono ainda não tem (D38). Depois de salvar, a pessoa continua na aba Dados com a confirmação.
- **Cadastro de quadra** (S4, D36): o passo a passo ganha os esportes (passo 3) e, nos detalhes, preço em "Valor por hora", "Grátis" ou "Não informar", "Sobre a quadra" e o WhatsApp do dono quando ele ainda não tem. Ao terminar, a pessoa vai direto para a aba Horário da quadra nova, com o aviso de que sem horário ela aparece como "Fechada".
- **Painel do dono com status ao vivo** (S4, D40): cada card de quadra mostra o selo (Disponível, Em uso, Fechada, com "até"/"abre") pela mesma regra do app, o selo "Desativada" na quadra desativada, o aviso de quadra sem horário com link para a aba Horário (D23) e atalhos para as abas Dados, Horário e Agenda.
- Seed de demonstração para o notebook (`database/seeds/demo_notebook.sql`, só local): horário, esportes e WhatsApp fictício nas quadras de verdade e partidas a partir da hora atual, para o app ter quadras "Em uso" e "Disponível". O seed de teste da S3 deixa de apontar para fotos que não existem.
- API da Sprint 3 para o app: `GET /venues/{id}` (fotos, descrição, endereço, horário de funcionamento e dono com WhatsApp) e `GET /venues/{id}/schedule` (faixas livres e ocupadas do dia).
- Status da quadra calculado pela API (`available`, `in_use`, `closed`) com `status_until`, a partir do horário de funcionamento e das partidas agendadas pelo dono (`App\Core\VenueSchedule`).
- Base das telas do dono (S4), ainda sem tela: validação do horário de funcionamento (D21, D22) e das partidas (sem sobrepor outra, dentro do horário, D27), aviso de partidas que ficam fora depois de mudar o horário, e gravar horário, agendar e cancelar partidas (`VenueHour`, `Game`).
- Testes automatizados em `tests/` (fora do deploy): regra do status e validações (`venue_schedule_test.php`, sem banco) e os models da agenda no banco local (`agenda_db_test.php`).
- Banco (patch `database/patches/004_horario_whatsapp_agenda.sql`): horário de funcionamento das quadras (`venue_hours`), esportes de cada quadra (`venue_sports`), WhatsApp do dono (`users.whatsapp`), descrição da quadra (`venues.description`) e partidas no modelo novo, agendadas pelo dono (`matches` com esporte opcional e `renter_name`).
- **WhatsApp no perfil** (D24), para donos e admins: aceita o número digitado de qualquer jeito e grava só os dígitos com 55 (`User::normalizeWhatsapp`); avisa o dono sem número de que o "Falar com o dono" não aparece no app. Nos 7 idiomas. Teste em `tests/whatsapp_test.php`.

### Alterado
- Painel, perfil, segurança e a aba Dados da quadra usam o mesmo cabeçalho das outras páginas do painel (`partials/owner_header.php`); o menu das Configurações virou `partials/config_nav.php`. E-mail de contato do rodapé: `contato@kolae.online`.
- **Desempenho e acessibilidade (Lighthouse):** o JS do site caiu de 161 KB para 7,5 KB (tirado o carrossel Swiper, que nenhuma página usava); o navegador passa a guardar CSS e JS por um ano e imagens por 30 dias; o vídeo da home (1,8 MB) só carrega no computador; botões azuis com texto branco ficaram num tom mais escuro (`brand-600`) para atingir o contraste mínimo; telas de entrada com descrição para buscadores. Acessibilidade da home e do login foi a 100.
- **Telas de entrada redesenhadas** (D57): login, cadastro, esqueci a senha e nova senha com o mesmo visual, foto da marca no computador e faixa no topo no celular, tema e idioma em todas, mostrar senha, aviso de Caps Lock, confirmação de senha ao digitar e o e-mail devolvido depois de um erro. Data de nascimento em três listas (dia, mês pelo nome e ano, na ordem de cada idioma), com aviso na hora de data inexistente ou de menor de 18.
- **Produção na HostGator, com domínio próprio: `https://kolae.online`** (D41, substitui a D13). A API de produção (`https://kolae.online/api/v1`) responde JSON direto, então o app passa a usar a produção, sem depender do notebook. Banco, fotos e configuração migrados do InfinityFree, mantendo as chaves (CNPJs e logins continuam valendo); e-mail do "esqueci a senha" corrigido (`smtp.gmail.com`, `tls`, 587).
- **Preparação para a HostGator**: o `.htaccess` deixa de fixar `RewriteBase /kolae/`, então o mesmo arquivo funciona no XAMPP (`/kolae`) e na raiz do domínio; a pasta do deploy por FTP vem do secret `FTP_SERVER_DIR` (sem ele, `/`, a raiz da conta de deploy). Passo a passo da migração e o que falta configurar em `docs/hospedagem.md`.
- **API: fotos que não existem no servidor saem como `null`** (`cover_image_url`, `avatar_url`, `owner.avatar_url`) ou ficam fora de `images`, em vez de um link quebrado. Acontece quando o banco é copiado sem a pasta `uploads/` (ex: o notebook) ou se um arquivo some. `ApiFormat::uploadUrl`.
- **Nova home**, com a identidade do app: abertura com prévia ilustrada do app e os selos de status, "Como funciona" em 3 passos, os status das quadras, quadras reais cadastradas (foto, bairro, piso, preço), chamada para responsáveis por quadra, "Sobre nós" e parceiros. Botões "Entrar" e "Cadastre-se" visíveis no topo; tudo nos 7 idiomas.
- **Animações na home**: abertura em sequência, prévia do app com o selo alternando entre "Disponível" e "Em uso" e pinos pulsando, seções que surgem ao rolar, cabeçalho fixo que ganha fundo ao rolar e detalhes ao passar o mouse. Respeitam a opção "reduzir movimento" do sistema e, sem JavaScript, a página aparece completa e parada.
- **Identidade visual do app em todo o site**: azul `#0084FF` (paleta `brand` no `src/input.css`) no lugar do ciano, títulos na fonte Kanit e as cores de status do app (`status-badge`).
- Cabeçalho, rodapé e `<head>` das páginas públicas viraram peças reutilizáveis em `app/views/partials/`.
- **`GET /venues` muda de formato**: lista paginada (`meta`), com busca, filtros (esporte, status, preço, coberta, iluminação, área de lazer, piso, raio), ordenação por nome, preço ou distância, preço em centavos (`0` = grátis) e tipos do contrato. Quadras desativadas no site não aparecem (D34).

### Removido
- `POST /api/v1/games`: a partida é agendada pelo dono no site (D02).
- Tabela `match_participants` e coluna `matches.max_players`, do modelo antigo de entrar e sair de partida (vazias).

### Corrigido
- O painel não mostrava vários avisos (CNPJ obrigatório, inválido ou já usado, quadra não encontrada, sem permissão, quadra desativada): a pessoa voltava sem explicação. Trocar a senha mostrava o subtítulo da página no lugar de "Senha alterada".
- **CNPJ:** só "criar quadra" conferia o CNPJ; editar, excluir e as abas Horário, Agenda, Fotos e Visão geral abriam para um dono sem CNPJ. A regra agora vale em todas (`VenueAccess::requireCnpjOrRedirect`).
- Menu do perfil e botão de tema não funcionavam na Visão geral e na aba Fotos da quadra (a página não carregava o `bundle.js`).
- **Cadastro no site:** aceitava data de nascimento no futuro (o ano de 2050 contava como "mais de 18") e, para menor de idade, mostrava "erro desconhecido". Agora usa a mesma regra da API (`User::isValidAdultBirthdate`) e mostra a mensagem certa.
- **Assistente de nova quadra:** oferecia 9 comodidades, mas só iluminação, coberta e área de lazer eram salvas; Wi-Fi, estacionamento, vestiário, bar, coletes e bola sumiam sem aviso. Ficaram só as 3 que o banco guarda. O assistente também estava todo em português, fixo no HTML (passos, piso, endereço, botões e avisos): agora segue o idioma, e o limite de foto mostrado passou de "5MB" para o real (10 MB, com WebP).
- **Editar esporte (admin):** a tela ficava escura no tema claro, com o título invisível, e não marcava o ícone atual (lia `icon` em vez de `icon_path`), obrigando a escolher o ícone de novo para mudar só o nome.
- Quadra sem foto e perfil sem foto mostravam imagens de um serviço externo (placehold.co) com "Sem Imagem" e "Foto" em português; agora são desenhos locais, sem texto.
- Títulos de aba: 15 páginas do painel e do admin mostravam só "Kolae"; agora têm o nome da página.
- "Modo escuro/claro" e "Foto de perfil" fixos em português nos menus do dono.
- Cadastro: senhas com ícone de calendário, um `>` sobrando no celular, logo invisível no tema claro e o ✓ do checkbox que não aparecia; textos em pt-BR com maiúsculas fora do lugar ("Enviar Link", "Nome Completo", "Email").
- Traduções que faltavam: a dica de "e-mail enviado" do esqueci a senha em 5 idiomas e "Toque para escolher fotos" em 3.
- Trocar o idioma apagava os outros parâmetros da página (filtros de `/quadras` e o token da nova senha) (D57).
- **`schema.sql` sem a ordem das fotos:** faltava a coluna `position` do patch 006 (instalação nova quebrava as fotos).
- **"Esqueci a senha" sem limite** (D52): agora sai no máximo um e-mail a cada 5 minutos por conta, com a mesma resposta em todos os casos.
- **Avatar não era reduzido** (D48): o código calculava a imagem menor e gravava a original.
- **Piso na Visão geral** aparecia só em português; agora segue o idioma, como na home.
- **"Lembrar de mim" não funcionava** (S5, D44): o login gravava o token num lugar e a leitura procurava em outro. Agora usa `user_tokens` (selector e hash do validator), o cookie é HttpOnly e Secure no HTTPS, e o logout apaga o token.
- **Senha mínima de 8 caracteres no site** (S5, D08): cadastro e redefinição de senha aceitavam qualquer tamanho e a dica dizia 6. Agora conferem no navegador e no servidor, como a API e o perfil.
- **XSS no mapa do admin** (S5): os dados das quadras entravam no `<script>` sem escapar `</script>`, e a busca montava o nome da quadra como HTML. Agora o JSON sai escapado e o nome entra como texto. O mesmo escape foi aplicado na senha gerada mostrada em Usuários.
- Depois de um deploy, quem já tinha aberto o site via a página quebrada: o navegador reaproveitava o CSS e o JS antigos do cache. Os endereços de `style.css` e `bundle.js` passam a levar a versão do arquivo (`?v=...`, via `ViewHelper::asset`), que muda a cada deploy.
- Cadastro de quadra: marcar uma comodidade (iluminação, cobertura, área de lazer) dava erro de JavaScript, o cartão não ficava destacado e a capacidade da área de lazer nunca aparecia (o script procurava um id que não existe). Agora a capacidade aparece ao marcar e é zerada ao desmarcar.
- Perfil: uma variável declarada duas vezes derrubava o script da página (menu do usuário e pré-visualização da foto não funcionavam). O menu e o tema passam a vir só do `bundle.js`, sem tratar cada clique duas vezes.
- O seletor de idioma não funcionava pelo módulo JavaScript (esperava um evento que já tinha passado); a home dependia de uma cópia do código no HTML.
- O botão de tema mostrava os ícones de lua e sol ao mesmo tempo.
- O título da home vazava para fora da tela no celular.
- `npm run build` nunca terminava (o `vite.config.js` estava em modo de vigiar arquivos).
- Rodapé da home: ano fixo em 2025, newsletter que não enviava nada, links de redes sociais vazios e logo de parceiro quebrado (imagem externa).
- API na produção: números decimais (coordenadas, distância) saíam com dígitos demais (`-23.565065499999999…`) por causa da configuração do PHP do InfinityFree. Agora saem com o menor número de dígitos (`-23.5650655`).

### Documentação
- README, `docs/README.md` (cronograma), `docs/CLAUDE.md`, `docs/setup.md`, `docs/hospedagem.md` (checklist de cada release), `docs/api.md` e `tests/e2e/README.md` atualizados para a v1.6.0; D59 registra a v1.6.0 como versão de entrega.
- Contrato da S3 em `docs/api.md`, revisado pelo Rafael (D29–D33), e D34 (quadra desativada fora da API).

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

[Não lançado]: https://github.com/Slengm4n/Kolae/compare/v1.6.0...HEAD
[1.6.0]: https://github.com/Slengm4n/Kolae/compare/v1.5.0...v1.6.0
[1.5.0]: https://github.com/Slengm4n/Kolae/compare/v1.4.0...v1.5.0
[1.4.0]: https://github.com/Slengm4n/Kolae/compare/v1.3...v1.4.0
[1.3.0]: https://github.com/Slengm4n/Kolae/compare/v1.2.2...v1.3
[1.2.2]: https://github.com/Slengm4n/Kolae/compare/v1.2.1...v1.2.2
[1.2.1]: https://github.com/Slengm4n/Kolae/compare/1.2.0...v1.2.1
[1.2.0]: https://github.com/Slengm4n/Kolae/releases/tag/1.2.0
[1.1.0]: https://github.com/Slengm4n/Kolae/releases/tag/v1.0.0
[1.0.0]: https://github.com/Slengm4n/Kolae/releases/tag/v1.0.0
