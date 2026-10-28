<?php

namespace App\Controllers;

use App\Core\ApiFormat;
use App\Core\BaseApiController;
use App\Core\ImageHelper;
use App\Models\User;

/**
 * Endpoints da pessoa logada (/api/v1/me). Todos exigem token.
 * Contrato em docs/api.md.
 */
class MeApiController extends BaseApiController
{
    /**
     * GET /api/v1/me
     */
    public function show()
    {
        $this->authenticateRequest();
        $this->sendSuccess(ApiFormat::user($this->user));
    }

    /**
     * PATCH /api/v1/me
     * Body: { "name"?: "...", "birthdate"?: "YYYY-MM-DD" }. Só os campos enviados mudam.
     */
    public function update()
    {
        $this->authenticateRequest();
        $input = $this->getJsonInput();

        $data = [];
        $errors = [];

        if (array_key_exists('name', $input)) {
            $name = trim((string)$input['name']);
            if ($name === '') {
                $errors['name'] = 'O nome não pode ficar vazio.';
            } elseif (mb_strlen($name) > 255) {
                $errors['name'] = 'O nome pode ter no máximo 255 caracteres.';
            } else {
                $data['name'] = $name;
            }
        }

        if (array_key_exists('birthdate', $input)) {
            $birthdate = trim((string)$input['birthdate']);
            if (!User::isValidAdultBirthdate($birthdate)) {
                $errors['birthdate'] = 'Informe uma data válida (AAAA-MM-DD). É preciso ter ' . User::MIN_AGE . ' anos ou mais.';
            } else {
                $data['birthdate'] = $birthdate;
            }
        }

        $this->failIfInvalid($errors);

        if ($data && !User::update($this->userId, $data)) {
            $this->sendError('Não foi possível salvar o perfil.', 'PROFILE_UPDATE_FAILED', 500);
        }

        $this->sendSuccess(ApiFormat::user(User::findById($this->userId)));
    }

    /**
     * POST /api/v1/me/avatar
     * multipart/form-data, arquivo no campo "avatar" (JPG, PNG ou GIF até 10 MB).
     */
    public function avatar()
    {
        $this->authenticateRequest();

        // Corpo maior que o post_max_size do PHP: o PHP descarta tudo e $_FILES chega vazio
        $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
        $postMax = self::iniBytes(ini_get('post_max_size')); // 0 = sem limite
        if ($postMax > 0 && $contentLength > $postMax && empty($_FILES)) {
            $this->sendError('A imagem é maior que o limite de 10 MB.', 'FILE_TOO_LARGE', 413);
        }

        $file = $_FILES['avatar'] ?? null;
        $uploadError = $file['error'] ?? UPLOAD_ERR_NO_FILE;

        if ($uploadError === UPLOAD_ERR_NO_FILE || is_array($uploadError)) {
            $this->failIfInvalid(['avatar' => 'Envie uma imagem no campo "avatar".']);
        }
        if ($uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE) {
            $this->sendError('A imagem é maior que o limite de 10 MB.', 'FILE_TOO_LARGE', 413);
        }
        if ($uploadError !== UPLOAD_ERR_OK) {
            error_log("Upload de avatar pela API falhou com o código $uploadError.");
            $this->sendError('Não foi possível receber a imagem.', 'UPLOAD_FAILED', 500);
        }
        if ($file['size'] > 10 * 1024 * 1024) {
            $this->sendError('A imagem é maior que o limite de 10 MB.', 'FILE_TOO_LARGE', 413);
        }

        // Valida pelo conteúdo real (não pela extensão), move e otimiza
        $uploadDir = BASE_PATH . '/uploads/avatars/';
        $newFileName = ImageHelper::processAndSave($file, $uploadDir, $this->userId);
        if (!$newFileName) {
            $this->failIfInvalid(['avatar' => 'Envie uma imagem JPG, PNG ou GIF.']);
        }

        $oldFileName = $this->user['avatar_path'] ?? null;
        if (!User::update($this->userId, ['avatar_path' => $newFileName])) {
            @unlink($uploadDir . $this->userId . '/' . $newFileName);
            $this->sendError('Não foi possível salvar a imagem.', 'AVATAR_UPDATE_FAILED', 500);
        }

        // Remove a foto antiga para não acumular arquivos no servidor
        if ($oldFileName) {
            $oldPath = $uploadDir . $this->userId . '/' . basename($oldFileName);
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $this->sendSuccess(ApiFormat::user(User::findById($this->userId)));
    }

    /**
     * PUT /api/v1/me/password
     * Body: { current_password, password, password_confirmation }
     */
    public function password()
    {
        $this->authenticateRequest();
        $input = $this->getJsonInput();

        $current = (string)($input['current_password'] ?? '');
        $password = (string)($input['password'] ?? '');
        $confirmation = (string)($input['password_confirmation'] ?? '');

        $errors = [];
        if ($current === '') {
            $errors['current_password'] = 'Informe a senha atual.';
        } elseif (!password_verify($current, $this->user['password'])) {
            $errors['current_password'] = 'A senha atual está incorreta.';
        }
        $errors += AuthApiController::validateNewPassword($password, $confirmation);
        $this->failIfInvalid($errors);

        $ok = User::update($this->userId, [
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'force_password_change' => 0
        ]);
        if (!$ok) {
            $this->sendError('Não foi possível trocar a senha.', 'PASSWORD_UPDATE_FAILED', 500);
        }

        $this->sendSuccess(null);
    }

    /**
     * Converte valores do php.ini como "40M" em bytes.
     */
    private static function iniBytes($value): int
    {
        $value = trim((string)$value);
        $number = (int)$value;
        switch (strtoupper(substr($value, -1))) {
            case 'G': return $number * 1024 ** 3;
            case 'M': return $number * 1024 ** 2;
            case 'K': return $number * 1024;
            default:  return $number;
        }
    }
}
