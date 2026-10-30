<?php

namespace App\Controllers;

use App\Core\BaseApiController;
use App\Core\Mailer;
use App\Models\User;
use Exception;
use App\Core\LoginThrottle;

class AuthApiController extends BaseApiController
{
    /**
     * POST /api/v1/auth/login
     * Body: { "email": "...", "password": "..." }
     * Contrato em docs/api.md.
     */
    public function login()
    {
        $input = $this->getJsonInput();

        $email = trim((string)($input['email'] ?? ''));
        $password = (string)($input['password'] ?? '');

        $errors = [];
        if ($email === '') {
            $errors['email'] = 'O e-mail é obrigatório.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Formato de e-mail inválido.';
        }
        if ($password === '') {
            $errors['password'] = 'A senha é obrigatória.';
        }
        $this->failIfInvalid($errors);

        // Muitos erros seguidos: 429 com Retry-After, sem conferir a senha (LoginThrottle)
        $wait = LoginThrottle::retryAfter($email);
        if ($wait !== null) {
            header('Retry-After: ' . $wait);
            $minutes = LoginThrottle::minutes($wait);
            $this->sendError("Muitas tentativas de login. Tente de novo em {$minutes} min.", 'TOO_MANY_ATTEMPTS', 429);
        }

        try {
            //Busca o usuário pelo email (findByEmail só retorna usuários ativos)
            $user = User::findByEmail($email);

            if (!$user || !password_verify($password, $user['password'])) {
                LoginThrottle::recordFailure($email);
                $this->sendError('E-mail ou senha incorretos.', 'INVALID_CREDENTIALS', 401);
            }

            LoginThrottle::clear($email);

            $this->sendSuccess($this->tokenResponse($user));
        } catch (Exception $e) {
            //Captura erros gerais (ex: falha na conexão com DB, erro ao gerar JWT)
            error_log("Erro no login da API: " . $e->getMessage());
            $this->sendError('Ocorreu um erro interno durante o login.', 'LOGIN_FAILED', 500);
        }
    }

    /**
     * POST /api/v1/auth/register
     * Body: { name, email, birthdate, password, password_confirmation }
     * Cria a conta como "player" e já devolve o token (mesmo formato do login).
     */
    public function register()
    {
        $input = $this->getJsonInput();

        $name = trim((string)($input['name'] ?? ''));
        $email = trim((string)($input['email'] ?? ''));
        $birthdate = trim((string)($input['birthdate'] ?? ''));
        $password = (string)($input['password'] ?? '');
        $confirmation = (string)($input['password_confirmation'] ?? '');

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'O nome é obrigatório.';
        } elseif (mb_strlen($name) > 255) {
            $errors['name'] = 'O nome pode ter no máximo 255 caracteres.';
        }

        if ($email === '') {
            $errors['email'] = 'O e-mail é obrigatório.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
            $errors['email'] = 'Formato de e-mail inválido.';
        } elseif (User::emailExists($email)) {
            $errors['email'] = 'Este e-mail já está cadastrado.';
        }

        if ($birthdate === '') {
            $errors['birthdate'] = 'A data de nascimento é obrigatória.';
        } elseif (!User::isValidAdultBirthdate($birthdate)) {
            $errors['birthdate'] = 'Informe uma data válida (AAAA-MM-DD). É preciso ter ' . User::MIN_AGE . ' anos ou mais.';
        }

        $errors += self::validateNewPassword($password, $confirmation);
        $this->failIfInvalid($errors);

        try {
            $userId = User::create([
                'name' => $name,
                'email' => $email,
                'birthdate' => $birthdate,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'player'
            ]);
            if (!$userId) {
                throw new Exception('User::create retornou false.');
            }

            $this->sendSuccess($this->tokenResponse(User::findById($userId)), 201);
        } catch (Exception $e) {
            error_log("Erro no cadastro pela API: " . $e->getMessage());
            $this->sendError('Não foi possível criar a conta.', 'REGISTER_FAILED', 500);
        }
    }

    /**
     * POST /api/v1/auth/forgot-password
     * Body: { "email": "..." }
     * Sempre responde igual, exista ou não a conta (não revela quem tem cadastro).
     */
    public function forgotPassword()
    {
        $input = $this->getJsonInput();
        $email = trim((string)($input['email'] ?? ''));

        if ($email === '') {
            $this->failIfInvalid(['email' => 'O e-mail é obrigatório.']);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->failIfInvalid(['email' => 'Formato de e-mail inválido.']);
        }

        $user = User::findByEmail($email);
        if ($user) {
            // Falha de envio vai para o log; a resposta continua a mesma
            Mailer::sendPasswordReset($email, $user['name']);
        }

        $this->sendSuccess(null);
    }

    /**
     * Regras de senha nova (cadastro e troca de senha). Retorna os erros por campo.
     */
    public static function validateNewPassword(string $password, string $confirmation): array
    {
        if ($password === '') {
            return ['password' => 'A senha é obrigatória.'];
        }
        if (mb_strlen($password) < User::PASSWORD_MIN_LENGTH) {
            return ['password' => 'A senha precisa ter pelo menos ' . User::PASSWORD_MIN_LENGTH . ' caracteres.'];
        }
        if ($password !== $confirmation) {
            return ['password_confirmation' => 'As senhas não conferem.'];
        }
        return [];
    }
}
