# Kolae 🏅

![Versão](https://img.shields.io/badge/version-v1.3-blue)
![PHP](https://img.shields.io/badge/php-%3E%3D%208.1-777bb4)
![Licença](https://img.shields.io/badge/license-MIT-green)

O **Kolae** é uma plataforma web desenvolvida para conectar atletas e entusiastas do esporte. O objetivo é facilitar a busca por parceiros de treino, equipes, locais para praticar esporte (quadras, campos) e eventos esportivos na sua região.

🔗 **[Acesse o projeto ao vivo](https://kolae.gamer.gd)**

![Screenshot do Dashboard](https://i.postimg.cc/GmV2qBBF/print-dashboard.png)

---

## 🆕 Novidades da v1.3

* **Dark Mode / Light Mode:** alternância de tema completa em toda a plataforma.
* **Internacionalização (i18n):** suporte a múltiplos idiomas via sistema de variáveis de tradução.
* **Otimizações de performance:** melhorias no carregamento de assets e nas consultas ao banco de dados.

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

* **Backend:** PHP 8.1+
* **Frontend:** HTML5, Tailwind CSS, ViteJS e Vanilla JavaScript
* **Base de Dados:** MySQL / MariaDB
* **Gestão de Dependências:** Composer (PHP) e NPM (Assets)
* **Migrações:** Phinx para controle de versão da base de dados
* **Internacionalização:** Sistema de variáveis de tradução para múltiplos idiomas
* **CI/CD:** Deploy automatizado para InfinityFree via GitHub Actions

---

## 🚀 Como Rodar Localmente

### Pré-requisitos
* Servidor local (XAMPP, WAMP, Laragon) com PHP 8.1+
* Composer e Node.js instalados
* MySQL/MariaDB configurado

### Instalação

1. **Clone o repositório:**
```bash
    git clone https://github.com/Slengm4n/Kolae.git
    cd Kolae
```

2. **Instale as dependências:**
```bash
    composer install
    npm install
```

3. **Configuração da Base de Dados:**
    * Crie uma base de dados (ex: `kolae_local`).
    * Copie o arquivo de exemplo e configure seus dados:
```bash
      cp config.example.php config.php
```
    * Execute as migrações para criar as tabelas:
```bash
      vendor/bin/phinx migrate
```

4. **Compilação de Assets:**
```bash
    npm run dev
```

5. **Acesse o projeto:**
    Abra seu navegador em `http://localhost/Kolae` (ou o caminho configurado no seu Apache).

---

## ☁️ Deploy

O deploy é feito automaticamente para o ambiente de produção sempre que é realizado um `push` na branch `main`. O processo envolve:

1. Verificação de sintaxe PHP.
2. Sincronização de arquivos via FTP com o servidor remoto.

---

## 👥 Contribuição

Projeto desenvolvido em colaboração, com fluxo de trabalho baseado em Pull Requests e branches (`main`, `hot_fix`, entre outras).

---

## 📄 Licença

Este projeto está licenciado sob a licença **MIT**.
