# Sugestões de funcionalidades e melhorias

Lista viva de ideias para o Kolae, com o motivo e quando faz sentido fazer. **Não é decisão**: quando uma sugestão for aprovada e feita, ela vira uma decisão em [`decisoes.md`](decisoes.md) e sai daqui (ou fica marcada como feita).

Esforço: **P** (até meio dia), **M** (1 a 2 dias), **G** (mais que isso).
Última revisão: 02/11/2026.

## Até a entrega (cabe antes do congelamento, 21/11)

Critério: aparece bem na banca e mexe pouco no que já está pronto e testado.

| # | Sugestão | Por quê | Esforço | Situação |
| :---- | :---- | :---- | :---- | :---- |
| 1 | **Página "Quadras" no site** (`/quadras`): lista e mapa lado a lado, filtros (esporte, disponível agora, coberta, iluminação, preço), cada quadra abrindo a página pública | Hoje o site só mostra as quadras em destaque na home; com ela, a banca vê o produto inteiro também pelo computador. A busca e os filtros já existem na API | M | **Feita (D51)** |
| 2 | **QR Code da quadra** na Visão geral, apontando para a página pública | O dono imprime e cola na quadra; na apresentação, dá para escanear e abrir na hora | P | Sugerida |
| 3 | **Limite de envios no "esqueci a senha"** (site e API), como o do login (D45) | Hoje qualquer um pode disparar e-mails sem parar para um endereço: incomoda a pessoa e pode fazer a HostGator bloquear a conta de e-mail | P | Recomendada |
| 4 | **Páginas de erro com o visual novo** (404, 403) | As atuais são do visual antigo, sem cabeçalho nem tema claro/escuro; a 404 aparece, por exemplo, em `/quadra/{id}` de quadra desativada | P | Sugerida |
| 5 | **Números no painel do dono**: partidas na semana, horas ocupadas, dia mais movimentado | Mostra que o produto ajuda a gerenciar, não só a aparecer no mapa. Só lê dados que já existem | M | Sugerida |
| 6 | **Testes no repositório e no deploy** (S6): os testes de clique em `tests/e2e/` e o GitHub Actions rodando os testes PHP antes do FTP | Hoje o merge na `main` publica sem conferir nada; esses testes pegaram bugs reais nas S4 e S5 | M | Planejada (S6) |
| 7 | **`robots.txt` e `sitemap.xml`** com as páginas públicas (home, quadras, termos) | O Google passa a encontrar as quadras; quase sem custo | P | Sugerida |

## Para o app (Rafael)

| Sugestão | Por quê | Esforço |
| :---- | :---- | :---- |
| Botão **"Compartilhar"** no detalhe da quadra, com o link `https://kolae.online/quadra/{id}` | Quem recebe abre no navegador, com foto e nome na prévia do WhatsApp (D49) | P |
| **Galeria em tela cheia** no detalhe da quadra | A API já manda as fotos na ordem do dono (D46) e leves (D48) | P |
| Tratar **`429 TOO_MANY_ATTEMPTS`** com uma mensagem própria (hoje mostra a `message` da API, que já funciona) | Fica mais caprichado; opcional | P |

## Depois da entrega (Kolae 2.0)

| Sugestão | Por quê | Esforço |
| :---- | :---- | :---- |
| **Partidas que se repetem** ("toda terça às 19h") | Pedido natural do dono; ficou de fora pela D39 porque mexe na agenda e no status | M |
| **Arrastar para reordenar fotos** | Os botões de mover já resolvem e funcionam melhor no celular e com leitor de tela (D46) | P |
| **Comunidade, avaliações e favoritos de verdade** | Hoje são vitrine (D04); precisam de banco, moderação e telas | G |
| **Reserva e pagamento pela plataforma** | Hoje o aluguel é combinado direto com o dono; é praticamente outro produto | G |
| **API respondendo no idioma do app** (`Accept-Language`) | O app já envia o idioma; as mensagens ainda saem em português | M |
| **Notificações** (ex.: lembrete de partida para o dono) | Precisa de push no app e de tarefas agendadas no servidor | G |
| **Planos Plus e Pro** (hoje vitrine no app) | Possível modelo de receita: descontos em quadras parceiras, eventos, torneios | G |
| **Migração para Laravel** (D01, branch `v2.0-laravel`) | O contrato da API já segue o formato do Laravel, então o app quase não muda | G |

## Melhorias técnicas

| Sugestão | Por quê | Quando |
| :---- | :---- | :---- |
| **Homologação** num subdomínio (ex.: `teste.kolae.online` no cPanel), recebendo o deploy da `develop` | Testar na hospedagem real antes de publicar; hoje só existem o local e a produção | Antes da entrega, se der |
| **Backup automático do banco** conferido no cPanel | Proteção contra erro humano perto da apresentação | Antes da entrega |
| **`.gitattributes`** padronizando o fim de linha (CRLF/LF) | Os arquivos estão misturados; isso gera avisos no git e já quebrou edições automáticas | Qualquer hora (P) |
| **Logout por POST** (com o token CSRF) em vez de link GET | Um site de fora consegue deslogar a pessoa com um link; baixo risco | Depois da entrega |
| **Apagar as fotos do disco** quando uma quadra é excluída | Hoje a exclusão é lógica e os arquivos ficam no servidor | Depois da entrega |
| **Remover `users.remember_token`** | Ficou sem uso com a D44 | Na migração para Laravel |
| **Traduzir o painel do admin** | Fora do escopo pela D19 | Depois da entrega |
