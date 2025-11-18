<?php

namespace App\Controllers;

use App\Core\ImageHelper;
use App\Core\AuthHelper;
use App\Core\ViewHelper;
use App\Models\User;
use App\Models\Venue;


class UserController
{


    public function dashboard()
    {
        AuthHelper::check();
        $userId = $_SESSION['user_id'];
        $user = User::findById($userId);

        $data = [
            'userName' => $_SESSION['user_name'] ?? 'Usuario',
            'userVenues' => Venue::findByUserId($userId),

            'showCnpjModal' => empty($user['cnpj'])
        ];

        ViewHelper::render('users/dashboard', $data);
    }

    public function profile()
    {
        AuthHelper::check();
        $data = [
            'userName' => $_SESSION['user_name'] ?? 'Usuario',
            'user' => User::findById($_SESSION['user_id'])
        ];
        ViewHelper::render('users/profile', $data);
    }



    public function updateProfile()
    {
        AuthHelper::check();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/dashboard/perfil');
            exit;
        }

        $userId = $_SESSION['user_id'];
        $newAvatarFileName = null; // Variável para guardar o nome do novo arquivo


        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {

            // Define o diretório CORRETO
            $uploadDir = BASE_PATH . '/uploads/avatars/';

            // Chama o helper para processar e salvar (passando $userId para criar subpasta)
            $newAvatarFileName = ImageHelper::processAndSave($_FILES['avatar'], $uploadDir, $userId);

            if (!$newAvatarFileName) {
                // Se o upload falhar, redireciona com erro 
                header('Location: ' . BASE_URL . '/dashboard/perfil?error=avatar_upload');
                exit;
            }
        }


        $updateData = [
            'name' => htmlspecialchars(trim($_POST['name'] ?? ''))
            // A data de nascimento é desabilitada no form, então não precisamos enviá-la
        ];

        if ($newAvatarFileName !== null) {
            $updateData['avatar_path'] = $newAvatarFileName;

            // Remove a foto antiga para não acumular lixo no servidor
            $oldUser = User::findById($userId);
            if ($oldUser && !empty($oldUser['avatar_path'])) {
                $oldAvatarFullPath = BASE_PATH . '/uploads/avatars/' . $userId . '/' . $oldUser['avatar_path'];
                if (file_exists($oldAvatarFullPath)) {
                    unlink($oldAvatarFullPath);
                }
            }
        }

        if (User::update($userId, array_filter($updateData))) {

            if (isset($updateData['name'])) {
                $_SESSION['user_name'] = $updateData['name'];
            }

            if ($newAvatarFileName !== null) {
                $_SESSION['user_avatar'] = $newAvatarFileName;
            }

            header('Location: ' . BASE_URL . '/dashboard/perfil?status=updated');
        }
        exit;
    }

    public function showSecurityPage()
    {
        AuthHelper::check();
        ViewHelper::render('users/security', ['userName' => $_SESSION['user_name'] ?? 'Utilizador']);
    }

    public function updatePasswordFromProfile()
    {
        AuthHelper::check(); // Garante que está logado

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_SESSION['user_id'];
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';


            $user = User::findById($userId);
            if (!$user) {
                header('Location: ' . BASE_URL . '/dashboard/perfil/seguranca?error=update_failed');
                exit;
            }

            if (!password_verify($currentPassword, $user['password_hash'])) {
                header('Location: ' . BASE_URL . '/dashboard/perfil/seguranca?error=current_mismatch');
                exit;
            }

            if ($newPassword !== $confirmPassword) {
                header('Location: ' . BASE_URL . '/dashboard/perfil/seguranca?error=new_mismatch');
                exit;
            }

            if (strlen($newPassword) < 8) {
                header('Location: ' . BASE_URL . '/dashboard/perfil/seguranca?error=weak_password');
                exit;
            }


            // Se todas as validações passaram, hash a nova senha
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

            // Tenta atualizar no banco de dados
            if (User::update($userId, ['password_hash' => $newPasswordHash])) {
                // Sucesso! Redireciona com status=success
                header('Location: ' . BASE_URL . '/dashboard/perfil/seguranca?status=success');
            } else {
                // Falha ao salvar no banco
                header('Location: ' . BASE_URL . '/dashboard/perfil/seguranca?error=update_failed');
            }
            exit;
        }

        // Se não for POST, redireciona de volta
        header('Location: ' . BASE_URL . '/dashboard/perfil/seguranca');
        exit;
    }

    public function addCnpj()
    {
        AuthHelper::check();
        ViewHelper::render('users/add_cnpj', ['userName' => $_SESSION['user_name'] ?? 'Utilizador']);
    }

    public function storeCnpj()
    {
        AuthHelper::check();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cnpj = preg_replace('/[^0-9]/', '', $_POST['cnpj'] ?? '');
            $userId = $_SESSION['user_id'];

            if (!$this->validateCnpj($cnpj)) {
                // Se o CNPJ for inválido, redireciona de volta com um erro para o popup.
                header('Location: ' . BASE_URL . '/dashboard?error=cnpj_invalid');
                exit;
            }
            if (User::isCnpjInUse($cnpj, $userId)) {
                // Se o CNPJ já estiver em uso, redireciona com outro erro.
                header('Location: ' . BASE_URL . '/dashboard?error=cnpj_in_use');
                exit;
            }
            if (User::update($userId, ['cnpj' => $cnpj])) {
                header('Location: ' . BASE_URL . '/dashboard?status=cnpj_success');
                exit;
            }
        }
        // Se ocorrer um erro geral, redireciona.
        header('Location: ' . BASE_URL . '/dashboard?error=generic');
        exit;
    }


    // --- MÉTODOS DA ÁREA DO ADMINISTRADOR ---

    public function index()
    {
        AuthHelper::checkAdmin();
        $data = [
            'userName' => $_SESSION['user_name'] ?? 'Admin',
            'users' => User::getAll()
        ];
        ViewHelper::render('admin/index', $data);
    }

    public function create()
    {
        AuthHelper::checkAdmin();
        ViewHelper::render('admin/create', ['userName' => $_SESSION['user_name'] ?? 'Admin']);
    }

    public function store()
    {
        AuthHelper::checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $randomPassword = bin2hex(random_bytes(4));
            $data = [
                'name'      => trim($_POST['name']),
                'email'     => trim($_POST['email']),
                'birthdate' => $_POST['birthdate'],
                'role'      => in_array($_POST['role'], ['user', 'admin']) ? $_POST['role'] : 'user',
                'password_hash' => password_hash($randomPassword, PASSWORD_DEFAULT)
            ];
            if (User::create($data)) {
                $_SESSION['flash_message'] = ['type' => 'success_with_password', 'password' => $randomPassword];
                header('Location: ' . BASE_URL . '/admin/usuarios');
                exit;
            } else {
                header('Location: ' . BASE_URL . '/admin/usuarios/criar?status=error_create');
                exit;
            }
        }
        header('Location: ' . BASE_URL . '/admin/usuarios/criar?status=error');
        exit;
    }

    public function edit(int $id)
    {
        AuthHelper::checkAdmin();
        $userData = User::findById($id);

        if (!$userData) {
            header("Location: " . BASE_URL . "/admin/usuarios?status=not_found");
            exit;
        }
        $data = ['userName' => $_SESSION['user_name'] ?? 'Admin', 'userData' => $userData];
        ViewHelper::render('admin/edit', $data);
    }

    public function update()
    {
        AuthHelper::checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $data = [
                'name'      => trim($_POST['name']),
                'email'     => trim($_POST['email']),
                'birthdate' => $_POST['birthdate'],
                'role'      => $_POST['role']
            ];
            if (User::update($id, $data)) {
                header('Location: ' . BASE_URL . '/admin/usuarios?status=updated');
                exit;
            }
        }
        header('Location: ' . BASE_URL . '/admin/usuarios?status=error');
        exit;
    }

    public function delete(int $id)
    {
        AuthHelper::checkAdmin();
        if (User::delete($id)) {
            header("Location: " . BASE_URL . "/admin/usuarios?status=deleted");
        } else {
            header("Location: " . BASE_URL . "/admin/usuarios?status=error");
        }
        exit;
    }

    // --- MÉTODOS PRIVADOS DE AJUDA ---

    private function validateCnpj(string $cnpj): bool
    {
        $cnpj = preg_replace('/[^0-9]/is', '', $cnpj);
        if (strlen($cnpj) != 14) {
            return false;
        }

        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }

        $weights = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        for ($t = 12; $t < 14; $t++) {

            $sum = 0;
            $weight_start_index = 14 - ($t + 1);

            for ($c = 0; $c < $t; $c++) {
                $sum += (int)$cnpj[$c] * $weights[$weight_start_index + $c];
            }
            $remainder = $sum % 11;
            $dv = ($remainder < 2) ? 0 : 11 - $remainder;
            if ((int)$cnpj[$c] !== $dv) {
                return false; // Dígito verificador inválido
            }
        }
        return true;
    }
}
