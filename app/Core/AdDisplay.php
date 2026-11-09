<?php

namespace App\Core;

use App\Models\VenueAd;
use DateTimeImmutable;

/**
 * Anúncios "Patrocinado" para o jogador no site (D63, parte C): no topo da home e da /quadras
 * (até 3, sorteados por dia) e na página pública da quadra (o anúncio dela).
 *
 * Cada anúncio mostrado conta uma vez que apareceu (ad_view); o clique (ad_click) é contado
 * quando a página da quadra abre com ?ref=ad (PublicVenueController::show). Os filtros são os
 * das estatísticas (D62): robô, dono, admin e repetição não contam.
 */
class AdDisplay
{
    /** Valor de ?ref= no link do card; a página da quadra conta o clique por ele. */
    public const REF = 'ad';

    /**
     * Cards do dia para a home e a /quadras, já contando a visualização.
     * @return array lista de $adCard para o partials/ad_card.php
     */
    public static function cards(int $limit = VenueAds::SITE_LIMIT, ?DateTimeImmutable $today = null): array
    {
        $today = ($today ?? VenueSchedule::now())->setTime(0, 0);
        try {
            $ads = VenueAds::dailyPick(array_values(VenueAd::visibleByVenue($today)), $limit, $today);
            $venues = VenueAd::venuesForCards(array_column($ads, 'venue_id'));
        } catch (\Throwable $e) {
            // Anúncio nunca derruba a página
            error_log('Anúncios: não montou os cards: ' . $e->getMessage());
            return [];
        }

        $cards = [];
        foreach ($ads as $ad) {
            $venue = $venues[(int)$ad['venue_id']] ?? null;
            if (!$venue) {
                continue;
            }
            $cards[] = self::card($ad, $venue, BASE_URL . '/quadra/' . (int)$venue['id'] . '?ref=' . self::REF);
            VenueStats::record($venue, 'ad_view', 'site', self::viewer());
        }
        return $cards;
    }

    /**
     * Anúncio da própria quadra, para a faixa da página pública (sem link: o jogador já está
     * na quadra). Conta a visualização. null se a quadra não tem anúncio no ar.
     */
    public static function forVenue(array $venue, ?DateTimeImmutable $today = null): ?array
    {
        $ad = self::visibleAd((int)$venue['id'], $today);
        if (!$ad) {
            return null;
        }
        VenueStats::record($venue, 'ad_view', 'site', self::viewer());
        return self::card($ad, ['name' => $venue['name'], 'neighborhood' => $venue['neighborhood'] ?? null, 'city' => $venue['city'] ?? null, 'cover' => null], null);
    }

    /** O jogador chegou pelo card? Conta o clique, se a quadra ainda tem anúncio no ar. */
    public static function recordClick(array $venue, ?DateTimeImmutable $today = null): bool
    {
        if (!self::visibleAd((int)$venue['id'], $today)) {
            return false;
        }
        return VenueStats::record($venue, 'ad_click', 'site', self::viewer());
    }

    // --- Ajudantes ---

    private static function visibleAd(int $venueId, ?DateTimeImmutable $today): ?array
    {
        $today = ($today ?? VenueSchedule::now())->setTime(0, 0);
        try {
            return VenueAd::visibleByVenue($today, [$venueId])[$venueId] ?? null;
        } catch (\Throwable $e) {
            error_log('Anúncios: não leu o anúncio da quadra: ' . $e->getMessage());
            return null;
        }
    }

    private static function card(array $ad, array $venue, ?string $url): array
    {
        $image = VenueAd::imageUrl($ad, $venue['cover'] ?? null);
        return [
            'id' => (int)$ad['id'],
            'venue_id' => (int)$ad['venue_id'],
            'title' => $ad['title'],
            'body' => $ad['body'],
            'image' => ImageHelper::thumbUrl($image), // capa usa a miniatura (D57); a foto do anúncio já é pequena
            'ends_on' => $ad['ends_on'],
            'venue' => $venue['name'],
            'place' => implode(', ', array_filter([$venue['neighborhood'] ?? null, $venue['city'] ?? null])),
            'url' => $url,
        ];
    }

    /** Quem está vendo, para o dono e o admin não contarem. */
    private static function viewer(): ?array
    {
        return isset($_SESSION['user_id']) ? ['id' => $_SESSION['user_id'], 'role' => $_SESSION['user_role'] ?? ''] : null;
    }
}
