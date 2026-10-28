# Documentação do Kolae

Comece por aqui. Cada assunto tem **um** lugar; se a informação estiver em dois, o lugar listado abaixo é o que vale.

## Onde está cada coisa

| Documento | O que tem | Quem usa |
| :---- | :---- | :---- |
| [`decisoes.md`](decisoes.md) | Tudo o que foi decidido, com data e motivo (D01, D02...) | João e Rafael |
| [`api.md`](api.md) | Contrato da API v1: formato, erros, endpoints, o que vem em cada sprint | Principalmente o Rafael (app) |
| [`modelo-status.md`](modelo-status.md) | Status da quadra, horário de funcionamento e agenda (proposta para S3/S4) | João e Rafael |
| [`setup.md`](setup.md) | Montar o ambiente local (XAMPP) e conectar o app ao notebook | Quem for rodar o projeto |
| [`../database/kolae.dbml`](../database/kolae.dbml) | Desenho do banco como ele é hoje (abrir no dbdiagram.io) | João |
| [`../database/patches/`](../database/patches/) | Scripts que levam cada mudança de banco para a produção | João |
| [`../CHANGELOG.md`](../CHANGELOG.md) | O que mudou em cada versão | Os dois e a banca |
| [`CLAUDE.md`](CLAUDE.md) | Contexto resumido para o Claude Code (status, cronograma, convenções) | O Claude |

O app tem o próprio `CLAUDE.md` no repositório `Thesatiru/kolae-mobile`. Para não haver duas versões do contrato, o do app deve **apontar** para `api.md` e `decisoes.md` deste repositório em vez de copiar o conteúdo.

## Cronograma

| Sprint | Backend e site (João) | App (Rafael) | Pronto quando |
| :---- | :---- | :---- | :---- |
| S1 · 26/10–01/11 | Hotfixes de segurança, login da API, formato JSON ✔ (v1.4.0) | Base, navegação, tema, i18n, telas com mock ✔ | Hotfix em produção |
| S2 · 02/11–08/11 | Formato Laravel, cadastro, esqueci a senha, perfil, avatar, senha ✔ (adiantada) | Login e cadastro reais, perfil, configurações, primeiro APK | Login ponta a ponta no celular |
| S3 · 09/11–15/11 | `/venues` com filtros, status e `owner`; `/venues/{id}` | Mapa com status, filtros, busca | Quadra do site aparece no app |
| S4 · 16/11–22/11 | Horário de funcionamento e agenda do dono no site; `/schedule` | Detalhe da quadra, agenda, "Falar com o dono" | Status muda sozinho no app |
| S5 · 23/11–29/11 | Segurança (CSRF, XSS, limite de tentativas), "lembrar de mim", troca de e-mail | Telas de vitrine, estados de erro e vazio | Congelamento de funcionalidades em 29/11 |
| S6 · 30/11–05/12 | Só correções, dados de demonstração | Correções, APK final, ensaio | Entrega em 05/12 |

## Como uma mudança anda

1. **Combinar.** Se muda o que o outro usa (API, regra do produto), registrar em `decisoes.md`.
2. **Escrever o contrato antes do código.** Endpoint novo ou mudança de formato entra em `api.md`; o outro revisa.
3. **Banco:** editar `database/kolae.dbml` e criar `database/patches/NNN_*.sql`; testar no local.
4. **Código** numa branch (`feat/`, `fix/`, `docs/`, `chore/`), com testes no ambiente local.
5. **PR para a `main`**, revisado pelo outro. A `main` publica direto na produção.
6. **Depois do merge:** aplicar o patch na produção (phpMyAdmin), avisar o outro se a API mudou, registrar em `[Não lançado]` no `CHANGELOG.md`.
7. **Fim da sprint:** o `[Não lançado]` vira a versão da sprint (tag e release no GitHub).
