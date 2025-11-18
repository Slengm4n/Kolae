<?php

namespace App\Controllers;

use App\Core\AuthHelper;
use App\Core\ViewHelper;
use App\Models\Sport;

class SportController
{
    /*** Exibe a lista de todos os esportes.*/
    public function index()
    {
        AuthHelper::checkAdmin();

        $data = [
            'userName' => $_SESSION['user_name'] ?? 'Admin',
            'sports' => Sport::getAll()
        ];

        ViewHelper::render('sports/index', $data);
    }

    /*** Exibe o formulário para criar um novo esporte.*/
    public function create()
    {
        AuthHelper::checkAdmin();

        $data = ['userName' => $_SESSION['user_name'] ?? 'Admin'];
        ViewHelper::render('sports/create', $data);
    }

    /*** Salva um novo esporte no banco de dados.*/
    public function store()
    {
        AuthHelper::checkAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'icon' => trim($_POST['icon'] ?? 'fa-question-circle')
            ];

            if (!empty($data['name'])) {
                if (Sport::create($data)) {
                    header('Location: ' . BASE_URL . '/admin/esportes?status=created_success');
                    exit;
                }
            }
        }
        header('Location: ' . BASE_URL . '/admin/esportes/criar?status=error');
        exit;
    }

    /**
     * Exibe o formulário para editar um esporte.
     * @param int $id O ID do esporte.
     */
    public function edit(int $id)
    {
        AuthHelper::checkAdmin();
        $sport = Sport::findById($id);

        if (!$sport) {
            header("Location: " . BASE_URL . "/admin/esportes?status=not_found");
            exit;
        }

        $data = [
            'userName' => $_SESSION['user_name'] ?? 'Admin',
            'sport' => $sport
        ];

        ViewHelper::render('sports/edit', $data);
    }

    /*** Atualiza um esporte existente no banco de dados.*/
    public function update()
    {
        AuthHelper::checkAdmin();

        if ($_SERVER["REQUEST_METHOD"] === 'POST' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'icon' => trim($_POST['icon'] ?? 'fa-question-circle')
            ];

            if (!empty($data['name'])) {
                if (Sport::update($id, $data)) {
                    header('Location: ' . BASE_URL . '/admin/esportes?status=updated_success');
                    exit;
                }
            }
        }

        $redirectId = isset($_POST['id']) ? '/' . $_POST['id'] : '';
        header('Location: ' . BASE_URL . '/admin/esportes/editar' . $redirectId . "?status=error");
        exit;
    }

    /**
     * Deleta um esporte.
     * @param int $id O ID do esporte.
     */
    public function delete(int $id)
    {
        AuthHelper::checkAdmin();

        if (Sport::delete($id)) {
            header("Location: " . BASE_URL . "/admin/esportes?status=deleted_success");
        } else {
            header("Location: " . BASE_URL . "/admin/esportes?status=error");
        }
        exit;
    }
}
