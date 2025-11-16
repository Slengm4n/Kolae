# Kolae

[![Versão](https://img.shields.io/badge/version-v1.1.0-blue)](https://github.com/Slengm4n/colae/releases/tag/v1.1.0)

## 🎯 Sobre o Projeto

Kolae é uma plataforma web desenvolvida para conectar atletas e entusiastas do esporte. O objetivo é facilitar a busca por parceiros de treino, equipes, locais para praticar esportes (quadras, campos) e eventos esportivos na sua região.

Esta plataforma visa criar uma comunidade engajada onde os usuários podem encontrar facilmente o que precisam para praticar seus esportes favoritos.

![Screenshot do Dashboard do Kolae](https://i.postimg.cc/GmV2qBBF/print-dashboard.png)

**Status Atual:** Versão `v1.0.0` lançada! Funcionalidades básicas de usuário e gerenciamento de locais implementadas.

## ✨ Funcionalidades Principais (v1.1.0)

- **Autenticação:** Cadastro e Login de usuários.
- **Perfil de Usuário:** Visualização e edição de informações básicas (nome, foto de perfil).
- **Validação de CPF:** Necessária para funcionalidades de gerenciamento.
- **Dashboard do Usuário:** Painel central para acesso às funcionalidades.
- **Gerenciamento de Locais (Quadras):**
  - Cadastro de novas quadras/locais esportivos (com endereço e detalhes).
  - Upload de imagens para os locais.
  - Listagem e Edição dos locais cadastrados pelo usuário.
- **Painel Administrativo Básico:**
  - Gerenciamento de usuários.
  - Criação de novos usuários.
  - Edição e Exclusão de usuários.
  - Criação e edição de modalidades esportivas.
  - Mapa com localização e descriação de quadras cadastradas.
- **Deploy Automatizado:** Integração contínua com GitHub Actions para deploy no InfinityFree.

## 🛠️ Tecnologias Utilizadas

- **Backend:** PHP (sem framework específico, estrutura MVC customizada)
- **Frontend:** HTML, Tailwind CSS, JavaScript (para interações como dropdowns, preview de imagem, etc.)
- **Banco de Dados:** MySQL / MariaDB
- **Gerenciador de Dependências:** Composer
- **Hospedagem:** InfinityFree
- **CI/CD:** GitHub Actions (Deploy via FTP)

## 🚀 Como Rodar Localmente (Desenvolvimento)

1.  **Clone o repositório:**

```bash
    git clone [https://github.com/Slengm4n/colae.git](https://github.com/Slengm4n/colae.git)

    cd colae
```

2.  **Instale as dependências do Composer:**

```bash
    composer install
```

3.  **Instale as dependências do Composer:**

- Certifique-se de ter um ambiente local (XAMPP, WAMP, etc.) com Apache, MySQL/MariaDB e PHP (versão 8.1+).

  - **Crie um banco de dados vazio** no seu MySQL/MariaDB (ex: via phpMyAdmin) com o nome que desejar (ex: `kolae_local`).
  - **Importe a estrutura do banco:** Use o phpMyAdmin (selecione o banco criado > aba Importar > escolha o arquivo `database/kolae.sql`) OU execute o seguinte comando no terminal (substitua `usuario`, `senha`, `kolae_local` pelos seus dados):

    ```bash
    mysql -u usuario -p -h localhost kolae_local < database/kolae.sql
    ```

    _(Será pedida a senha do MySQL)_

  - Copie `config.example.php` para `config.php` e preencha com as credenciais do **seu banco de dados local** (nome do banco `colae_local`, usuário, senha).
  - Configure `BASE_URL` e `BASE_DIR_URL` no `index.php` para seu ambiente local (ex: `http://localhost/colae` e `/colae`).

4.  **Acesse o projeto:** Abra seu navegador e acesse `http://localhost/colae` (ou o caminho configurado).

## ☁️ Deploy

O deploy para o ambiente de produção (InfinityFree) é feito automaticamente via **GitHub Actions** sempre que um `push` é realizado na branch `main`. O workflow copia os arquivos via FTP.

## 📄 Licença

[MIT](https://choosealicense.com/licenses/mit/)
