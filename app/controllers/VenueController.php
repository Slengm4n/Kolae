<?php

namespace App\Controllers;

use App\Core\AuthHelper;
use App\Core\ViewHelper;
use App\Models\Venue;
use App\Models\User;
use App\Models\Address;
use App\Models\VenueImage;

class VenueController
{
    /**
     * Exibe a lista (tabela) de TODAS as quadras para o admin.
     */
    public function index()
    {
        // --- MODIFICAÇÃO 1 ---
        // Esta rota agora é só para admins
        AuthHelper::checkAdmin();

        // Busca TODAS as quadras (assumindo que Venue::getAllForAdmin() existe)
        $venues = Venue::getAllForAdmin();

        $data = [
            'userName' => $_SESSION['user_name'] ?? 'Usuário',
            'venues' => $venues
        ];

        // Renderiza a view da tabela de admin
        ViewHelper::render('venues/index', $data);
        // --- FIM DA MODIFICAÇÃO 1 ---
    }

    /**
     * Exibe o formulário para criar uma nova quadra.
     * (Usado tanto pelo Usuário quanto pelo Admin)
     */
    public function create()
    {
        $this->checkCnpjStatus(); // Admin também precisa de CNPJ para criar
        $routePrefix = AuthHelper::isAdmin() ? '/admin' : '/dashboard';
        $data = ['userName' => $_SESSION['user_name'] ?? 'Usuário', 'routePrefix' => $routePrefix];
        ViewHelper::render('venues/create', $data);
    }

    /**
     * Salva uma nova quadra.
     * (Usado tanto pelo Usuário quanto pelo Admin)
     */
    public function store()
    {
        $this->checkCnpjStatus();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // ... (O código de store() continua o mesmo) ...
            // (Ele corretamente associa a nova quadra ao ID do usuário logado,
            // seja ele um usuário normal ou um admin)

            // 1. Cria o endereço
            $addressData = [
                'cep' => $_POST['cep'],
                'street' => $_POST['street'],
                'number' => $_POST['number'],
                'neighborhood' => $_POST['neighborhood'],
                'complement' => $_POST['complement'] ?? null,
                'city' => $_POST['city'],
                'state' => $_POST['state']
            ];
            $addressId = Address::create($addressData);

            if (!$addressId) {
                // Tratar erro
                header('Location: ' . BASE_URL . '/dashboard/quadras/criar?status=address_error');
                exit;
            }

            // 2. Cria a quadra
            $venueData = [
                'user_id' => $_SESSION['user_id'],
                'address_id' => $addressId,
                'name' => $_POST['name'],
                'average_price_per_hour' => $_POST['average_price_per_hour'],
                'court_capacity' => $_POST['court_capacity'],
                'has_leisure_area' => $_POST['has_leisure_area'] ?? 0,
                'leisure_area_capacity' => $_POST['leisure_area_capacity'] ?? null,
                'floor_type' => $_POST['floor_type'],
                'has_lighting' => $_POST['has_lighting'] ?? 0,
                'is_covered' => $_POST['is_covered'] ?? 0
            ];
            $venueId = Venue::create($venueData);

            if (!$venueId) {
                // Tratar erro
                header('Location: ' . BASE_URL . '/dashboard/quadras/criar?status=venue_error');
                exit;
            }

            // 3. Processa e salva as imagens
            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                $this->handleImageUploads($venueId);
            }

            $redirectUrl = $this->getRedirectUrl();
            header('Location: ' . BASE_URL . $redirectUrl . '?status=venue_created');
            exit;
        }
    }

    /**
     * Exibe o formulário para editar uma quadra.
     * (Permite admin editar quadras de outros)
     * @param int $id
     */
    public function edit(int $id)
    {
        AuthHelper::check();
        $venue = Venue::findById($id);

        // --- INÍCIO DA MODIFICAÇÃO ---
        $isAdmin = AuthHelper::isAdmin(); // Verifica se é admin

        // Validação de segurança: (Esta parte você já tem)
        if (!$venue || ($venue['user_id'] != $_SESSION['user_id'] && !$isAdmin)) {
            header('Location: ' . BASE_URL . '/dashboard?error=not_found');
            exit;
        }

        // Define o prefixo da rota com base no 'role' do usuário
        $routePrefix = $isAdmin ? '/admin' : '/dashboard';
        // --- FIM DA MODIFICAÇÃO ---

        $data = [
            'userName' => $_SESSION['user_name'] ?? 'Usuário',
            'venue' => $venue,
            'routePrefix' => $routePrefix // <-- Passa o prefixo para a view
        ];
        ViewHelper::render('venues/edit', $data);
    }

    /**
     * Atualiza uma quadra e seu endereço.
     * (Permite admin atualizar quadras de outros)
     * @param int $id
     */
    public function update(int $id)
    {
        AuthHelper::check();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validação de segurança
            $venue = Venue::findById($id);

            // --- MODIFICAÇÃO 3 ---
            // Redireciona se a quadra não existe OU
            // se o usuário NÃO for o dono E também NÃO for admin
            if (!$venue || ($venue['user_id'] != $_SESSION['user_id'] && !AuthHelper::isAdmin())) {
                header('Location: ' . BASE_URL . '/dashboard?error=unauthorized');
                exit;
            }
            // --- FIM DA MODIFICAÇÃO 3 ---

            // 1. Atualiza o endereço
            $addressData = [
                'cep' => $_POST['cep'],
                'street' => $_POST['street'],
                'number' => $_POST['number'],
                'neighborhood' => $_POST['neighborhood'],
                'complement' => $_POST['complement'] ?? null,
                'city' => $_POST['city'],
                'state' => $_POST['state']
            ];
            Address::update((int)$_POST['address_id'], $addressData);

            // 2. Atualiza a quadra
            $venueData = [
                'name' => $_POST['name'],
                'average_price_per_hour' => $_POST['average_price_per_hour'],
                'court_capacity' => $_POST['court_capacity'],
                'has_leisure_area' => $_POST['has_leisure_area'] ?? 0,
                'leisure_area_capacity' => $_POST['leisure_area_capacity'] ?? null,
                'floor_type' => $_POST['floor_type'],
                'has_lighting' => $_POST['has_lighting'] ?? 0,
                'is_covered' => $_POST['is_covered'] ?? 0,
                'status' => $_POST['status'] ?? 'available'
            ];
            Venue::update($id, $venueData);

            // --- Lógica de Imagem (já estava correta) ---
            if (isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
                $uploadDir = BASE_PATH . "/uploads/venues/" . $id . "/";
                foreach ($_POST['delete_images'] as $imageId) {
                    $image = VenueImage::findById((int)$imageId);
                    if ($image) {
                        $fileName = $image['file_path'];
                        $filePath = $uploadDir . $fileName;
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                        VenueImage::delete((int)$imageId);
                    }
                }
            }
            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                $this->handleImageUploads($id);
            }
            // --- Fim da Lógica de Imagem ---
            // Pega o prefixo de rota que o formulário enviou 
            // Pega o prefixo de rota que o formulário enviou
            $redirectUrl = $this->getRedirectUrl();
            header('Location: ' . BASE_URL . $redirectUrl . '?status=venue_created');
            exit;
        }
    }

    /**
     * Deleta (soft delete) uma quadra.
     * (Permite admin deletar quadras de outros)
     * @param int $id
     */
    public function delete(int $id)
    {
        AuthHelper::check();
        // Validação de segurança
        $venue = Venue::findById($id);

        // --- MODIFICAÇÃO 4 ---
        // Redireciona se a quadra não existe OU
        // se o usuário NÃO for o dono E também NÃO for admin
        if (!$venue || ($venue['user_id'] != $_SESSION['user_id'] && !AuthHelper::isAdmin())) {
            header('Location: ' . BASE_URL . '/dashboard?error=unauthorized');
            exit;
        }
        // --- FIM DA MODIFICAÇÃO 4 ---

        $redirectUrl = $this->getRedirectUrl();
        header('Location: ' . BASE_URL . $redirectUrl . '?status=venue_created');

        if (Venue::delete($id)) {
            header("Location: " . BASE_URL . $redirectUrl . "?status=deleted");
        } else {
            header("Location: " . BASE_URL . $redirectUrl . "?status=error");
        }
        exit;
    }


    // --- MÉTODOS PRIVADOS DE AJUDA ---
    private function getRedirectUrl(): string
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';

        // 1. Tenta pelo Referer (de onde o usuário veio)
        if (str_contains($referer, '/admin/')) {
            return '/admin/quadras';
        }

        if (str_contains($referer, '/dashboard/')) {
            return '/dashboard';
        }

        // 2. Se o Referer falhar (estiver vazio), usa o "role" como fallback
        if (AuthHelper::isAdmin()) {
            return '/admin/quadras';
        } else {
            return '/dashboard';
        }
    }

    /**
     * Verifica se o usuário tem um CNPJ cadastrado.
     */
    private function checkCnpjStatus()
    {
        AuthHelper::check();

        // Admin não precisa de CNPJ para criar/editar quadras
        if (AuthHelper::isAdmin()) {
            return;
        }

        $user = User::findById($_SESSION['user_id']);
        if (empty($user['cnpj'])) {
            header('Location: ' . BASE_URL . '/dashboard?error=cnpj_required');
            exit;
        }
    }

    /**
     * Processa o upload de múltiplas imagens para uma quadra.
     */
    private function handleImageUploads(int $venueId)
    {
        $uploadDir = BASE_PATH . "/uploads/venues/" . $venueId . "/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $imageCount = count($_FILES['images']['name']);
        for ($i = 0; $i < $imageCount; $i++) {
            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['images']['tmp_name'][$i];
                $originalName = $_FILES['images']['name'][$i];
                $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                $newFileName = uniqid('venue_', true) . '.' . $fileExtension;
                $destinationPath = $uploadDir . $newFileName;

                if (move_uploaded_file($tmpName, $destinationPath)) {
                    VenueImage::create($venueId, $newFileName);
                }
            }
        }
    }
}
