# Testes de clique (ponta a ponta)

Abrem o site num navegador de verdade (sem janela) e fazem o que uma pessoa faria: entram, clicam, preenchem, enviam. Conferem a tela, o banco e a API. Pegaram bugs reais nas S4 e S5 que os testes de PHP não pegariam (ex.: pontos invisíveis no mapa, QR com linhas que o leitor não lia).

São **só para o ambiente local** (XAMPP). Não rodam no GitHub Actions: lá rodam os testes de PHP da pasta `tests/` antes de cada deploy (D56).

## Antes da primeira vez

1. XAMPP com **Apache e MySQL ligados** e o site abrindo em `http://localhost/kolae`.
2. Banco local `kolae_db` com o `database/schema.sql` (ou os patches) aplicado.
3. `config.php` local apontando para esse banco (**`DB_HOST` precisa ser `localhost`**: os scripts recusam qualquer outro, para nunca mexer na produção).
4. Na raiz do projeto: `npm install` (instala o `puppeteer-core`, que controla o navegador).
5. **Chrome ou Edge** instalado (usa o que já existe no PC; não baixa navegador).

## Rodar

Da raiz do projeto:

```bash
npm run test:e2e                     # todos (uns 10 minutos)
node tests/e2e/rodar.js fotos visao  # só alguns
```

Antes de cada teste o `rodar.js`:
- recria os dados de teste no banco local (`preparar_banco.php`: roda `database/seeds/teste_status_limpar.sql` e `teste_status.sql`, deixa as contas de teste com a senha `teste123` e zera os limites de login);
- passa ao teste os ids das quadras de que ele precisa.

No fim mostra uma tabela com cada teste e o total de passos. Sai com erro (código 1) se algum falhar. As capturas de tela ficam em `tests/e2e/.capturas/` (fora do git), boas para conferir o visual.

## Dados de teste

| Quadra | Situação |
| :---- | :---- |
| A · Arena Teste S3 | Em uso agora (partida de 30 minutos a partir da hora em que o seed roda) |
| B · Beach Teste S3 | Disponível, grátis, com partida daqui a 1 hora |
| C · Clay Teste S3 | Em manutenção |
| D · Duda Teste S3 | Sem horário e sem coordenadas |
| E · Excluida Teste S3 | Desativada (fora da API e da página pública) |

Contas (senha `teste123`): `dono-s3@kolae.test` (dono das quadras acima), `dono@teste.local` (outro dono, para testar que um não mexe na quadra do outro) e `admin@teste.local`.

## Configurar outro PC

Os caminhos têm padrão para o XAMPP e podem ser trocados por variáveis de ambiente:

| Variável | Para quê | Padrão |
| :---- | :---- | :---- |
| `KOLAE_URL` | endereço do site local | `http://localhost/kolae` |
| `KOLAE_BROWSER` | Chrome ou Edge | o primeiro que existir |
| `KOLAE_PHP` | `php.exe` | `C:/xampp/php/php.exe` (ou o do PATH) |
| `KOLAE_MYSQL` | `mysql.exe` | `C:/xampp/mysql/bin/mysql.exe` (ou o do PATH) |
| `KOLAE_DB` | banco local | `kolae_db` |

Exemplo (Git Bash): `KOLAE_URL=http://localhost:8080/kolae npm run test:e2e`

## Cuidados

- **Perto da meia-noite** alguns testes da agenda falham: o seed cria uma partida "agora" que passa para o dia seguinte. Não é bug; rodar em outro horário.
- Os testes de clique das abas Horário e Agenda marcam o guia passo a passo (D50) como visto depois do login; um teste novo que abra essas abas precisa fazer o mesmo, senão o guia cobre os botões.
- O teste `fotos`, o `otimizar` e o `publica` apagam e recriam as fotos das quadras de teste em `uploads/venues/`.
- O teste `qr` usa o `jsQR` (jsDelivr) para ler o QR desenhado; precisa de internet. O `quadras` também, para os mapas do OpenStreetMap.

## Testes

| Teste | O que cobre |
| :---- | :---- |
| `horario` | Aba Horário: interruptores, preencher rápido, validação, resumo do app |
| `agenda` | Aba Agenda: linha do tempo, agendar, conflito, cancelar, outro dono barrado |
| `dados` | Aba Dados: preço, descrição, esportes, situação, WhatsApp |
| `cadastro` | Cadastro de quadra em passos, até cair na aba Horário |
| `painel` | Painel do dono com status ao vivo |
| `s5` | CSRF, "lembrar de mim", senha mínima, limite de login (site e API), XSS do mapa |
| `fotos` | Aba Fotos: enviar, capa, ordem, apagar, limite de 10 |
| `visao` | Visão geral: galeria, tela cheia, informações, "Quadra completa" |
| `otimizar` | Fotos otimizadas no envio, rotação pelo EXIF, botão do admin |
| `publica` | Página pública `/quadra/{id}`: WhatsApp, compartilhar, 404, prévia de link |
| `tutorial` | Guia passo a passo das abas Horário e Agenda |
| `legal` | Termos de Uso e Política de Privacidade |
| `quadras` | Página `/quadras`: filtros iguais aos da API, "Perto de mim", mapa |
| `erros` | Páginas 404, 403 e "Página expirada" |
| `qr` | QR Code da quadra (lê o código e confere o endereço) |
