# Kolae — migração para a HostGator

Passo a passo para trocar a produção do InfinityFree para a HostGator (Plano Start, hospedagem compartilhada com cPanel). Motivo: o InfinityFree responde ao app com um desafio JavaScript em vez do JSON (D13).

> **Estado (02/11/2026): produção em `https://kolae.online` (D41), na versão v1.6.0.** Migração feita em 29/10: Passos 1 a 6 concluídos e conferidos: DNS e SSL, PHP 8.2, banco `joao5502_kolae` importado (7 quadras, 8 usuários), fotos, `config.php` (mesmas chaves, banco com senha nova; o SMTP passou depois para o Titan, ver [b](#b-e-mail-próprio-para-o-esqueci-a-senha--02112026)) e deploy por FTP com a conta `deploy@kolae.online` (`FTP_SERVER_DIR = /`). Site e API testados: home e login sem erro de JavaScript, `GET /venues`, detalhe, agenda, login com erro 401 e rota protegida, todos em JSON direto. **O que falta está em [O que falta](#o-que-falta-depois-da-migração), no fim.**

**Não cancele o InfinityFree** até o passo 8 dar certo: ele continua no ar e é de onde saem o banco, as fotos e as chaves.

## 1. cPanel: domínio e SSL (fazer primeiro)

- Domínio apontando para a HostGator (DNS). A propagação pode levar de algumas horas a 2 dias.
- SSL grátis ativo (AutoSSL / Let's Encrypt), para o site abrir em `https://`.

## 2. cPanel: PHP

- "Selecionar versão do PHP" ou "MultiPHP Manager": **8.2** (mínimo 8.0).
- Extensões ligadas: `gd`, `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `curl`.

## 3. cPanel: banco

- "Bancos de dados MySQL": criar o banco, criar um usuário com **senha nova** (não reaproveitar a do InfinityFree) e adicionar o usuário ao banco com **todos os privilégios**. Na HostGator, os nomes ganham o prefixo da conta (ex: `conta_kolae`).
- Anotar a versão do MySQL (phpMyAdmin, coluna da direita). MySQL 8 ou MariaDB 10.2+ é o esperado; no MySQL 5.7 tudo funciona, mas a trava `chk_venue_hours_interval` é ignorada (o PHP valida do mesmo jeito).

## 4. Tirar os dados do InfinityFree

- **Banco:** phpMyAdmin do InfinityFree → Exportar → SQL.
- **Fotos:** baixar por FTP a pasta `htdocs/uploads/` inteira (não está no git).
- **`config.php`:** baixar por FTP. As constantes `ENCRYPTION_KEY` e `ENCRYPTION_IV` **precisam ser as mesmas** na HostGator: sem elas, os CNPJs já salvos não podem mais ser lidos. Manter também o `JWT_SECRET` (assim quem já fez login no app continua logado).

## 5. Colocar os dados na HostGator

- **Banco:** phpMyAdmin da HostGator → banco novo → Importar o `.sql`. Depois, conferir se a tabela `venue_hours` existe; se não existir, importar também `database/patches/004_horario_whatsapp_agenda.sql`.
- **`config.php`:** partir do baixado no passo 4 e trocar só `DB_HOST` (normalmente `localhost`), `DB_NAME`, `DB_USER` e `DB_PASS`. Enviar pelo Gerenciador de Arquivos do cPanel para `public_html/`. Ele nunca vai pelo deploy (fica fora do git).
- **Fotos:** compactar a pasta `uploads/` num `.zip`, enviar pelo Gerenciador de Arquivos para `public_html/` e extrair lá (bem mais rápido que FTP arquivo por arquivo). Conferir que `public_html/uploads/.htaccess` existe.

## 6. Deploy pelo GitHub Actions

No GitHub: Settings → Secrets and variables → Actions.

| Secret | Valor |
| :---- | :---- |
| `FTP_SERVER` | host de FTP da HostGator (cPanel → Contas FTP) |
| `FTP_USERNAME` / `FTP_PASSWORD` | conta de FTP |
| `FTP_SERVER_DIR` | `/public_html/` com a conta principal do cPanel; `/` se a conta de FTP foi criada já apontando para `public_html` |

Depois, rodar o workflow "Deploy (FTP)" de novo (Actions → último deploy → Re-run). Na primeira vez ele envia todos os arquivos, então demora mais.

## 7. Serviços externos

- **Google Maps:** no Google Cloud Console, adicionar o domínio novo às restrições de site da chave (`https://dominio/*`). Sem isso, o mapa e a geocodificação param.
- **E-mail (esqueci a senha):** testar pelo site. Se o Gmail (`smtp.gmail.com:587`) não conectar, a HostGator está bloqueando SMTP externo: criar um e-mail no cPanel e usar o servidor de e-mail da própria HostGator nas constantes `SMTP_*`.

## 8. Testes

- Site: home, login, cadastro, perfil (foto e WhatsApp), painel do dono, painel do admin, mapa.
- Fotos das quadras aparecendo.
- API pelo navegador: `https://dominio/api/v1/sports` devolve JSON.
- **App pelo 4G do celular** (fora do Wi-Fi do notebook): login e lista de quadras com a base URL `https://dominio/api/v1`.

## 9. Depois que tudo funcionar

- Avisar o Rafael da base URL nova.
- Atualizar `docs/api.md`, `docs/setup.md`, `docs/CLAUDE.md` e a D13 em `docs/decisoes.md` (o app deixa de depender do notebook). ✔ Feito em 29/10 (D41).
- Cancelar o InfinityFree só depois de alguns dias sem problema.

## O que falta depois da migração

Tudo no cPanel da HostGator, sem mudar código. Marque conforme for fazendo.

### a) Forçar HTTPS
Hoje `http://kolae.online` abre sem ir para `https://`.
- cPanel → **Domínios** → na linha do `kolae.online`, ligar **"Forçar redirecionamento HTTPS"**.
- Conferir: abrir `http://kolae.online` e ver o endereço virar `https://`.
- Feito pelo painel, e não no `.htaccess`, para não afetar o XAMPP local (que não tem HTTPS).

### b) E-mail próprio para o "esqueci a senha" ✔ (02/11/2026)
Feito: o "esqueci a senha" envia por `nao-responda@kolae.online`. **O e-mail da HostGator neste plano é o Titan** (painel "powered by Titan"), não o e-mail do cPanel: o servidor é `smtp.titan.email`, e não `mail.kolae.online`.
1. Painel de e-mail → **Criar conta de e-mail**: `nao-responda@kolae.online` (envio) e `contato@kolae.online` (citado nos Termos e na Privacidade).
2. **Ligar o acesso de outros aplicativos** (sem isso o login SMTP é recusado): entrar no webmail do Titan com a conta `nao-responda` → **Configurações** (engrenagem) → **Enable Titan on Other Apps** → confirmar. Se aparecer "Configure 3rd party apps", já está ligado. Com verificação em duas etapas ligada na conta, é preciso senha de aplicativo.
3. Gerenciador de Arquivos → `public_html/config.php` → **Editar** e trocar só o bloco de e-mail:
   ```php
   define('SMTP_HOST', 'smtp.titan.email');
   define('SMTP_USER', 'nao-responda@kolae.online');
   define('SMTP_PASS', 'a senha da conta nao-responda');
   define('SMTP_SECURE', 'ssl');
   define('SMTP_PORT', 465);
   define('SMTP_FROM_EMAIL', 'nao-responda@kolae.online');
   define('SMTP_FROM_NAME', 'Kolae');
   ```
   `SMTP_FROM_EMAIL` precisa ser a mesma conta do `SMTP_USER`, senão o servidor recusa ou o e-mail cai no spam.
4. No painel do Titan, o card **Conectar domínio** precisa estar todo verde (MX, SPF, DKIM).
5. Testar: "Esqueci a senha" no site com um e-mail cadastrado (esperar 5 minutos entre dois pedidos, D52). Se precisar voltar: Gmail (`smtp.gmail.com`, `tls`, 587, senha de app).

### c) Entregabilidade (SPF e DKIM)
- cPanel → **Entregabilidade de e-mail** (Email Deliverability) → no `kolae.online`, se aparecer "Problemas", clicar em **Reparar** em SPF e DKIM.
- Sem isso, o Gmail e o Outlook tendem a mandar os e-mails do Kolae para o spam.

### d) Limites de upload do PHP
O site aceita fotos de até 10 MB (avatar e quadras), mas o PHP da hospedagem pode estar limitado a 2 MB, e aí o upload falha sem explicar.
- cPanel → **MultiPHP INI Editor** → modo básico → escolher `kolae.online`:
  - `upload_max_filesize` = `10M`
  - `post_max_size` = `40M` (o cadastro de quadra envia várias fotos de uma vez)
  - `memory_limit` = `256M`
  - `max_execution_time` = `60`
  - `display_errors` = **Off** (erro de PHP nunca aparece para o visitante)
- Testar: trocar a foto do perfil por uma foto de celular (3–5 MB).

### e) Google Maps
- Google Cloud Console → APIs e serviços → Credenciais → a chave do Kolae → **Restrições de aplicativo: sites** → adicionar `https://kolae.online/*` e `https://www.kolae.online/*`.
- Testar: painel do admin → Mapa, e cadastrar uma quadra com endereço (a geocodificação preenche latitude e longitude).

### f) Backup
- cPanel → **Backup** → baixar um backup do banco `joao5502_kolae` antes das mudanças grandes (patches novos, dados de demonstração da S6).

### g) App pelo 4G
- O Rafael troca a base URL para `https://kolae.online/api/v1` e testa **fora do Wi-Fi**: login, lista, detalhe e agenda.

### h) Cancelar o InfinityFree
- Depois de alguns dias sem problema na HostGator. Antes, conferir que nada aponta mais para `kolae.gamer.gd` (app, README, documentos).

### i) Patch 005 junto com o deploy da S5 ✔
- phpMyAdmin → banco `joao5502_kolae` → aba **SQL** → colar e executar `database/patches/005_login_attempts.sql`. Cria a tabela do limite de tentativas de login (D45).
- Pode ser antes ou logo depois do merge: sem a tabela o login funciona, só fica sem o limite.

### j) Patch 006 ANTES do merge da aba Fotos ✔
- phpMyAdmin → banco `joao5502_kolae` → aba **SQL** → colar e executar `database/patches/006_ordem_das_fotos.sql`. Cria a ordem das fotos (D46) mantendo a capa atual de cada quadra.
- **Precisa ser antes do merge:** o código novo ordena por essa coluna e quebra sem ela. O código atual ignora a coluna, então aplicar antes não afeta nada.

### k) Patch 007 ANTES do merge da confirmação de e-mail
- phpMyAdmin → **clicar no banco `joao5502_kolae` na lista da esquerda** → aba **SQL** desse banco → colar e executar `database/patches/007_confirmacao_de_email.sql`. Só garante a coluna `users.email_verified_at` (D58); o normal é responder "coluna email_verified_at já existe".
- Na aba SQL da tela inicial do phpMyAdmin (sem banco escolhido), o patch só avisa "Nenhum banco selecionado" e não muda nada.

### l) Miniaturas das fotos depois do deploy da v1.6.0
- Painel do admin → card **Fotos das quadras** → **Otimizar fotos**. Cria a miniatura (`thumb_*.jpg`, até 640 px) das fotos enviadas antes da D58; o card mostra quantas ainda estão "sem miniatura". Pode apertar mais de uma vez.
- Sem isso o site funciona igual, só que os cards usam a foto inteira.

### m) Patch 008 ANTES do merge das redes sociais (v1.7.0) ✔ (05/11/2026)
- phpMyAdmin → **clicar no banco `joao5502_kolae` na lista da esquerda** → aba **SQL** desse banco → colar e executar `database/patches/008_redes_sociais_da_quadra.sql`. Cria as quatro colunas das redes sociais em `venues` (D60), todas vazias.
- **Precisa ser antes do merge:** o código novo lê e grava essas colunas e quebra sem elas. O código atual ignora as colunas, então aplicar antes não afeta nada. Rodar de novo só avisa "coluna ... já existe".

### m) Patch 009 ANTES do merge do plano (Kolae Premium)
- phpMyAdmin → **clicar no banco `joao5502_kolae`** → aba **SQL** → colar e executar `database/patches/009_kolae_premium.sql`. Cria as colunas do plano em `users` (o Kolae Premium é do dono) e as tabelas `venue_events`, `venue_ads`, `match_requests` e `notifications` (D61–D64). Um patch só para as quatro partes do Premium.
- O fim mostra "Patch 009 concluído". Pode rodar de novo sem problema.

### n) Patch 010 ANTES do merge do chat (parte B, `feat/chat-banco`)
- phpMyAdmin → **clicar no banco `joao5502_kolae`** → aba **SQL** → colar e executar `database/patches/010_chat.sql`. Cria as tabelas `conversations` e `messages` (D66), vazias. Um patch só para todas as partes do chat.
- O fim mostra "Patch 010 concluído". Pode rodar de novo sem problema. Sem banco escolhido, mostra o aviso e para sem mudar nada.

## Checklist de cada release
1. Patches novos de `database/patches/` aplicados no phpMyAdmin **antes** do merge (com o banco selecionado), seguindo o que cada patch diz.
2. Merge do PR na `main`: o GitHub Actions roda os testes e só então publica por FTP.
3. Passos manuais da versão (no CHANGELOG, logo abaixo do título dela).
4. Conferir a produção: home, `/quadras`, login, painel do dono, uma página pública de quadra e a API (`/api/v1/venues`).
5. Tag `vX.Y.Z` no commit do merge e release no GitHub com o texto da versão no CHANGELOG.
6. `develop` avançada até a `main`.
