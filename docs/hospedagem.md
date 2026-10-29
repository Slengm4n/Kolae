# Kolae — migração para a HostGator

Passo a passo para trocar a produção do InfinityFree para a HostGator (Plano Start, hospedagem compartilhada com cPanel). Motivo: o InfinityFree responde ao app com um desafio JavaScript em vez do JSON (D13).

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
- Atualizar `docs/api.md`, `docs/setup.md`, `docs/CLAUDE.md` e a D13 em `docs/decisoes.md` (o app deixa de depender do notebook).
- Cancelar o InfinityFree só depois de alguns dias sem problema.
