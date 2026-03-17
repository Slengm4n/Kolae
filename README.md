# Kolae 🏅

![Versão](https://img.shields.io/badge/version-v2.0-blue)
![PHP](https://img.shields.io/badge/php-%3E%3D%208.1-777bb4)
![Licença](https://img.shields.io/badge/license-MIT-green)

O **Kolae** é uma plataforma web desenvolvida para conectar atletas e entusiastas do desporto. O objetivo é facilitar a procura por parceiros de treino, equipas, locais para praticar desporto (quadras, campos) e eventos desportivos na sua região.

![Screenshot do Dashboard](https://i.postimg.cc/GmV2qBBF/print-dashboard.png)

---

## ✨ Funcionalidades Principais (v2.0)

### 👤 Para Utilizadores
* **Autenticação Completa:** Registo, Login e Recuperação de Palavra-passe.
* **Perfil do Utilizador:** Gestão de informações básicas, foto de perfil e e-mail.
* **Segurança:** Alteração de palavra-passe e gestão de sessões.
* **Painel do Utilizador:** Dashboard central para acesso rápido a todas as funções.

### 🏟️ Para Gestores de Locais
* **Validação de CNPJ:** Obrigatória para utilizadores que desejam gerir recintos desportivos.
* **Gestão de Quadras:** Cadastro, edição e listagem de locais com suporte a geolocalização.
* **Galeria de Imagens:** Upload e gestão de fotos para cada local.

### 🛠️ Painel Administrativo
* **Gestão de Utilizadores:** Criar, editar e eliminar utilizadores do sistema.
* **Controlo de Modalidades:** Gestão das modalidades desportivas disponíveis na plataforma.
* **Mapa de Recintos:** Visualização geográfica de todas as quadras cadastradas.

---

## 🛠️ Tecnologias Utilizadas

O projeto utiliza uma arquitetura **MVC (Model-View-Controller) customizada**, focada em performance e organização.

* **Backend:** PHP 8.1+.
* **Frontend:** HTML5, Tailwind CSS, ViteJS e Vanilla JavaScript.
* **Base de Dados:** MySQL / MariaDB.
* **Gestão de Dependências:** Composer (PHP) e NPM (Assets).
* **Migrações:** Phinx para controlo de versão da base de dados.
* **CI/CD:** Deploy automatizado para InfinityFree via GitHub Actions.

---

## 🚀 Como Rodar Localmente

### Pré-requisitos
* Servidor local (XAMPP, WAMP, Laragon) com PHP 8.1+.
* Composer e Node.js instalados.
* MySQL/MariaDB configurado.

### Instalação

1.  **Clone o repositório:**
    ```bash
    git clone [https://github.com/Slengm4n/Kolae.git](https://github.com/Slengm4n/Kolae.git)
    cd Kolae
    ```

2.  **Instale as dependências:**
    ```bash
    composer install
    npm install
    ```

3.  **Configuração da Base de Dados:**
    * Crie uma base de dados (ex: `kolae_local`).
    * Copie o ficheiro de exemplo e configure os seus dados:
      ```bash
      cp config.example.php config.php
      ```
    * Execute as migrações para criar as tabelas:
      ```bash
      vendor/bin/phinx migrate
      ```

4.  **Compilação de Assets:**
    ```bash
    npm run dev
    ```

5.  **Aceda ao projeto:**
    Abra o seu navegador em `http://localhost/Kolae` (ou o caminho configurado no seu Apache).

---

## ☁️ Deploy

O deploy é feito automaticamente para o ambiente de produção sempre que é realizado um `push` na branch `main`. O processo envolve:
1. Verificação de sintaxe PHP.
2. Sincronização de ficheiros via FTP com o servidor remoto.

---

## 📄 Licença

Este projeto está licenciado sob a licença **MIT**.
