# Kolae — migração para a HostGator

Passo a passo para trocar a produção do InfinityFree para a HostGator (Plano Start, hospedagem compartilhada com cPanel). Motivo: o InfinityFree responde ao app com um desafio JavaScript em vez do JSON (D13).

> **Estado (29/10/2026): migração feita, produção em `https://kolae.online` (D41).** Passos 1 a 6 concluídos e conferidos: DNS e SSL, PHP 8.2, banco `joao5502_kolae` importado (7 quadras, 8 usuários), fotos, `config.php` (mesmas chaves, banco com senha nova, SMTP corrigido para `smtp.gmail.com` / `tls` / 587) e deploy por FTP com a conta `deploy@kolae.online` (`FTP_SERVER_DIR = /`). Site e API testados: home e login sem erro de JavaScript, `GET /venues`, detalhe, agenda, login com erro 401 e rota protegida, todos em JSON direto. **O que falta está em [O que falta](#o-que-falta-depois-da-migração), no fim.**

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

### b) E-mail próprio para o "esqueci a senha"
O plano inclui contas de e-mail. Um remetente `@kolae.online` chega mais na caixa de entrada que um Gmail (o domínio do remetente bate com o do site) e não depende da senha de app do Google.
1. cPanel → **Contas de e-mail** → **Criar**: `nao-responda@kolae.online`, senha gerada pelo cPanel.
2. Na lista, ao lado da conta: **Conectar dispositivos** (ou "Configurar cliente de e-mail"). Anotar o **servidor de saída (SMTP)** e a porta **SSL/TLS** (normalmente `mail.kolae.online`, porta **465**).
3. Gerenciador de Arquivos → `public_html/config.php` → **Editar** e trocar só o bloco de e-mail:
   ```php
   define('SMTP_HOST', 'mail.kolae.online');      // o servidor de saída anotado
   define('SMTP_USER', 'nao-responda@kolae.online');
   define('SMTP_PASS', 'a senha da conta de e-mail');
   define('SMTP_SECURE', 'ssl');                  // porta 465 = ssl; porta 587 = tls
   define('SMTP_PORT', 465);
   define('SMTP_FROM_EMAIL', 'nao-responda@kolae.online');
   define('SMTP_FROM_NAME', 'Kolae');
   ```
   `SMTP_FROM_EMAIL` precisa ser a mesma conta do `SMTP_USER`, senão o servidor recusa ou o e-mail cai no spam.
4. Testar: no site, "Esqueci a senha" com um e-mail cadastrado; ver se chega (olhar o spam na primeira vez).
5. Se der erro, voltar ao Gmail (`smtp.gmail.com`, `tls`, 587, senha de app), que já está configurado.

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
