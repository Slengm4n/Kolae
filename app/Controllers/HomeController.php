<?php

namespace App\Controllers;

use App\Core\ViewHelper;
use App\Core\AuthHelper;

class HomeController
{
    public function index()
    {
        //Tenta restaurar a sessão silenciosamente
        AuthHelper::checkRememberMe();

        //Carrega a view (o header da view vai se adaptar sozinho se a sessão existir)
        ViewHelper::render('home/index');
    }
}
