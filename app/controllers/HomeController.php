<?php

namespace App\Controllers;

use App\Core\ViewHelper;
use App\Core\AuthHelper;

class HomeController
{
    public function index()
    {
        // 1. Tenta restaurar a sessão silenciosamente
        AuthHelper::checkRememberMe();

        // 2. Carrega a view (o header da view vai se adaptar sozinho se a sessão existir)
        ViewHelper::render('home/index');
    }
}
