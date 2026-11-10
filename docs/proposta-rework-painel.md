# Proposta: rework do painel do dono, do admin e do cadastro de quadra

**Situação:** **aprovada em 10/11/2026: é a D67.** Pedida pelo João antes do login com Google (D65). **Só site:** nada muda na API nem no app do Rafael.
**Esforço:** M (4 a 5 dias, só o João). **Data de corte:** 15/11, para sobrar tempo até o
congelamento (21/11). O Google (D65) vem depois.

## Por quê

O Kolae ganhou muita coisa desde 05/11 (Premium, anúncios, chat, pedidos de horário), e as três
telas principais não acompanharam:

| Tela | Hoje | Problema |
| :---- | :---- | :---- |
| Painel do dono (`/dashboard`) | "Ações rápidas" com dois atalhos ("Novo local" e "Minha conta") e os cartões das quadras | O dono não vê o que importa no dia: pedidos esperando, mensagens novas, partidas de hoje, situação do Premium |
| Painel do admin (`/admin`) | Menu lateral próprio, título "Dashboard" em inglês, números em cartões coloridos (verde, roxo, laranja), e Premium, Anúncios, Fotos e Usuários recentes empilhados numa página | Visual diferente do resto do Kolae, nada diz o que precisa de atenção primeiro, e a página só cresce |
| Cadastro de quadra (`/dashboard/quadras/criar`) | 6 passos: piso → capacidade → comodidades → detalhes (nome, preço, WhatsApp) → localização → fotos; termina na aba Horário | Começa pelo piso, e o nome e o endereço só vêm no 4º e no 5º passo; o cabeçalho é outro (sem o sino nem as Mensagens) |

## O que muda (decidido pelo João em 10/11)

### 1. Painel do dono: "Hoje" no topo

No lugar das "Ações rápidas", um bloco **Hoje** com até quatro cartões. Cada um mostra um número
e leva à tela que resolve:

| Cartão | Mostra | Leva a |
| :---- | :---- | :---- |
| Pedidos esperando | Quantos pedidos de horário (D64) esperam resposta | `/dashboard/notificacoes` |
| Mensagens | Quantas mensagens do chat (D66) não foram lidas | `/dashboard/mensagens` |
| Partidas de hoje | Quantas partidas há hoje em todas as quadras, e a próxima ("Próxima: 19:00, Arena") | A agenda da quadra da próxima partida |
| Kolae Premium | Grátis: convite. Premium: "até 09/12" e o anúncio (no ar, pausado ou nenhum) | Plano ou aba Anúncio |

- Cartão com número zero fica discreto (sem destaque), mas continua lá, para o dono saber que
  está tudo em dia.
- "Novo local" vira um botão ao lado do título "Meus locais". "Minha conta" já está no menu.
- **Sem banco novo:** usa o que já existe (`MatchRequest::pendingForOwner`,
  `Conversation::unreadForOwner`, `Game::findScheduledOn`, `PremiumPlan`, `VenueAd`).
- Os cartões das quadras ficam como estão.

### 2. Painel do admin: mesmo visual, "Precisa de atenção" e páginas separadas

- **Mesmo visual do painel do dono:** o mesmo `<head>`, tema claro e escuro e cores do Kolae;
  tudo em português ("Início" no lugar de "Dashboard"); os números em cartões neutros, no estilo
  dos outros cartões do site (sem verde, roxo e laranja).
- **"Precisa de atenção" no topo do Início:** pedidos de Premium esperando, anúncios no ar para
  revisar e fotos ainda sem miniatura, cada um com o número e o link para a página dele. Sem
  nada pendente: "Tudo em dia".
- **Páginas separadas no menu**, no lugar dos cards empilhados:
  - `/admin/premium` (o card "Kolae Premium" de hoje: pedidos e donos Premium, ativar e desativar);
  - `/admin/anuncios` (o card "Anúncios": remover com motivo);
  - `/admin/fotos` (o card "Fotos das quadras": otimizar).
  As ações continuam as mesmas rotas (`/admin/premium/{id}/ativar` etc.); só voltam para a página
  nova em vez de `/admin#premium`.
- O Início fica com: "Precisa de atenção", os números (usuários, quadras, esportes) e os usuários
  recentes.
- As outras páginas do admin (Usuários, Esportes, Quadras, Mapa) ganham o mesmo cabeçalho e menu;
  o conteúdo delas não muda nesta proposta.
- O admin continua só em português (D19).

### 3. Cadastro de quadra: menos passos, começando pelo essencial

De 6 para **3 passos**:

1. **Nome e endereço** (o essencial para a quadra existir e aparecer no mapa): nome, CEP (preenche
   rua, bairro e cidade), número e complemento.
2. **Como é a quadra:** piso, capacidade, comodidades, preço e o WhatsApp (só se a conta ainda não
   tiver).
3. **Fotos** (opcional, com "Pular por agora").

- Ao salvar, cai na **Visão geral** da quadra nova, com a lista "Quadra completa" (D50) mostrando
  o que falta (horário, esportes, descrição), em vez de cair direto na aba Horário.
- O cabeçalho passa a ser o do painel (`owner_header.php`), com o sino e as Mensagens.
- **Mesmos campos e as mesmas regras de hoje** no servidor (`VenueController::store`): muda a
  ordem e o agrupamento, não o que é gravado.

## Partes (uma PR cada)

| Parte | O quê | Testes |
| :---- | :---- | :---- |
| **A** | Esta proposta | — |
| **B** | Painel do dono: bloco "Hoje" | Teste de clique novo (`painel_hoje`) e o `painel` |
| **C** | Admin: visual comum, "Precisa de atenção", páginas Premium, Anúncios e Fotos | `plano`, `anuncio_admin`, `otimizar` (passam a usar as páginas novas) e um teste do Início do admin |
| **D** | Cadastro de quadra em 3 passos, terminando na Visão geral | `cadastro` (refeito) e `visao` |

Textos novos do painel do dono nos 7 idiomas; os do admin só em português (D19).

## Datas

| Quando | O quê |
| :---- | :---- |
| 10/11 | Aprovar esta proposta |
| 11/11 | Parte B (painel do dono) |
| 12–13/11 | Parte C (admin) |
| 14/11 | Parte D (cadastro) |
| **15/11** | **Data de corte**; depois, o login com Google (D65), com a data dele revista |

## Se der errado

- **Até 15/11 não ficou pronto:** o que estiver pronto entra; o resto fica como está hoje (as
  telas atuais funcionam). O Google não espera o rework terminar.
- **Um teste de clique antigo quebra por causa do visual:** o teste é ajustado na mesma PR, nunca
  apagado.

## Registro do andamento

Anotar aqui cada passo, inclusive o que não deu certo, com a data.

| Data | O quê | Resultado |
| :---- | :---- | :---- |
| 10/11 | Proposta escrita a partir das capturas das três telas e das escolhas do João: "Hoje" no painel do dono; no admin, mesmo visual, "Precisa de atenção" e páginas separadas; cadastro com menos passos começando pelo essencial | Aprovada pelo João (D67) |
