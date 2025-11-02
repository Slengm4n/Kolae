Verbo,Rota,Controlador::Método,Descrição
GET,/,HomeController::index,Exibe a landing page principal.
GET,/login,AuthController::index,Exibe o formulário de login.
POST,/login/authenticate,AuthController::authenticate,"Processa os dados (email, senha) e tenta logar o usuário."
GET,/register,AuthController::register,Exibe o formulário de registro.
POST,/register/store,AuthController::store,Processa os dados e cria um novo usuário.
GET,/logout,AuthController::logout,Desloga o usuário e destrói a sessão.
GET,/forgot-password,AuthController::showForgotPasswordForm,"Exibe o formulário ""Esqueci a senha""."
POST,/forgot-password,AuthController::handleForgotPassword,Processa o pedido de redefinição (recebe email).
GET,/reset-password,AuthController::showResetForm,Exibe o formulário para criar a nova senha (requer token na URL).
POST,/reset-password,AuthController::handleResetPassword,"Salva a nova senha (recebe token, senha)."