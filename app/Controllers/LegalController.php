<?php

namespace App\Controllers;

use App\Core\ViewHelper;

/**
 * Termos de Uso e Política de Privacidade: páginas públicas, abertas também pelo app (D42).
 * O texto fica em português (vale pela lei brasileira); título e navegação seguem o idioma.
 */
class LegalController
{
    /** Data da versão atual dos dois documentos (mostrada em "Última atualização"). */
    public const UPDATED_AT = '29/10/2026';

    /** E-mail de contato citado nos documentos. */
    public const CONTACT_EMAIL = 'contato@kolae.online';

    /**
     * GET /termos
     */
    public function terms()
    {
        ViewHelper::render('legal/page', ['doc' => 'terms']);
    }

    /**
     * GET /privacidade
     */
    public function privacy()
    {
        ViewHelper::render('legal/page', ['doc' => 'privacy']);
    }
}
