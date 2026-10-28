# Kolae — setup local

Como rodar o backend (site + `/api/v1`) no Windows com XAMPP e como o app mobile se conecta a ele.

> **Por que o app usa o backend local:** a hospedagem atual (InfinityFree) responde a clientes que não são navegador com um desafio JavaScript em vez do JSON, então o app não consegue usar a API da produção. Até a migração de hospedagem, o app aponta para o XAMPP de um notebook na mesma rede Wi-Fi.

## 1. Backend no XAMPP

### Requisitos

- XAMPP com PHP 8.2 e MariaDB/MySQL
- Composer
- Git

### Passo a passo

1. **Clonar** o repositório (ex: `C:\Users\<você>\Documents\GitHub\Kolae`).

2. **Ligar a pasta ao XAMPP** sem copiar arquivos (junction). No PowerShell:

   ```powershell
   cmd /c mklink /J C:\xampp\htdocs\kolae C:\Users\<você>\Documents\GitHub\Kolae
   ```

   O projeto precisa ficar em `/kolae`: o `index.php` e o `.htaccess` esperam esse caminho no ambiente local.

3. **Extensões do PHP**: no painel do XAMPP, Apache → Config → `PHP (php.ini)`, tirar o `;` do começo destas linhas e reiniciar o Apache:

   ```ini
   extension=gd
   extension=zip
   ```

   A `gd` é usada para redimensionar a foto de perfil (sem ela o upload de avatar falha) e a `zip` para o Composer extrair os pacotes. As outras que o projeto usa (`pdo_mysql`, `openssl`, `mbstring`, `fileinfo`, `curl`) já vêm ligadas no XAMPP. Para conferir: `C:\xampp\php\php.exe -m`.

4. **Dependências PHP**, na pasta do projeto:

   ```powershell
   composer install
   ```

   Sem o Composer instalado, dá para usar o PHP do próprio XAMPP: baixar o `composer.phar` de [getcomposer.org/download](https://getcomposer.org/download/) e rodar `C:\xampp\php\php.exe composer.phar install`.

5. **Banco**: no painel do XAMPP, ligar o MySQL. No phpMyAdmin (`http://localhost/phpmyadmin` ou `:8080`, conforme a porta do Apache):
   - criar o banco `kolae_db` com collation `utf8mb4_unicode_ci`;
   - importar `database/schema.sql` (só a estrutura, sem dados).

   Para ter dados de verdade (quadras, esportes), peça ao João um export da produção e importe no lugar do `schema.sql`. Com o banco vazio, crie sua conta pelo próprio site em `/register`.

6. **`config.php`**: copiar `config.example.php` para `config.php` (este arquivo não vai para o git) e preencher:

   | Constante | Valor local |
   | :---- | :---- |
   | `DB_HOST` | `localhost` |
   | `DB_PORT` | `3306` (o `Database.php` ainda não usa esta constante; o MySQL precisa estar na porta padrão) |
   | `DB_NAME` | `kolae_db` |
   | `DB_USER` / `DB_PASS` | `root` / vazio |
   | `ENCRYPTION_KEY` | 32 caracteres aleatórios |
   | `ENCRYPTION_IV` | 32 caracteres **hexadecimais** (ex: `php -r "echo bin2hex(random_bytes(16));"`) |
   | `JWT_SECRET` | string longa aleatória (ex: `php -r "echo bin2hex(random_bytes(32));"`) |
   | `GOOGLE_MAPS_API_KEY` | opcional; sem ela o mapa e a geocodificação não funcionam |
   | `SMTP_HOST`, `SMTP_USER`, `SMTP_PASS` | servidor e conta de e-mail do "esqueci a senha" (ex: `smtp.gmail.com`, o e-mail e uma senha de app) |
   | `SMTP_SECURE` / `SMTP_PORT` | `tls` / `587` (ou `ssl` / `465`) |
   | `SMTP_FROM_EMAIL` / `SMTP_FROM_NAME` | remetente dos e-mails (normalmente o mesmo de `SMTP_USER`) / `Kolae` |

   Para ler os CNPJs de um export da produção, `ENCRYPTION_KEY` e `ENCRYPTION_IV` precisam ser os mesmos da produção.

   **E-mail (SMTP):** as constantes `SMTP_*` precisam existir no `config.php`, mesmo vazias; se faltar alguma, o "esqueci a senha" para com erro fatal. Com `SMTP_HOST` vazio o site funciona, mas o e-mail de recuperação não é enviado (o erro vai para o log do PHP). Para testar o reset de senha de verdade, use uma conta de e-mail com SMTP liberado. No Gmail: ativar a verificação em duas etapas, gerar uma "senha de app" em [myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords) e usá-la em `SMTP_PASS` (a senha normal da conta não funciona). Nunca commitar essas credenciais: o `config.php` fica fora do git.

7. **Abrir** `http://localhost/kolae` (Apache na porta 80) ou `http://localhost:8080/kolae` (porta 8080). Qualquer porta funciona.

### Problemas comuns

- **MySQL "shutdown unexpectedly" logo depois de ligar.** Rodar `C:\xampp\mysql\bin\mysqld.exe --console` para ver o erro real. Se aparecer `Failed to initialize multi master structures`, mover os arquivos `multi-master.info`, `master-*.info`, `relay-log*.info` e `mysql-relay-bin*` de `C:\xampp\mysql\data` para outra pasta e ligar de novo. Costuma acontecer quando o PC é desligado com o MySQL ligado: pare o MySQL no painel antes de desligar.
- **Todas as páginas dão 404.** O projeto não está em `/kolae` (rever o passo 2) ou o `mod_rewrite` está desligado no Apache.
- **Erro fatal sobre `vendor/...`.** Rodar `composer install` de novo.
- **`Undefined constant "SMTP_HOST"` (ou outra `SMTP_*`) no "esqueci a senha".** O `config.php` foi criado antes de o bloco de e-mail existir no `config.example.php`: copiar as constantes `SMTP_*` do exemplo (passo 6).
- **Upload da foto de perfil sempre falha.** A extensão `gd` está desligada (passo 3).
- **Composer reclama de `zip` ou demora baixando "from source".** A extensão `zip` está desligada (passo 3).

## 2. App mobile apontando para o notebook

1. O notebook com o XAMPP e o celular (ou o emulador) precisam estar **na mesma rede Wi-Fi**.

2. **Descobrir o IP do notebook**: no PowerShell, `ipconfig`, e procurar o "Endereço IPv4" do **Adaptador de Rede sem Fio Wi-Fi** (ex: `192.168.0.172`). Ignore adaptadores virtuais (vEthernet, WSL, VirtualBox).

3. **Base URL da API no app**:

   ```
   http://<IP do notebook>:<porta do Apache>/kolae/api/v1
   ```

   Ex: `http://192.168.0.172:8080/kolae/api/v1`. Deixe isso numa variável de ambiente ou num arquivo de config do app: o IP muda quando a rede muda.

4. **Testar antes no navegador do celular**: abrir `http://<IP>:8080/kolae/api/v1/sports`. Tem que aparecer o JSON dos esportes. Se não abrir:
   - **Firewall do Windows**: liberar o "Apache HTTP Server" para o perfil da rede atual (Pública ou Privada).
   - **Rede da faculdade ou pública**: muitas bloqueiam a comunicação entre aparelhos (isolamento de clientes). Use o **roteador do celular** (hotspot) com o notebook conectado nele.

5. **HTTP sem HTTPS**: o backend local é `http://`. O Expo Go aceita, mas em um build próprio (APK/IPA) o Android e o iOS bloqueiam HTTP por padrão; aí é preciso liberar o tráfego HTTP na configuração do build (ex: `usesCleartextTraffic` no Android). Para a apresentação, o Expo Go resolve.

O contrato da API (formato das respostas, erros, endpoints) está em `docs/api.md`.
