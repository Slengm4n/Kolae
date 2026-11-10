# Sugestões de funcionalidades e melhorias

Lista viva de ideias para o Kolae, com o motivo e quando faz sentido fazer. **Não é decisão**: quando uma sugestão for aprovada e feita, ela vira uma decisão em [`decisoes.md`](decisoes.md) e sai daqui (ou fica marcada como feita).

Esforço: **P** (até meio dia), **M** (1 a 2 dias), **G** (mais que isso).
Última revisão: 05/11/2026 (redes sociais feitas; propostas de login com Google e chat).

## Até a entrega (cabe antes do congelamento, 21/11)

Critério: aparece bem na banca e mexe pouco no que já está pronto e testado.

| # | Sugestão | Por quê | Esforço | Situação |
| :---- | :---- | :---- | :---- | :---- |
| 1 | **Página "Quadras" no site** (`/quadras`): lista e mapa lado a lado, filtros (esporte, disponível agora, coberta, iluminação, preço), cada quadra abrindo a página pública | Hoje o site só mostra as quadras em destaque na home; com ela, a banca vê o produto inteiro também pelo computador. A busca e os filtros já existem na API | M | **Feita (D51)** |
| 2 | **QR Code da quadra** na Visão geral, apontando para a página pública | O dono imprime e cola na quadra; na apresentação, dá para escanear e abrir na hora | P | **Feita (D54)** |
| 3 | **Limite de envios no "esqueci a senha"** (site e API), como o do login (D45) | Hoje qualquer um pode disparar e-mails sem parar para um endereço: incomoda a pessoa e pode fazer a HostGator bloquear a conta de e-mail | P | **Feita (D52)** |
| 4 | **Páginas de erro com o visual novo** (404, 403) | As atuais são do visual antigo, sem cabeçalho nem tema claro/escuro; a 404 aparece, por exemplo, em `/quadra/{id}` de quadra desativada | P | **Feita (D53)** |
| 5 | **Números no painel do dono**: partidas na semana, horas ocupadas, dia mais movimentado | Mostra que o produto ajuda a gerenciar, não só a aparecer no mapa. Só lê dados que já existem | M | Sugerida |
| 6 | **Testes no repositório e no deploy** (S6): os testes de clique em `tests/e2e/` e o GitHub Actions rodando os testes PHP antes do FTP | Hoje o merge na `main` publica sem conferir nada; esses testes pegaram bugs reais nas S4 e S5 | M | **Feita (D56)** |
| 7 | **`robots.txt` e `sitemap.xml`** com as páginas públicas (home, quadras, termos) | O Google passa a encontrar as quadras; quase sem custo | P | Sugerida |
| 8 | **Telas de entrada, avisos e confirmações no visual do Kolae** | Primeira impressão da banca; as telas antigas tinham erros visíveis | M | **Feita (D57)** |
| 9 | **Confirmação de e-mail (versão leve)** e **miniaturas das fotos** | Conta mais confiável sem travar ninguém; cards leves no 4G | M | **Feita (D58)** |
| 10 | **Redes sociais da quadra** (Instagram, TikTok, Facebook, site) no painel, na página pública e no app. Proposta de banco e contrato em [`proposta-redes-sociais.md`](proposta-redes-sociais.md) | A pessoa confere fotos e eventos antes de alugar; para o dono é divulgação de graça; na banca, a quadra parece um negócio de verdade | M | **Feita (D60)** |
| 11 | **Entrar com Google** no app (e no site, se sobrar tempo). Proposta em [`proposta-login-google.md`](proposta-login-google.md) | Cadastro em dois toques e menos senha esquecida; o app pede só a data de nascimento na primeira vez | M | Proposta (05/11); corte em 12/11 |
| 12 | **Chat com o responsável pela quadra**: a pessoa escreve pelo app, o responsável responde pelo painel, por consulta periódica (sem WebSocket na HostGator). Proposta em [`proposta-chat.md`](proposta-chat.md) | Nem todo dono quer expor o WhatsApp; a conversa fica no Kolae; na banca mostra o ciclo completo até o aluguel | G | Proposta (05/11); corte em 18/11 |

## Para o app (Rafael)

Nenhuma destas muda o contrato, exceto as redes sociais (#10 acima). As branches e notas ficam
no repositório do app (`changelog/<branch>.md`).

| Sugestão | Por quê | Esforço | Situação |
| :---- | :---- | :---- | :---- |
| Botão **"Compartilhar"** no detalhe da quadra, com o link `https://kolae.online/quadra/{id}` | Quem recebe abre no navegador, com foto e nome na prévia do WhatsApp (D49) | P | **Feita** (PR #9 do app) |
| **Galeria em tela cheia** no detalhe da quadra | A API já manda as fotos na ordem do dono (D46) e leves (D48) | P | Sugerida |
| Tratar **`429 TOO_MANY_ATTEMPTS`** com uma mensagem própria (hoje mostra a `message` da API, que já funciona) | Fica mais caprichado; opcional | P | Sugerida |
| **Telas de entrada novas**: linhas de quadra animadas, cartão "ao vivo" com quadras de exemplo, cadastro em duas etapas, data de nascimento em roleta, abertura sem pisca | A proposta do app aparece logo no login; primeira impressão da banca | M | Feita (branch `feat/auth-redesign`, falta o PR) |
| **Status e distância no card da quadra** (Pesquisar e Favoritos), atualizando sozinho quando o status vence | O app promete "ver se está livre", mas o card só mostrava foto, nome e preço; os dados já vinham na API | P | Feita (branch `feat/venue-card-status`, falta o PR) |
| **Pesquisar nova**: cabeçalho com "quadras livres agora", buscas recentes, filtros em chips (livre agora, grátis, coberta, iluminação) e carregamento em esqueleto | A tela principal estava simples demais; os filtros já existem na API | M | Próxima |
| **Vibração e animações no app todo**, com a opção "Vibração" nas Configurações (ao lado de "reduzir movimento") | Acabamento que aparece na hora; a opção mantém o controle com quem usa | P | Sugerida |
| **Redes sociais da quadra** no detalhe (ícones abaixo do "Falar com o dono") | Ver #10 | P | **Feita** (PR #7 do app) |
| **Entrar com Google** e **chat com o responsável** | Ver #11 e #12 (mudam o contrato) | M e G | Proposta (05/11) |

## Depois da entrega (Kolae 2.0)

| Sugestão | Por quê | Esforço |
| :---- | :---- | :---- |
| **Confirmação de e-mail completa** (bloquear ações até confirmar, trocar e-mail com código) | A versão leve (D58) só avisa; bloquear exige mudar o app e o fluxo de cadastro | M |
| **Mais comodidades** (Wi-Fi, estacionamento, vestiário, bar, coletes, bola) | O assistente antigo oferecia, mas o banco só guarda iluminação, coberta e área de lazer (D57). Pede colunas novas, a API e o app mostrarem | M |
| **Partidas que se repetem** ("toda terça às 19h") | Pedido natural do dono; ficou de fora pela D39 porque mexe na agenda e no status | M |
| **Arrastar para reordenar fotos** | Os botões de mover já resolvem e funcionam melhor no celular e com leitor de tela (D46) | P |
| **Partidas com jogadores**: partida aberta com vagas e lista de espera, pedido de horário aprovado pelo dono, presença, avaliações e ranking de participação. Proposta completa em [`proposta-partidas.md`](proposta-partidas.md) | Resolve "achar gente para jogar" sem tirar a agenda do dono; muda a D02 em parte, por isso fica para depois | G |
| **Comunidade de verdade** (posts, comentários, seguidores) | Hoje é vitrine (D04). Conteúdo de usuário aberto exige moderação (denúncia, remoção, bloqueio) antes de ir ao ar, também pela LGPD | G |
| **Chat em tempo real** (WebSocket, digitando..., fotos) | A versão por consulta periódica é a #12; o tempo real precisa de servidor próprio no Laravel. Se a #12 for adiada, ela volta para cá | G |
| **Favoritos na conta** (hoje ficam no aparelho, D04) | Trocou de celular, os favoritos vão junto; pede `GET/PUT /me/favorites` | P |
| **Avaliações de verdade** | Entram na fase 3 da proposta de partidas: só quem jogou avalia | M |
| **Reserva e pagamento pela plataforma** | Hoje o aluguel é combinado direto com o dono; é praticamente outro produto | G |
| **API respondendo no idioma do app** (`Accept-Language`) | O app já envia o idioma; as mensagens ainda saem em português | M |
| **Notificações** (ex.: lembrete de partida, vaga aberta, mensagem nova) | Precisa de push e de tarefas agendadas no servidor. O push não funciona no Expo Go no Android, só no APK, e pede um projeto Firebase (grátis) | G |
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
| **Verificação em duas etapas (2FA)** | Pedido do João em 10/11, para depois do login com Google: código por e-mail ou por aplicativo autenticador ao entrar; hoje é vitrine no app (D04) | Depois do login com Google |
| **Rework do painel do admin** | Pedido do João em 10/11, por último na fila (depois do Google e da 2FA). O que entra ainda vai ser definido; ideias já anotadas: números neutros, "Precisa de atenção", tudo em português, `<head>` e tema comuns às outras páginas | Por último |
