<?php

namespace App\Controllers;

use App\Core\ViewHelper;
use App\Core\AuthHelper;
use App\Models\Venue;

class HomeController
{
    /** Quantas quadras aparecem em "Quadras no Kolae" na home. */
    private const FEATURED_VENUES = 3;

    /** Chave de piso (Venue::FLOOR_TYPES) → chave de tradução já existente em lang/*.php */
    private const FLOOR_LANG_KEYS = [
        'synthetic' => 'venue_floor_answer_1',
        'court'     => 'venue_floor_answer_2',
        'sand'      => 'venue_floor_answer_3',
        'clay'      => 'venue_floor_answer_4',
        'grass'     => 'venue_floor_answer_5',
        'wood'      => 'venue_floor_answer_6',
    ];

    public function index()
    {
        //Tenta restaurar a sessão silenciosamente
        AuthHelper::checkRememberMe();

        //Carrega a view (o header da view vai se adaptar sozinho se a sessão existir)
        ViewHelper::render('home/index', [
            'featuredVenues' => $this->featuredVenues()
        ]);
    }

    /**
     * Quadras reais para a vitrine da home: só as disponíveis, com foto primeiro.
     * A foto só é usada se o arquivo existir (no ambiente local, nem todas existem).
     */
    private function featuredVenues(): array
    {
        try {
            $venues = array_filter(Venue::getAll(), fn($v) => ($v['status'] ?? '') === 'available');
        } catch (\Throwable $e) {
            error_log('Home: erro ao buscar quadras: ' . $e->getMessage());
            return [];
        }

        $cards = array_map(function ($v) {
            $imagePath = !empty($v['image_path'])
                ? '/uploads/venues/' . (int)$v['id'] . '/' . basename($v['image_path'])
                : null;
            $hasImage = $imagePath && is_file(BASE_PATH . $imagePath);
            $price = (float)($v['average_price_per_hour'] ?? 0);

            return [
                'name' => $v['name'],
                'city' => $v['city'] ?? '',
                'neighborhood' => $v['neighborhood'] ?? '',
                'image_url' => $hasImage ? BASE_URL . $imagePath : null,
                'floor_lang_key' => self::FLOOR_LANG_KEYS[$v['floor_type'] ?? ''] ?? null,
                'price' => $price > 0 ? number_format($price, 2, ',', '.') : null,
                'is_covered' => !empty($v['is_covered']),
                'has_lighting' => !empty($v['has_lighting']),
            ];
        }, $venues);

        // Com foto primeiro; entre iguais, mantém a ordem do banco
        usort($cards, fn($a, $b) => (int)($b['image_url'] !== null) <=> (int)($a['image_url'] !== null));

        return array_slice($cards, 0, self::FEATURED_VENUES);
    }
}
