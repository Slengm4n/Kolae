<?php

namespace App\Controllers;

use App\Core\AdDisplay;
use App\Core\ApiFormat;
use App\Core\BaseApiController;
use App\Core\VenueAds;
use App\Core\VenueSchedule;
use App\Core\VenueSocials;
use App\Core\VenueStats;
use App\Models\Game;
use App\Models\Venue;
use App\Models\VenueHour;
use App\Models\VenueImage;
use App\Models\VenueSport;
use DateTimeImmutable;

/**
 * Quadras para o app (/api/v1/venues). Todos os endpoints são públicos.
 * Contrato em docs/api.md (S3); regra do status em docs/modelo-status.md.
 */
class VenueApiController extends BaseApiController
{
    private const STATUSES = ['available', 'in_use', 'closed'];
    private const SORTS = ['name', 'price', 'distance'];
    private const PER_PAGE_DEFAULT = 20;
    private const PER_PAGE_MAX = 100;
    private const RADIUS_MIN_KM = 1;
    private const RADIUS_MAX_KM = 50;
    private const Q_MAX_LENGTH = 100;

    /**
     * GET /api/v1/venues
     * Lista paginada com filtros, status calculado e distância.
     */
    public function index()
    {
        $f = $this->parseListFilters($_GET);
        $venues = self::listVenues($f);

        $total = count($venues);
        $perPage = $f['per_page'];
        $page = array_slice($venues, ($f['page'] - 1) * $perPage, $perPage);

        $this->sendSuccess($page, 200, [
            'current_page' => $f['page'],
            'last_page' => max(1, (int)ceil($total / $perPage)),
            'per_page' => $perPage,
            'total' => $total
        ]);
    }

    /**
     * Quadras filtradas, com status e distância, já ordenadas e sem paginar.
     * Usado pela API e pela página Quadras do site (D51), para os dois mostrarem o mesmo.
     * @param array $f filtros já validados (formato de parseListFilters; 'sort' padrão 'name')
     * @return array quadras no formato do resumo da API
     */
    public static function listVenues(array $f): array
    {
        $f += ['sort' => 'name'];
        $rows = Venue::searchForApi($f);
        $ids = array_map(fn($r) => (int)$r['id'], $rows);
        $now = VenueSchedule::now();
        $hours = VenueHour::findByVenueIds($ids);
        $matches = Game::findScheduledOn($ids, $now->format('Y-m-d'));
        $sports = VenueSport::findByVenueIds($ids);
        $ads = AdDisplay::visibleFor($ids); // anúncio no ar de cada quadra (D63)
        $hasPosition = isset($f['lat']);

        $venues = [];
        foreach ($rows as $row) {
            $id = (int)$row['id'];
            $status = VenueSchedule::status($row['status'], $hours[$id] ?? [], $matches[$id] ?? [], $now);
            if (isset($f['status']) && $status['status'] !== $f['status']) {
                continue;
            }

            $distance = null;
            if ($hasPosition && $row['latitude'] !== null && $row['longitude'] !== null) {
                $distance = self::distanceKm($f['lat'], $f['lng'], (float)$row['latitude'], (float)$row['longitude']);
            }
            if (isset($f['radius_km']) && ($distance === null || $distance > $f['radius_km'])) {
                continue;
            }

            $venues[] = self::summary($row, $status, $sports[$id] ?? [], $distance)
                + ['ad' => AdDisplay::forApi($ads[$id] ?? null, $row['image_path'])];
        }

        // As linhas já vêm em ordem alfabética; o usort do PHP 8 é estável, então empates mantêm o nome
        if ($f['sort'] === 'price') {
            usort($venues, fn($a, $b) => self::nullsLast($a['price_per_hour_cents'], $b['price_per_hour_cents']));
        } elseif ($f['sort'] === 'distance') {
            usort($venues, fn($a, $b) => self::nullsLast($a['distance_km'], $b['distance_km']));
        }
        return $venues;
    }

    /**
     * GET /api/v1/venues/{id}
     */
    public function show($id)
    {
        $row = ctype_digit((string)$id) ? Venue::findForApi((int)$id) : false;
        if (!$row) {
            $this->sendError('Quadra não encontrada.', 'NOT_FOUND', 404);
        }

        $id = (int)$row['id'];
        $now = VenueSchedule::now();
        $hours = VenueHour::findByVenueIds([$id])[$id] ?? [];
        $matches = Game::findScheduledOn([$id], $now->format('Y-m-d'))[$id] ?? [];
        $status = VenueSchedule::status($row['status'], $hours, $matches, $now);
        $sports = VenueSport::findByVenueIds([$id])[$id] ?? [];

        $venue = self::summary($row, $status, $sports, null);
        unset($venue['distance_km']); // no detalhe, o app calcula (D33)

        // Fotos na ordem escolhida pelo dono (aba Fotos, D46): a primeira é a capa, igual ao cover_image_url
        $images = VenueImage::findByVenueId($id);

        $openingHours = [];
        foreach ($hours as $weekday => $h) {
            $openingHours[] = [
                'weekday' => $weekday,
                'opens_at' => substr($h['opens_at'], 0, 5),
                'closes_at' => substr($h['closes_at'], 0, 5)
            ];
        }

        $this->recordView($row);
        $this->sendSuccess($venue + [
            'description' => self::nullIfBlank($row['description']),
            'leisure_area_capacity' => self::intOrNull($row['leisure_area_capacity']),
            'images' => array_values(array_filter(array_map(fn($img) => self::imageUrl($id, $img['file_path']), $images))),
            'address' => [
                'street' => $row['street'],
                'number' => self::nullIfBlank($row['number']),
                'complement' => self::nullIfBlank($row['complement']),
                'neighborhood' => $row['neighborhood'],
                'city' => $row['city'],
                'state' => $row['state'],
                'cep' => self::nullIfBlank($row['cep']),
                'full' => self::fullAddress($row)
            ],
            'opening_hours' => $openingHours,
            'owner' => [
                'name' => $row['owner_name'],
                'avatar_url' => ApiFormat::uploadUrl('avatars/' . $row['user_id'], $row['owner_avatar_path']),
                'member_since' => ApiFormat::datetime($row['owner_created_at']),
                'whatsapp' => self::nullIfBlank($row['owner_whatsapp'])
            ],
            // Redes sociais (D60): sempre as quatro chaves, null no que o dono não informou
            'socials' => VenueSocials::forApi($row),
            // Anúncio no ar (D63) ou null; a capa é a primeira foto, igual ao cover_image_url
            'ad' => AdDisplay::forApi(AdDisplay::visibleAd($id), $images[0]['file_path'] ?? null),
        ]);
    }

    /**
     * GET /api/v1/ads
     * Até 5 anúncios no ar para o carrossel "Destaques" do app (D63), sorteados por dia (a mesma
     * ordem o dia todo, igual ao site). Cada um traz a quadra no formato da lista, sem o `ad`.
     * Não conta a aparição: o app envia ad_view e ad_click pelo POST /venues/{id}/events.
     */
    public function ads()
    {
        $today = VenueSchedule::now()->setTime(0, 0);
        $items = [];
        foreach (self::listVenues([]) as $venue) {
            if ($venue['ad'] === null) {
                continue;
            }
            $ad = $venue['ad'];
            unset($venue['ad'], $venue['distance_km']);
            $items[] = $ad + ['venue' => $venue];
        }
        $this->sendSuccess(VenueAds::dailyPick($items, VenueAds::API_LIMIT, $today));
    }

    /**
     * Abrir o detalhe no app conta como visualização (D62). Chamado antes de responder;
     * o próprio VenueStats ignora o dono, o admin e a repetição em menos de 30 minutos.
     */
    private function recordView(array $row): void
    {
        VenueStats::record($row, 'view', 'app', $this->optionalUser());
    }

    /**
     * POST /api/v1/venues/{id}/events
     * Registra um clique para as estatísticas do dono (D62). Login opcional. Responde 204.
     * Usado pelo app ("source": "app") e pela página pública da quadra ("source": "site").
     */
    public function event($id)
    {
        $row = ctype_digit((string)$id) ? Venue::findForApi((int)$id) : false;
        if (!$row) {
            $this->sendError('Quadra não encontrada.', 'NOT_FOUND', 404);
        }
        $input = $this->getJsonInput();
        $type = is_string($input['type'] ?? null) ? $input['type'] : '';
        $source = is_string($input['source'] ?? null) ? $input['source'] : 'app';
        $errors = [];
        if (!in_array($type, VenueStats::CLICK_TYPES, true)) {
            $errors['type'] = ['Tipo inválido. Use: ' . implode(', ', VenueStats::CLICK_TYPES) . '.'];
        }
        if (!in_array($source, VenueStats::SOURCES, true)) {
            $errors['source'] = ['Origem inválida. Use: ' . implode(', ', VenueStats::SOURCES) . '.'];
        }
        $this->failIfInvalid($errors);
        if (VenueStats::overLimit((int)$row['id'])) {
            header('Retry-After: 3600');
            $this->sendError('Muitos registros deste aparelho. Tente mais tarde.', 'TOO_MANY_ATTEMPTS', 429);
        }
        // Aparição e clique de anúncio só contam com anúncio no ar (D63); a resposta é a mesma
        if (!str_starts_with($type, 'ad_') || AdDisplay::visibleAd((int)$row['id'])) {
            VenueStats::record($row, $type, $source, $this->optionalUser());
        }
        http_response_code(204);
        exit;
    }

    /**
     * GET /api/v1/venues/{id}/schedule?date=YYYY-MM-DD
     */
    public function schedule($id)
    {
        $row = ctype_digit((string)$id) ? Venue::findForApi((int)$id) : false;
        if (!$row) {
            $this->sendError('Quadra não encontrada.', 'NOT_FOUND', 404);
        }

        $day = VenueSchedule::now()->setTime(0, 0);
        if (isset($_GET['date']) && $_GET['date'] !== '') {
            $day = self::parseDate((string)$_GET['date']);
            if ($day === null) {
                $this->failIfInvalid(['date' => 'Use uma data válida no formato AAAA-MM-DD.']);
            }
        }

        $id = (int)$row['id'];
        $date = $day->format('Y-m-d');
        $hours = VenueHour::findByVenueIds([$id])[$id] ?? [];
        $matches = Game::findScheduledOn([$id], $date)[$id] ?? [];

        $this->sendSuccess(['date' => $date] + VenueSchedule::daySlots($row['status'], $hours, $matches, $day));
    }

    // --- Ajudantes ---

    /**
     * Valida a query string da lista. Responde 422 com o erro de cada parâmetro inválido.
     */
    private function parseListFilters(array $query): array
    {
        $f = [];
        $errors = [];
        $get = fn($key) => isset($query[$key]) && is_scalar($query[$key]) && trim((string)$query[$key]) !== ''
            ? trim((string)$query[$key])
            : null;

        if (($q = $get('q')) !== null) {
            if (mb_strlen($q) > self::Q_MAX_LENGTH) {
                $errors['q'] = 'A busca pode ter no máximo ' . self::Q_MAX_LENGTH . ' caracteres.';
            } else {
                $f['q'] = $q;
            }
        }

        foreach (['sport_id' => 1, 'max_price_cents' => 0] as $key => $min) {
            if (($v = $get($key)) !== null) {
                if (!ctype_digit($v) || (int)$v < $min) {
                    $errors[$key] = $min ? 'Use um número inteiro positivo.' : 'Use um número inteiro (0 ou mais).';
                } else {
                    $f[$key] = (int)$v;
                }
            }
        }

        if (($v = $get('status')) !== null) {
            if (in_array($v, self::STATUSES, true)) {
                $f['status'] = $v;
            } else {
                $errors['status'] = 'Use available, in_use ou closed.';
            }
        }

        foreach (['is_covered', 'has_lighting', 'has_leisure_area'] as $key) {
            if (($v = $get($key)) !== null) {
                $bool = ['true' => true, '1' => true, 'false' => false, '0' => false][strtolower($v)] ?? null;
                if ($bool === null) {
                    $errors[$key] = 'Use true ou false.';
                } else {
                    $f[$key] = $bool;
                }
            }
        }

        if (($v = $get('floor_type')) !== null) {
            if (array_key_exists($v, Venue::FLOOR_TYPES)) {
                $f['floor_type'] = $v;
            } else {
                $errors['floor_type'] = 'Use ' . implode(', ', array_keys(Venue::FLOOR_TYPES)) . '.';
            }
        }

        // Posição: lat e lng sempre juntos
        $lat = $get('lat');
        $lng = $get('lng');
        $hasPosition = $lat !== null || $lng !== null;
        if ($hasPosition) {
            if ($lat === null || !is_numeric($lat) || (float)$lat < -90 || (float)$lat > 90) {
                $errors['lat'] = $lat === null ? 'Envie lat junto com lng.' : 'Use uma latitude entre -90 e 90.';
            }
            if ($lng === null || !is_numeric($lng) || (float)$lng < -180 || (float)$lng > 180) {
                $errors['lng'] = $lng === null ? 'Envie lng junto com lat.' : 'Use uma longitude entre -180 e 180.';
            }
            if (!isset($errors['lat']) && !isset($errors['lng'])) {
                $f['lat'] = (float)$lat;
                $f['lng'] = (float)$lng;
            }
        }

        if (($v = $get('radius_km')) !== null) {
            if (!is_numeric($v) || (float)$v < self::RADIUS_MIN_KM || (float)$v > self::RADIUS_MAX_KM) {
                $errors['radius_km'] = 'Use um raio entre ' . self::RADIUS_MIN_KM . ' e ' . self::RADIUS_MAX_KM . ' km.';
            } elseif (!$hasPosition) {
                $errors['radius_km'] = 'O raio exige lat e lng.';
            } else {
                $f['radius_km'] = (float)$v;
            }
        }

        $f['sort'] = $get('sort') ?? 'name';
        if (!in_array($f['sort'], self::SORTS, true)) {
            $errors['sort'] = 'Use name, price ou distance.';
        } elseif ($f['sort'] === 'distance' && !$hasPosition) {
            $errors['sort'] = 'Ordenar por distância exige lat e lng.';
        }

        $f['page'] = 1;
        if (($v = $get('page')) !== null) {
            if (ctype_digit($v) && (int)$v >= 1) {
                $f['page'] = (int)$v;
            } else {
                $errors['page'] = 'Use uma página a partir de 1.';
            }
        }

        $f['per_page'] = self::PER_PAGE_DEFAULT;
        if (($v = $get('per_page')) !== null) {
            if (ctype_digit($v) && (int)$v >= 1 && (int)$v <= self::PER_PAGE_MAX) {
                $f['per_page'] = (int)$v;
            } else {
                $errors['per_page'] = 'Use um valor de 1 a ' . self::PER_PAGE_MAX . '.';
            }
        }

        $this->failIfInvalid($errors);
        return $f;
    }

    /**
     * Objeto "venue" resumido do contrato.
     */
    private static function summary(array $row, array $status, array $sports, ?float $distance): array
    {
        $id = (int)$row['id'];
        return [
            'id' => $id,
            'name' => $row['name'],
            'status' => $status['status'],
            'status_until' => $status['status_until'] ? $status['status_until']->format(DATE_ATOM) : null,
            // 0 = grátis; NULL = não informado (D31)
            'price_per_hour_cents' => $row['average_price_per_hour'] !== null
                ? (int)round((float)$row['average_price_per_hour'] * 100)
                : null,
            'floor_type' => $row['floor_type'] ?: null,
            'is_covered' => (bool)$row['is_covered'],
            'has_lighting' => (bool)$row['has_lighting'],
            'has_leisure_area' => (bool)$row['has_leisure_area'],
            'court_capacity' => self::intOrNull($row['court_capacity']),
            'cover_image_url' => self::imageUrl($id, $row['image_path']),
            'sports' => $sports,
            'neighborhood' => $row['neighborhood'],
            'city' => $row['city'],
            'latitude' => $row['latitude'] !== null ? (float)$row['latitude'] : null,
            'longitude' => $row['longitude'] !== null ? (float)$row['longitude'] : null,
            'distance_km' => $distance
        ];
    }

    /**
     * Distância em km entre dois pontos (fórmula de Haversine), com uma casa decimal.
     */
    private static function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return round(6371 * 2 * atan2(sqrt($a), sqrt(1 - $a)), 1);
    }

    /**
     * Comparação para usort com os nulos no fim.
     */
    private static function nullsLast($a, $b): int
    {
        if ($a === null || $b === null) {
            return ($a === null) <=> ($b === null);
        }
        return $a <=> $b;
    }

    /**
     * "2026-11-03" → data no horário de Brasília, ou null se inválida.
     */
    private static function parseDate(string $value): ?DateTimeImmutable
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) || !checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
            return null;
        }
        return new DateTimeImmutable($value . ' 00:00:00', VenueSchedule::timezone());
    }

    /**
     * "Rua Exemplo, 100 - Centro, Ferraz de Vasconcelos - SP", pulando as partes que faltam.
     */
    private static function fullAddress(array $row): ?string
    {
        $street = implode(', ', array_filter([$row['street'], self::nullIfBlank($row['number'])]));
        $city = implode(' - ', array_filter([$row['city'], $row['state']]));
        $full = implode(' - ', array_filter([$street, implode(', ', array_filter([$row['neighborhood'], $city]))]));
        return $full !== '' ? $full : null;
    }

    /**
     * Foto da quadra, ou null se o arquivo não existe no servidor.
     */
    private static function imageUrl(int $venueId, ?string $fileName): ?string
    {
        return ApiFormat::uploadUrl('venues/' . $venueId, $fileName);
    }

    private static function intOrNull($value): ?int
    {
        return $value === null || $value === '' ? null : (int)$value;
    }

    private static function nullIfBlank($value): ?string
    {
        return $value === null || trim((string)$value) === '' ? null : (string)$value;
    }
}
