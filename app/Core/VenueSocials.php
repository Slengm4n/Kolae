<?php

namespace App\Core;

/**
 * Class VenueSocials
 * Redes sociais da quadra (D60): Instagram, TikTok, Facebook e site, nas colunas de venues.
 * Aceita o que o dono colar no painel e guarda no formato do contrato da API (docs/api.md):
 * Instagram e TikTok só o usuário, sem @; Facebook e site com https://.
 */
class VenueSocials
{
    /** Colunas em venues, na ordem em que aparecem no painel e na API. */
    public const FIELDS = ['instagram', 'tiktok', 'facebook_url', 'website_url'];

    /** Tamanho máximo de cada coluna (patch 008). */
    public const HANDLE_MAX = 30;
    public const URL_MAX = 255;

    /** Caminhos do Instagram que não são perfil (link de post, reel, story...). */
    private const INSTAGRAM_RESERVED = ['p', 'reel', 'reels', 'stories', 'explore', 'tv', 'accounts', 'direct'];

    /**
     * Valida e normaliza os quatro campos do formulário.
     * @return array{values: array<string, ?string>, error: ?string} error = o primeiro campo inválido
     */
    public static function fromInput(array $input): array
    {
        $values = [
            'instagram' => self::normalizeInstagram((string)($input['instagram'] ?? '')),
            'tiktok' => self::normalizeTiktok((string)($input['tiktok'] ?? '')),
            'facebook_url' => self::normalizeFacebookUrl((string)($input['facebook_url'] ?? '')),
            'website_url' => self::normalizeWebsiteUrl((string)($input['website_url'] ?? '')),
        ];
        foreach ($values as $field => $value) {
            if ($value === false) {
                return ['values' => [], 'error' => $field];
            }
        }
        return ['values' => $values, 'error' => null];
    }

    /**
     * "@arena", "arena" ou "instagram.com/arena" → "arena".
     * @return string|null|false usuário, null (vazio) ou false se inválido
     */
    public static function normalizeInstagram(string $input)
    {
        $value = trim($input);
        if ($value === '') {
            return null;
        }
        if (self::looksLikeUrl($value)) {
            $segment = self::firstPathSegment($value, ['instagram.com', 'instagr.am']);
            if ($segment === null || in_array(strtolower($segment), self::INSTAGRAM_RESERVED, true)) {
                return false;
            }
            $value = $segment;
        }
        return self::validHandle(ltrim($value, '@'));
    }

    /**
     * "@arena", "arena" ou "tiktok.com/@arena" → "arena".
     * @return string|null|false usuário, null (vazio) ou false se inválido
     */
    public static function normalizeTiktok(string $input)
    {
        $value = trim($input);
        if ($value === '') {
            return null;
        }
        if (self::looksLikeUrl($value)) {
            // O perfil do TikTok é sempre tiktok.com/@usuario; link encurtado (vm.tiktok.com) não serve
            $segment = self::firstPathSegment($value, ['tiktok.com']);
            if ($segment === null || $segment[0] !== '@') {
                return false;
            }
            $value = $segment;
        }
        return self::validHandle(ltrim($value, '@'));
    }

    /**
     * Página do Facebook: https:// e de facebook.com ou fb.com, com o caminho da página.
     * @return string|null|false endereço, null (vazio) ou false se inválido
     */
    public static function normalizeFacebookUrl(string $input)
    {
        $url = self::normalizeUrl($input);
        if (!is_string($url)) {
            return $url;
        }
        $parts = parse_url($url);
        $host = strtolower($parts['host']);
        $isFacebook = in_array($host, ['facebook.com', 'fb.com'], true)
            || str_ends_with($host, '.facebook.com') || str_ends_with($host, '.fb.com');
        $hasPage = trim($parts['path'] ?? '', '/') !== '' || !empty($parts['query']);
        return $isFacebook && $hasPage ? $url : false;
    }

    /**
     * Site da quadra: qualquer endereço com https://.
     * @return string|null|false endereço, null (vazio) ou false se inválido
     */
    public static function normalizeWebsiteUrl(string $input)
    {
        return self::normalizeUrl($input);
    }

    /**
     * Objeto "socials" da API: sempre as quatro chaves, null no que não foi informado.
     */
    public static function forApi(array $venue): array
    {
        $socials = [];
        foreach (self::FIELDS as $field) {
            $value = $venue[$field] ?? null;
            $socials[$field] = ($value === null || $value === '') ? null : (string)$value;
        }
        return $socials;
    }

    /**
     * Links prontos para mostrar os ícones (página pública e prévia do painel).
     * @return array<int, array{network: string, url: string, icon: string}> só as redes informadas
     */
    public static function links(array $venue): array
    {
        $socials = self::forApi($venue);
        $links = [];
        if ($socials['instagram'] !== null) {
            $links[] = ['network' => 'instagram', 'url' => 'https://instagram.com/' . rawurlencode($socials['instagram']), 'icon' => 'fab fa-instagram'];
        }
        if ($socials['tiktok'] !== null) {
            $links[] = ['network' => 'tiktok', 'url' => 'https://www.tiktok.com/@' . rawurlencode($socials['tiktok']), 'icon' => 'fab fa-tiktok'];
        }
        if ($socials['facebook_url'] !== null) {
            $links[] = ['network' => 'facebook', 'url' => $socials['facebook_url'], 'icon' => 'fab fa-facebook'];
        }
        if ($socials['website_url'] !== null) {
            $links[] = ['network' => 'website', 'url' => $socials['website_url'], 'icon' => 'fas fa-globe'];
        }
        return $links;
    }

    // --- Ajudantes ---

    /** Letras, números, "." e "_", até 30 caracteres. */
    private static function validHandle(string $handle)
    {
        return preg_match('/^[A-Za-z0-9._]{1,' . self::HANDLE_MAX . '}$/', $handle) ? $handle : false;
    }

    /** Tem cara de link: começa com http(s):// ou tem uma "/" (ex: instagram.com/arena). */
    private static function looksLikeUrl(string $value): bool
    {
        return str_contains($value, '/') || preg_match('#^https?:#i', $value) === 1;
    }

    /**
     * Primeiro trecho do caminho de um link de uma das redes (com ou sem https://, www. ou m.).
     * Retorna null se o link for de outro site ou não tiver caminho.
     */
    private static function firstPathSegment(string $value, array $hosts): ?string
    {
        if (!preg_match('#^https?://#i', $value)) {
            $value = 'https://' . ltrim($value, '/');
        }
        $parts = parse_url($value);
        if (!$parts || empty($parts['host'])) {
            return null;
        }
        $host = preg_replace('/^(www\.|m\.)/', '', strtolower($parts['host']));
        if (!in_array($host, $hosts, true)) {
            return null;
        }
        $segments = array_values(array_filter(explode('/', $parts['path'] ?? ''), 'strlen'));
        return $segments[0] ?? null;
    }

    /**
     * Endereço com https://: completa quando falta e troca http:// por https://.
     * Recusa outro protocolo, espaço no meio, endereço sem domínio e mais de 255 caracteres.
     * @return string|null|false
     */
    private static function normalizeUrl(string $input)
    {
        $url = trim($input);
        if ($url === '') {
            return null;
        }
        if (preg_match('/\s/', $url)) {
            return false;
        }
        if (preg_match('#^https?://#i', $url)) {
            $url = 'https://' . preg_replace('#^https?://#i', '', $url);
        } elseif (str_contains($url, '://') || preg_match('#^[a-z][a-z0-9+.-]*:#i', $url)) {
            return false; // javascript:, ftp://, mailto: ...
        } else {
            $url = 'https://' . ltrim($url, '/');
        }
        if (strlen($url) > self::URL_MAX || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }
        $host = (string)parse_url($url, PHP_URL_HOST);
        if (!preg_match('/^[a-z0-9-]+(\.[a-z0-9-]+)+$/i', $host)) {
            return false;
        }
        return $url;
    }
}
