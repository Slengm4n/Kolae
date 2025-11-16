
### Rotas do sistema Kolae

# 1. Rotas Públicas (Autenticação e Gerais)
#### 1.1. Landing Page
Exibe a landing page principal do site.

```http
  GET /
```

#### 1.2. Formulário de Login
Exibe a página com o formulário de login.
```http
  GET /login
```

#### 1.3. Processar Login
Processa a tentativa de login do usuário.
```http
  POST /login/authenticate
```
| Parâmetros (Corpo) | Tipo   | Descrição                       |
| ------------------ | ------ | ------------------------------- |
| email              | string | Obrigatório. E-mail do usuário. |
| senha              | string | Obrigatório. Senha do usuário.  |

#### 1.4. Formulário de Registro
Exibe a página com o formulário de registro de novo usuário.
```http
  GET /register
```

#### 1.5. Processar Registro
Processa e salva o novo usuário no banco de dados.
```http
  POST /register/store
```
| Parâmetros (Corpo) | Tipo   | Descrição                       |
| ------------------ | ------ | ------------------------------- |
| nome               | string | Obrigatório. Nome do usuário.   |
| email              | string | Obrigatório. E-mail do usuário. |
| senha              | string | Obrigatório. Senha do usuário.  |

#### 1.6. Logout
Desloga o usuário atual e destrói a sessão.
```http
  GET /logout
```

#### 1.7. Formulário "Esqueci a Senha"
Exibe o formulário para o usuário inserir seu e-mail e solicitar a redefinição de senha.
```http
  GET /forgot-password
```

#### 1.8. Processar "Esqueci a Senha"
Processa o pedido de redefinição.
```http
  POST /forgot-password
```
| Parâmetros (Corpo) | Tipo   | Descrição                                                         |
| ------------------ | ------ | ----------------------------------------------------------------- |
| email              | string | Obrigatório. E-mail para onde o link de recuperação será enviado. |

#### 1.9. Formulário "Resetar Senha"
Exibe o formulário onde o usuário pode definir uma nova senha.
```http
  GET /reset-password
```
| Parâmetro (URL) | Tipo   | Descrição                                                                                     |
| --------------- | ------ | --------------------------------------------------------------------------------------------- |
| token           | string | Obrigatório. O token de verificação enviado por e-mail (ex: .../reset-password?token=abc123). |

#### 1.10. Processar "Resetar Senha"
Processa e salva a nova senha do usuário.
```http
  POST /reset-password
```
| Parâmetros (Corpo) | Tipo   | Descrição                                 |
| ------------------ | ------ | ----------------------------------------- |
| token              | string | Obrigatório. O token vindo do formulário. |
| senha              | string | Obrigatório. A nova senha do usuário.     |
| confirmar-senha    | string | Obrigatório. A confirmação da nova senha. |


# 2. Painel do Usuário (Requer Login)
Endpoints prefixados com /dashboard.


#### 2.1. Dashboard principal
Exibe a página principal do painel do usuário logado.
```http
  GET /dashboard
```

#### 2.2. Adicionar CPF
Exibe a página para o usuário adicionar seu CPF (caso ainda não tenha).
```http
  GET /dashboard/cpf
```


#### 2.3. Salvar CPF
Salva o CPF do usuário no banco de dados.
```http
  POST /dashboard/cpf
```
| Parâmetros (Corpo) | Tipo   | Descrição                     |
| ------------------ | ------ | ----------------------------- |
| cpf                | string | Obrigatório. O CPF do usuário |


#### 2.4. Página de Perfil
Exibe a página "Meu Perfil" com os dados editáveis do usuário.
```http
  GET /dashboard/perfil
```


#### 2.5. Atualizar Perfil
Processa a atualização dos dados do perfil do usuário.
```http
  POST /dashboard/perfil/atualizar
```


#### 2.6. Página de Segurança
Exibe a página para o usuário alterar sua senha.
```http
  POST /dashboard/perfil/seguranca
```


#### 2.7. Atualizar Senha (Pelo Perfil)
Processa a mudança de senha (requer que o usuário saiba a senha atual).
```http
POST /dashboard/perfil/seguranca/atualizar
```
| Parâmetros (Corpo)   | Tipo   | Descrição                               |
| -------------------- | ------ | --------------------------------------- |
| senha_atual          | string | Obrigatório. A senha antiga do usuário. |
| nova_senha           | string | Obrigatório. A nova senha.              |
| confirmar_nova_senha | string | Obrigatório. Confirmação da nova senha. |


# 3. Gerenciamento de Quadras (Requer Login)
Endpoints prefixados com /dashboard/quadra.
#### 3.1. Listar Minhas Quadras 
```http
GET /dashboard/quadras
```

#### 3.2. Formulário de Criar Quadra
Exibe o formulário para cadastrar uma nova quadra.
```http
GET /dashboard/quadras/criar
```

#### 3.3. Salvar Nova Quadra
Salva a nova quadra no banco de dados.
```http
POST /dashboard/quadras/salvar
```

#### 3.4. Formulário de Editar Quadra
Exibe o formulário para editar uma quadra existente.
```http
POST /dashboard/quadras/editar/{id}
```
| Parâmetros (URL) | Tipo    | Descrição                                                                   |
| ---------------- | ------- | --------------------------------------------------------------------------- |
| id               | integer | Obrigatório. ID da quadra a ser editada (ex: /dashboard/quadras/editar/12). |

#### 3.5. Atualizar Quadra
Processa a atualização dos dados da quadra.
```http
POST /dashboard/quadras/atualizar/{id}
```
| Parâmetros (URL) | Tipo    | Descrição                                   |
| ---------------- | ------- | ------------------------------------------- |
| id               | integer | Obrigatório. ID da quadra a ser atualizada. |


#### 3.6. Inativar Quadra
Inativa a quadra do banco de dados.
```http
POST /dashboard/quadras/excluir/{id}
```

# 4. Painel de Admin (Requer Login de Admin)
Endpoints prefixados com /admin.

#### 4.1. Dashboard Admin
Exibe a página principal do painel de administração.
```http
GET /admin
```

#### 4.2. Mapa Geral
Exibe o mapa geral com todas as quadras de todos os usuários.
```http
GET /admin/mapa
```

#### 4.3. Listar Usuários
Exibe a lista de todos os usuários do sistema.
```http
GET /admin/usuarios
```

#### 4.4. Formulário Criar Usuário
Exibe o formulário para o admin criar um novo usuário.
```http
GET /admin/usuarios/criar
```

#### 4.5. Salvar Usuário (Admin)
Salva o novo usuário criado pelo admin.
```http
POST /admin/usuarios/salvar
```

#### 4.6. Formulário Editar Usuário
Exibe o formulário para editar um usuário existente.
```http
GET /admin/usuarios/editar/{id}
```
| Parâmetros (URL) | Tipo    | Descrição                                 |
| ---------------- | ------- | ----------------------------------------- |
| id               | integer | Obrigatório. ID do usuário a ser editado. |

##### 4.7. Atualizar Usuário (Admin)
Processa a atualização do usuário (requer id no corpo).
```http
POST /admin/usuarios/atualizar
```

#### 4.8 Excluir Usuário (Admin)
Exclui o usuário do banco de dados.
```http
POST /admin/usuarios/excluir/{id}
```
| Parâmetros (URL) | Tipo    | Descrição                                  |
| ---------------- | ------- | ------------------------------------------ |
| id               | integer | Obrigatório. ID do usuário a ser excluído. |


#### 4.9. Listar Esportes
Exibe a lista de todos os esportes cadastrados no sistema.
```http
GET /admin/esportes
```

#### 5.0. Formulário Criar Esporte
Exibe o formulário para adicionar um novo esporte.
```http
GET /admin/esportes/criar
```

##### 5.1. Salvar Esporte (Admin)
```http
POST /admin/esportes/salvar
```

#### 5.2. Formulário Editar Esporte
Exibe o formulário para editar um esporte existente.
```http
GET /admin/esportes/editar/{ID}
```
| Parâmetros (URL) | Tipo    | Descrição                                 |
| ---------------- | ------- | ----------------------------------------- |
| id               | integer | Obrigatório. ID do esporte a ser editado. |

#### 5.3. Excluir Esporte (Admin)
```http
GET /admin/esportes/excluir/{ID}
```
| Parâmetros (URL) | Tipo    | Descrição                                  |
| ---------------- | ------- | ------------------------------------------ |
| id               | integer | Obrigatório. ID do esporte a ser excluído. |
