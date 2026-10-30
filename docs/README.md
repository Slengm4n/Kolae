# Documentação do Kolae

Comece por aqui. Cada assunto tem **um** lugar; se a informação estiver em dois, o lugar listado abaixo é o que vale.

## Onde está cada coisa

| Documento | O que tem | Quem usa |
| :---- | :---- | :---- |
| [`decisoes.md`](decisoes.md) | Tudo o que foi decidido, com data e motivo (D01, D02...) | João e Rafael |
| [`api.md`](api.md) | Contrato da API v1: formato, erros, endpoints, o que vem em cada sprint | Principalmente o Rafael (app) |
| [`modelo-status.md`](modelo-status.md) | Status da quadra, horário de funcionamento e agenda (aprovado em 28/10; base da S3/S4) | João e Rafael |
| [`setup.md`](setup.md) | Montar o ambiente local (XAMPP) e testar o app contra o notebook antes do deploy | Quem for rodar o projeto |
| [`hospedagem.md`](hospedagem.md) | Produção na HostGator (`https://kolae.online`, D41): como foi montada e o que falta configurar no cPanel | João |
| [`../database/kolae.dbml`](../database/kolae.dbml) | Desenho do banco como ele é hoje (abrir no dbdiagram.io) | João |
| [`../database/patches/`](../database/patches/) | Scripts que levam cada mudança de banco para a produção | João |
| [`../CHANGELOG.md`](../CHANGELOG.md) | O que mudou em cada versão | Os dois e a banca |
| [`CLAUDE.md`](CLAUDE.md) | Contexto resumido para o Claude Code (status, cronograma, convenções) | O Claude |

O app tem o próprio `CLAUDE.md` no repositório `Thesatiru/kolae-mobile`. Para não haver duas versões do contrato, o do app deve **apontar** para `api.md` e `decisoes.md` deste repositório em vez de copiar o conteúdo.

## Cronograma

| Sprint | Backend e site (João) | App (Rafael) | Pronto quando |
| :---- | :---- | :---- | :---- |
> **Prazo novo (D28):** entrega em **28/11/2026** e congelamento de funcionalidades em **21/11**. A S2 do backend terminou adiantada, então a S3 começa já em 29/10.

| Sprint | Backend e site (João) | App (Rafael) | Pronto quando |
| :---- | :---- | :---- | :---- |
| S1 · 26/10–28/10 | Hotfixes de segurança, login da API, formato JSON ✔ (v1.4.0) | Base, navegação, tema, i18n, telas com mock ✔ | Hotfix em produção |
| S2 · 28/10 | Formato Laravel, cadastro, esqueci a senha, perfil, avatar, senha ✔ (v1.5.0) | Continua na S3 | API de contas em produção |
| S3 · 29/10–08/11 | Contrato da S3, patch 004 (horário, WhatsApp, partidas), `/venues` com filtros, status e `owner`; `/venues/{id}`; `/schedule` | Login e cadastro reais, perfil, configurações, primeiro APK; mapa com status (mock até a API chegar) | Quadra do site aparece no app com status |
| S4 · 09/11–15/11 | Telas do dono no site: horário de funcionamento, agenda de partidas, WhatsApp no perfil | Mapa e detalhe da quadra com a API real, agenda do dia, "Falar com o dono" | Status muda sozinho no app |
| S5 · 16/11–21/11 | Segurança essencial (CSRF, XSS no `admin/map.php`, limite de tentativas no login), "lembrar de mim" | Telas de vitrine, estados de erro e vazio | **Congelamento em 21/11** |
| S6 · 22/11–28/11 | Só correções, dados de demonstração, ensaio | Correções, APK final, ensaio | **Entrega em 28/11** |

Fora do escopo da entrega: troca de e-mail com código (vitrine no app, D28), tradução do painel de admin (D19).

## Como uma mudança anda

1. **Combinar.** Se muda o que o outro usa (API, regra do produto), registrar em `decisoes.md`.
2. **Escrever o contrato antes do código.** Endpoint novo ou mudança de formato entra em `api.md`; o outro revisa.
3. **Banco:** editar `database/kolae.dbml` e criar `database/patches/NNN_*.sql`; testar no local.
4. **Código** numa branch (`feat/`, `fix/`, `docs/`, `chore/`), com testes no ambiente local.
5. **PR para a `main`**, revisado pelo outro. A `main` publica direto na produção.
6. **Depois do merge:** aplicar o patch na produção (phpMyAdmin), avisar o outro se a API mudou, registrar em `[Não lançado]` no `CHANGELOG.md`.
7. **Fim da sprint:** o `[Não lançado]` vira a versão da sprint (tag e release no GitHub).
