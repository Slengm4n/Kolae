# Documentação do Kolae

Comece por aqui. Cada assunto tem **um** lugar; se a informação estiver em dois, o lugar listado abaixo é o que vale.

## Onde está cada coisa

| Documento | O que tem | Quem usa |
| :---- | :---- | :---- |
| [`decisoes.md`](decisoes.md) | Tudo o que foi decidido, com data e motivo (D01, D02...) | João e Rafael |
| [`api.md`](api.md) | Contrato da API v1: formato, erros, endpoints, o que vem em cada sprint | Principalmente o Rafael (app) |
| [`modelo-status.md`](modelo-status.md) | Status da quadra, horário de funcionamento e agenda (aprovado em 28/10; base da S3/S4) | João e Rafael |
| [`setup.md`](setup.md) | Montar o ambiente local (XAMPP), rodar os testes e testar o app contra o notebook antes do deploy | Quem for rodar o projeto |
| [`../tests/e2e/README.md`](../tests/e2e/README.md) | Testes de clique: como rodar, dados de teste e o que cada um cobre | Quem mexer no site |
| [`sugestoes.md`](sugestoes.md) | Ideias de funcionalidades e melhorias, com o porquê e quando fazer (ainda não são decisões) | João e Rafael |
| [`proposta-redes-sociais.md`](proposta-redes-sociais.md) | Proposta das redes sociais da quadra (aprovada: virou a D60) | João e Rafael |
| [`proposta-login-google.md`](proposta-login-google.md) | Proposta de "Entrar com Google": fluxo, contrato do `POST /auth/google`, configuração no Google Cloud, datas, plano B e registro do andamento (vira a D61 se aprovada) | João e Rafael |
| [`proposta-chat.md`](proposta-chat.md) | Proposta do chat com o responsável pela quadra: banco, contrato, painel, app, push opcional, datas, plano B e registro do andamento (vira a D62 se aprovada) | João e Rafael |
| [`proposta-premium.md`](proposta-premium.md) | Proposta do Kolae Premium: plano da quadra, estatísticas, anúncios, solicitações de horário e notificações; banco, contrato, telas, app e cronograma até 21/11 | João e Rafael |
| [`proposta-partidas.md`](proposta-partidas.md) | Proposta das partidas com jogadores para o Kolae 2.0: fases, banco, endpoints, telas e o que decidir | João e Rafael |
| [`hospedagem.md`](hospedagem.md) | Produção na HostGator (`https://kolae.online`, D41): como foi montada, e-mail pelo Titan, patches e o que falta no cPanel | João |
| [`../database/kolae.dbml`](../database/kolae.dbml) | Desenho do banco como ele é hoje (abrir no dbdiagram.io) | João |
| [`../database/patches/`](../database/patches/) | Scripts que levam cada mudança de banco para a produção | João |
| [`../CHANGELOG.md`](../CHANGELOG.md) | O que mudou em cada versão | Os dois e a banca |
| [`CLAUDE.md`](CLAUDE.md) | Contexto resumido para o Claude Code (status, cronograma, convenções) | O Claude |

O app tem o próprio `CLAUDE.md` no repositório `Thesatiru/kolae-mobile`. Para não haver duas versões do contrato, o do app deve **apontar** para `api.md` e `decisoes.md` deste repositório em vez de copiar o conteúdo.

## Cronograma

> **Prazo (D28):** entrega em **28/11/2026** e congelamento de funcionalidades em **21/11**. O backend andou adiantado: S3 a S6 ficaram prontas até 02/11 e saíram juntas na **v1.6.0**, a versão de entrega.

| Sprint | Backend e site (João) | App (Rafael) | Situação |
| :---- | :---- | :---- | :---- |
| S1 · 26/10–28/10 | Hotfixes de segurança, login da API, formato JSON | Base, navegação, tema, i18n, telas com mock | ✔ v1.4.0 |
| S2 · 28/10 | Formato Laravel, cadastro, esqueci a senha, perfil, avatar, senha | Continua na S3 | ✔ v1.5.0 |
| S3 · 29/10–08/11 | Patch 004 (horário, WhatsApp, partidas), `/venues` com filtros e status, `/venues/{id}`, `/schedule` | Login e cadastro reais, perfil, configurações, primeiro APK, mapa com status | ✔ backend (v1.6.0) |
| S4 · 09/11–15/11 | Telas do dono: horário, agenda, dados, cadastro em passos, painel com status ao vivo | Mapa e detalhe com a API real, agenda do dia, "Falar com o dono" | ✔ backend (v1.6.0) |
| S5 · 16/11–21/11 | CSRF, XSS do mapa do admin, limite de login, "lembrar de mim" | Telas de vitrine, estados de erro e vazio | ✔ backend (v1.6.0) · **congelamento em 21/11** |
| S6 · 22/11–28/11 | Testes antes do deploy, polimento, dados de demonstração, ensaio | Correções, APK final, ensaio | Testes e polimento ✔ (v1.6.0); falta demonstração e ensaio · **entrega em 28/11** |

Além do planejado, entraram na v1.6.0: aba Fotos e Visão geral, página pública da quadra, página Quadras com mapa, QR Code, guia passo a passo, Termos e Privacidade, produção na HostGator com e-mail pelo Titan, telas de entrada redesenhadas, avisos no visual do Kolae, confirmação de e-mail e miniaturas das fotos (D42–D58).

Fora do escopo da entrega: troca de e-mail com código (vitrine no app, D28), tradução do painel de admin (D19). Ideias para depois: [`sugestoes.md`](sugestoes.md).

## Como uma mudança anda

1. **Combinar.** Se muda o que o outro usa (API, regra do produto), registrar em `decisoes.md`.
2. **Escrever o contrato antes do código.** Endpoint novo ou mudança de formato entra em `api.md`; o outro revisa.
3. **Banco:** editar `database/kolae.dbml` e criar `database/patches/NNN_*.sql`; testar no local.
4. **Código** numa branch (`feat/`, `fix/`, `docs/`, `ci/`), com testes no ambiente local: os de `tests/` e os de clique da área mexida (`npm run test:e2e`, ver [`setup.md`](setup.md#testes)).
5. **PR para a `main`**, revisado pelo outro. O GitHub Actions roda os testes no PR e de novo antes de publicar; a `main` publica direto na produção.
6. **Depois do merge:** aplicar o patch na produção (phpMyAdmin), avisar o outro se a API mudou, registrar em `[Não lançado]` no `CHANGELOG.md`.
7. **Fim da sprint:** o `[Não lançado]` vira a versão da sprint (tag e release no GitHub).
