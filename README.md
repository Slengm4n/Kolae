# Kolae 🏅

[![Versão](https://img.shields.io/github/v/release/Slengm4n/Kolae?label=vers%C3%A3o)](https://github.com/Slengm4n/Kolae/releases/latest)
![PHP](https://img.shields.io/badge/php-%3E%3D%208.2-777bb4)
![Licença](https://img.shields.io/badge/license-MIT-green)

O **Kolae** é uma plataforma web desenvolvida para conectar atletas e entusiastas do esporte. O objetivo é facilitar a busca por parceiros de treino, equipes, locais para praticar esporte (quadras, campos) e eventos esportivos na sua região.

🔗 **[Acesse o projeto ao vivo](https://kolae.online)**

![Screenshot do Dashboard](https://i.postimg.cc/GmV2qBBF/print-dashboard.png)

---

## 🆕 Novidades da v1.4

* **Segurança:** upload de imagens validado pelo conteúdo real do arquivo e correção de acesso indevido à edição de quadras de outros usuários.
* **API para o app mobile:** login com token JWT e respostas JSON padronizadas ([contrato da API](docs/api.md)).
* **Edição de quadras corrigida**, com fotos atuais, tipo de piso padronizado e coordenadas preservadas no mapa.
* **Banco de dados documentado:** diagrama em [`database/kolae.dbml`](database/kolae.dbml) e estrutura pronta em [`database/schema.sql`](database/schema.sql).

Histórico completo no [CHANGELOG](CHANGELOG.md).

---

## ✨ Funcionalidades Principais

### 👤 Para Usuários
* **Autenticação Completa:** Cadastro, Login e Recuperação de Senha.
* **Perfil do Usuário:** Gestão de informações básicas, foto de perfil e e-mail.
* **Segurança:** Alteração de senha e gestão de sessões.
* **Painel do Usuário:** Dashboard central para acesso rápido a todas as funções.
* **Personalização:** Alternância entre Dark Mode e Light Mode, seleção de idioma.

### 🏟️ Para Gestores de Locais
* **Validação de CNPJ:** Obrigatória para usuários que desejam gerenciar recintos esportivos.
* **Gestão de Quadras:** Cadastro, edição e listagem de locais com suporte a geolocalização.
* **Galeria de Imagens:** Upload e gestão de fotos para cada local.

### 🛠️ Painel Administrativo
* **Gestão de Usuários:** Criar, editar e excluir usuários do sistema.
* **Controle de Modalidades:** Gestão das modalidades esportivas disponíveis na plataforma.
* **Mapa de Recintos:** Visualização geográfica de todas as quadras cadastradas.

---

## 🛠️ Tecnologias Utilizadas

O projeto utiliza uma arquitetura **MVC (Model-View-Controller) customizada**, focada em performance e organização.

* **Backend:** PHP 8.2+
* **API:** REST em `/api/v1`, autenticação JWT, consumida pelo app mobile (React Native + Expo)
* **Frontend:** HTML5, Tailwind CSS, ViteJS e Vanilla JavaScript
* **Base de Dados:** MySQL / MariaDB, modelada no [dbdiagram](https://dbdiagram.io) (`database/kolae.dbml`); mudanças aplicadas por scripts em `database/patches/`
* **Gestão de Dependências:** Composer (PHP) e NPM (Assets)
* **Internacionalização:** Sistema de variáveis de tradução para múltiplos idiomas
* **CI/CD:** Deploy automatizado para a HostGator (FTP) via GitHub Actions

---

## 🚀 Como Rodar Localmente

Toda a documentação do projeto (decisões, contrato da API, modelo de dados, cronograma) está indexada em **[docs/README.md](docs/README.md)**.

O passo a passo completo (XAMPP, banco, `config.php`, problemas comuns e como conectar o app mobile) está em **[docs/setup.md](docs/setup.md)**. Resumo:

### Pré-requisitos
* XAMPP (ou similar) com PHP 8.2+ e MySQL/MariaDB
* Composer
* Node.js, só se for alterar CSS/JS (os assets compilados já estão no repositório)

### Instalação

1. **Clone o repositório** e deixe-o acessível em `/kolae` no Apache (no XAMPP, `htdocs/kolae`):
```bash
git clone https://github.com/Slengm4n/Kolae.git
```

2. **Instale as dependências PHP:**
```bash
composer install
```

3. **Banco de dados:** crie o banco `kolae_db` (`utf8mb4_unicode_ci`) e importe [`database/schema.sql`](database/schema.sql).

4. **Configuração:** copie `config.example.php` para `config.php` e preencha banco, chaves de criptografia e `JWT_SECRET`.

5. **Assets (opcional):**
```bash
npm install
npm run build   # ou npm run watch durante o desenvolvimento
```

6. **Acesse** `http://localhost/kolae` (ou `http://localhost:8080/kolae`, se o Apache estiver na 8080).

---

## ☁️ Deploy

O deploy é feito automaticamente para o ambiente de produção sempre que é realizado um `push` na branch `main`. O processo envolve:

1. Verificação de sintaxe PHP.
2. Instalação das dependências (`composer install --no-dev`).
3. Sincronização de arquivos via FTP com o servidor remoto.

Mudanças no banco não entram no deploy: os scripts de `database/patches/` são aplicados à mão no phpMyAdmin da produção.

---

## 👥 Contribuição

Projeto desenvolvido em colaboração, com fluxo baseado em Pull Requests:

* Uma branch por tarefa (`feat/...`, `fix/...`, `docs/...`, `chore/...`), com PR para a `main`. A `main` vai direto para produção.
* A `develop` é um espelho da `main`, atualizada após cada merge.
* Versionamento [SemVer](https://semver.org/lang/pt-BR/) com tags `vX.Y.Z`: uma versão minor por sprint, patch para correções urgentes. Mudanças registradas no [CHANGELOG](CHANGELOG.md).

---

## 📄 Licença

Este projeto está licenciado sob a licença **MIT**.
