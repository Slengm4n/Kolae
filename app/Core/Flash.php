<?php

namespace App\Core;

/**
 * Avisos que aparecem como pop-up (toast, src/js/ui.js) depois de um redirecionamento.
 * Os controllers continuam mandando ?status=... ou ?error=... na URL; aqui cada código vira
 * [tipo, texto]. O mesmo código pode ter texto próprio por página ($context).
 * O partials/toasts.php entrega a lista ao JS, que mostra e limpa a URL (recarregar não repete).
 */
class Flash
{
    /** Códigos de cada página: contexto => ['status' | 'error' => [código => chave de tradução]] */
    private const MAP = [
        'dashboard' => [
            'status' => [
                'cnpj_success' => 'dashboard_cnpj_success',
                'deleted' => 'flash_venue_deleted',
                'email_verified' => 'verify_done',
                'verify_sent' => 'verify_sent',
            ],
            'error' => [
                'verify_wait' => 'verify_wait',
                'verify_failed' => 'verify_failed',
                'verify_expired' => 'verify_expired',
                'verify_invalid' => 'verify_invalid',
                'cnpj_required' => 'flash_cnpj_required',
                'cnpj_invalid' => 'flash_cnpj_invalid',
                'cnpj_in_use' => 'flash_cnpj_in_use',
                'not_found' => 'flash_venue_not_found',
                'unauthorized' => 'flash_unauthorized',
                'generic' => 'unknown_error',
            ],
        ],
        'plan' => [
            'status' => [
                'plan_requested' => 'plan_flash_requested',
                'plan_activated' => 'plan_flash_activated',
                'plan_deactivated' => 'plan_flash_deactivated',
            ],
            'error' => ['plan_invalid_date' => 'plan_flash_invalid_date'],
        ],
        'profile' => [
            'status' => ['updated' => 'profile_update_success'],
            'error' => [
                'avatar_upload' => 'profile_error_upload',
                'update_failed' => 'profile_error_update',
                'whatsapp_invalid' => 'profile_error_whatsapp',
            ],
        ],
        'security' => [
            'status' => ['success' => 'flash_password_changed'],
            'error' => [
                'current_mismatch' => 'security_wrong_password_error',
                'new_mismatch' => 'password_mismatch',
                'weak_password' => 'security_strength_password_error',
                'update_failed' => 'security_save_password_error',
            ],
        ],
    ];

    /**
     * Avisos da URL atual para a página $context.
     * @return array lista de ['type' => 'success'|'error', 'message' => texto]
     */
    public static function fromQuery(string $context, array $lang): array
    {
        $map = self::MAP[$context] ?? [];
        $out = [];
        if (isset($_GET['status'], $map['status'][$_GET['status']])) {
            $out[] = ['type' => 'success', 'message' => $lang[$map['status'][$_GET['status']]] ?? ''];
        }
        if (isset($_GET['error'])) {
            $key = $map['error'][$_GET['error']] ?? 'unknown_error';
            $out[] = ['type' => 'error', 'message' => $lang[$key] ?? ''];
        }
        return array_values(array_filter($out, fn($t) => $t['message'] !== ''));
    }
}
