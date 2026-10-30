<?php

namespace App\Controllers;

use App\Core\AuthHelper;
use App\Core\ImageHelper;
use App\Core\VenueAccess;
use App\Core\ViewHelper;
use App\Models\Venue;
use App\Models\User;
use App\Models\Address;
use App\Models\VenueImage;

class VenueController
{
    //Exibe a lista (tabela) de TODAS as quadras para o admin
    public function index()
    {


        AuthHelper::checkAdmin();

        $venues = Venue::getAllForAdmin();

        $data = [
            'userName' => $_SESSION['user_name'] ?? 'Usuário',
            'venues' => $venues
        ];

        ViewHelper::render('venues/index', $data);
    }

    /**
     * Exibe o formulário para criar uma nova quadra.
     * (Usado tanto pelo Usuário quanto pelo Admin)
     */
    public function create()
    {
        $this->checkCnpjStatus(); // Admin também precisa de CNPJ para criar
        $data = [
            'userName' => $_SESSION['user_name'] ?? 'Usuário',
            'routePrefix' => $this->getRoutePrefix(),
            'backUrl' => $this->getRedirectUrl()
        ];
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


            //Cria o endereço
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
                header('Location: ' . BASE_URL . $this->getRoutePrefix() . '/quadras/criar?status=address_error');
                exit;
            }

            //Cria a quadra
            $venueData = [
                'user_id' => $_SESSION['user_id'],
                'address_id' => $addressId,
                'name' => $_POST['name'],
                'average_price_per_hour' => $_POST['average_price_per_hour'],
                'court_capacity' => $_POST['court_capacity'],
                'has_leisure_area' => $_POST['has_leisure_area'] ?? 0,
                'leisure_area_capacity' => $_POST['leisure_area_capacity'] ?? null,
                'floor_type' => $this->sanitizeFloorType($_POST['floor_type'] ?? null),
                'has_lighting' => $_POST['has_lighting'] ?? 0,
                'is_covered' => $_POST['is_covered'] ?? 0
            ];
            $venueId = Venue::create($venueData);

            if (!$venueId) {
                // Tratar erro
                header('Location: ' . BASE_URL . $this->getRoutePrefix() . '/quadras/criar?status=venue_error');
                exit;
            }

            //Processa e salva as imagens
            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                $this->handleImageUploads($venueId);
            }

            $redirectUrl = $this->getRedirectUrl();
            header('Location: ' . BASE_URL . $redirectUrl . '?status=created');
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

        $isAdmin = AuthHelper::isAdmin(); //Verifica se é admin

        //Validação de segurança
        if (!$venue || ($venue['user_id'] != $_SESSION['user_id'] && !$isAdmin)) {
            header('Location: ' . BASE_URL . '/dashboard?error=not_found');
            exit;
        }

        // A view lista as fotos atuais (para exibir e marcar para exclusão)
        $venue['images'] = VenueImage::findByVenueId($id);

        $data = [
            'userName' => $_SESSION['user_name'] ?? 'Usuário',
            'venue' => $venue,
            'routePrefix' => $this->getRoutePrefix(), // Rotas do formulário (/admin ou /dashboard)
            'backUrl' => $this->getRedirectUrl(),     // Destino do "Cancelar" e do "Painel"
            'status' => VenueAccess::currentStatus($venue) // Selo no topo, igual ao do app (D40)
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

            $venue = Venue::findById($id);

            // Redireciona se a quadra não existe OU
            // se o usuário NÃO for o dono E também NÃO for admin
            if (!$venue || ($venue['user_id'] != $_SESSION['user_id'] && !AuthHelper::isAdmin())) {
                header('Location: ' . BASE_URL . '/dashboard?error=unauthorized');
                exit;
            }

            //Atualiza o endereço
            $addressData = [
                'cep' => $_POST['cep'],
                'street' => $_POST['street'],
                'number' => $_POST['number'],
                'neighborhood' => $_POST['neighborhood'],
                'complement' => $_POST['complement'] ?? null,
                'city' => $_POST['city'],
                'state' => $_POST['state']
            ];
            // Usa o endereço vinculado à quadra no banco, nunca o address_id vindo do POST
            Address::update((int)$venue['address_id'], $addressData);

            //Atualiza a quadra
            $venueData = [
                'name' => $_POST['name'],
                'average_price_per_hour' => $_POST['average_price_per_hour'],
                'court_capacity' => $_POST['court_capacity'],
                'has_leisure_area' => $_POST['has_leisure_area'] ?? 0,
                'leisure_area_capacity' => $_POST['leisure_area_capacity'] ?? null,
                'floor_type' => $this->sanitizeFloorType($_POST['floor_type'] ?? null),
                'has_lighting' => $_POST['has_lighting'] ?? 0,
                'is_covered' => $_POST['is_covered'] ?? 0,
                // Mantém o status atual se o formulário não enviar um válido (antes reativava quadras inativas)
                'status' => in_array($_POST['status'] ?? null, Venue::STATUSES, true) ? $_POST['status'] : $venue['status']
            ];
            Venue::update($id, $venueData);

            //Logica de Imagem 
            if (isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
                $uploadDir = BASE_PATH . "/uploads/venues/" . $id . "/";
                foreach ($_POST['delete_images'] as $imageId) {
                    $image = VenueImage::findById((int)$imageId);
                    // Só apaga imagens que pertencem a esta quadra
                    if ($image && (int)$image['venue_id'] === $id) {
                        $fileName = basename($image['file_path']);
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
            $redirectUrl = $this->getRedirectUrl();
            header('Location: ' . BASE_URL . $redirectUrl . '?status=updated');
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
        //Validação de segurança
        $venue = Venue::findById($id);

        //Redireciona se a quadra não existe OU
        //se o usuário NÃO for o dono E também NÃO for admin
        if (!$venue || ($venue['user_id'] != $_SESSION['user_id'] && !AuthHelper::isAdmin())) {
            header('Location: ' . BASE_URL . '/dashboard?error=unauthorized');
            exit;
        }

        $redirectUrl = $this->getRedirectUrl();

        if (Venue::delete($id)) {
            header("Location: " . BASE_URL . $redirectUrl . "?status=deleted");
        } else {
            header("Location: " . BASE_URL . $redirectUrl . "?status=error");
        }
        exit;
    }


    // --- MÉTODOS PRIVADOS DE AJUDA ---
    /**
     * Prefixo das rotas de quadra conforme o painel em que a pessoa está.
     * Decide pela rota atual (não pelo Referer, que o navegador pode omitir):
     * admin em /admin/... continua no painel de admin; o resto usa /dashboard.
     */
    private function getRoutePrefix(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';

        if (AuthHelper::isAdmin() && str_contains($path, '/admin/')) {
            return '/admin';
        }

        return '/dashboard';
    }

    /**
     * Página de volta do painel atual (lista de quadras do admin ou dashboard do usuário).
     */
    private function getRedirectUrl(): string
    {
        return $this->getRoutePrefix() === '/admin' ? '/admin/quadras' : '/dashboard';
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
     * Aceita só as chaves de piso conhecidas (Venue::FLOOR_TYPES); qualquer outra vira null.
     */
    private function sanitizeFloorType(?string $floorType): ?string
    {
        return array_key_exists((string)$floorType, Venue::FLOOR_TYPES) ? $floorType : null;
    }

    /**
     * Processa o upload de múltiplas imagens para uma quadra.
     */
    private function handleImageUploads(int $venueId)
    {
        $uploadDir = BASE_PATH . "/uploads/venues/" . $venueId . "/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (!is_array($_FILES['images']['name'])) {
            return;
        }

        $imageCount = count($_FILES['images']['name']);
        for ($i = 0; $i < $imageCount; $i++) {
            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['images']['tmp_name'][$i];

                // Valida pelo conteúdo real e gera a extensão no servidor (ignora o nome original)
                $fileExtension = ImageHelper::validateUpload($tmpName, $_FILES['images']['size'][$i]);
                if ($fileExtension === false) {
                    continue;
                }

                $newFileName = uniqid('venue_', true) . '.' . $fileExtension;
                $destinationPath = $uploadDir . $newFileName;

                if (move_uploaded_file($tmpName, $destinationPath)) {
                    VenueImage::create($venueId, $newFileName);
                }
            }
        }
    }
}
