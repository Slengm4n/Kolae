# Proposta: redes sociais da quadra

**Situação:** proposta (05/11/2026). Ainda não é decisão; aprovada, vira a D60 em
[`decisoes.md`](decisoes.md) e o formato entra em [`api.md`](api.md).
**Esforço:** M (banco, painel do dono, API e app). **Cabe antes do congelamento (21/11).**

## O que é

O dono informa no painel os perfis da quadra (Instagram, Facebook, TikTok e site). O app e a
página pública da quadra mostram ícones que abrem cada perfil, como o "Falar com o dono" já faz
com o WhatsApp (D24).

**Por quê:** é como a pessoa confere fotos recentes, eventos e a "cara" do lugar antes de
alugar. Para o dono, é divulgação de graça. Na banca, mostra a quadra como um negócio de verdade.

## Onde guardar: na quadra, não no dono

Um dono pode ter várias quadras, cada uma com o seu Instagram. Por isso os campos ficam na
**quadra** (`venues`), não no usuário.

## Proposta de banco

Quatro colunas novas em `venues`, todas opcionais (`NULL` = não informado):

| Coluna | Tipo | O que guarda | Exemplo |
| :---- | :---- | :---- | :---- |
| `instagram` | `VARCHAR(30)` | Só o usuário, sem `@` | `arenadoxuxa` |
| `tiktok` | `VARCHAR(30)` | Só o usuário, sem `@` | `arenadoxuxa` |
| `facebook_url` | `VARCHAR(255)` | Endereço completo (os do Facebook variam muito) | `https://facebook.com/arenadoxuxa` |
| `website_url` | `VARCHAR(255)` | Endereço completo, `https://` | `https://arenadoxuxa.com.br` |

**Validação no site (o painel aceita o que a pessoa colar):**
- Instagram e TikTok: aceita `@usuario`, `usuario` ou o link do perfil, e guarda só o usuário
  (letras, números, `.` e `_`).
- Facebook e site: precisa começar com `https://`. Se a pessoa colar sem, o site completa. O
  Facebook precisa ser de `facebook.com` ou `fb.com`.

## Proposta de contrato (para revisar antes de entrar no `api.md`)

Em `GET /venues/{id}` (detalhe), um objeto novo `socials`, **sempre presente**, com `null` no
que não foi informado. Fica fora do resumo de `GET /venues`, porque a lista não precisa disso.

```json
"socials": {
  "instagram": "arenadoxuxa",
  "tiktok": null,
  "facebook_url": "https://facebook.com/arenadoxuxa",
  "website_url": null
}
```

| Campo | Regra para o app |
| :---- | :---- |
| `socials.instagram` | Abrir `https://instagram.com/<valor>`. `null` = sem ícone |
| `socials.tiktok` | Abrir `https://www.tiktok.com/@<valor>`. `null` = sem ícone |
| `socials.facebook_url` | Abrir como está. `null` = sem ícone |
| `socials.website_url` | Abrir como está. `null` = sem ícone |

Com os quatro `null`, a seção inteira some (mesma ideia do `description` `null`, D30).

## Onde aparece

- **Painel do dono:** aba de dados da quadra, seção "Redes sociais", com os quatro campos e a
  prévia de como fica. Também entra na lista **Quadra completa** (D47) como item opcional.
- **Página pública da quadra (D49) e página "Quadras" (D51):** ícones abaixo do endereço.
- **App, no detalhe da quadra:** uma linha de ícones redondos abaixo do "Falar com o dono", cada
  um com o nome da rede para o leitor de tela ("Instagram da quadra, abre outro app").

## Ordem de trabalho

1. Aprovar o formato acima (João e Rafael) e registrar a D60.
2. Escrever no `api.md`, na seção do `GET /venues/{id}`.
3. App: mock com os quatro casos (todos, alguns, nenhum, só site), sem esperar a API.
4. Backend: migração, painel, validação, API e página pública, com teste ponta a ponta.

## Em aberto

- Entram outras redes (YouTube, X, Kwai)? A sugestão é começar com as quatro e acrescentar
  depois: o objeto `socials` aceita campos novos sem quebrar o app.
- Mostrar os ícones também no card do mapa? A sugestão é não: o card já está cheio.
