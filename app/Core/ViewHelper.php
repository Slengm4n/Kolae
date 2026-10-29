<?php

namespace App\Core;

use Exception;

/**
 * Class ViewHelper
 * Responsável por renderizar os ficheiros de view.
 */
class ViewHelper
{
    /**
     * Renderiza um ficheiro de view e passa dados para ele.
     * @param string $view O caminho para a view (ex: 'admin/users/index').
     * @param array $data Os dados a serem extraídos como variáveis para a view.
     * @throws Exception Se o ficheiro da view não for encontrado.
     */
    public static function render(string $view, array $data = [])
    {
        // Transforma as chaves do array $data em variáveis
        // Ex: $data['users'] se torna a variável $users dentro da view.
        extract($data);

        // Constrói o caminho completo para o ficheiro da view.
        // Ex: 'admin/users/index' se torna 'D:\...\app/views/admin/users/index.php'
        $viewPath = BASE_PATH . "/app/views/" . $view . ".php";

        // Verifica se o ficheiro realmente existe antes de tentar incluí-lo.
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            // Se não encontrar o ficheiro, lança um erro claro.
            throw new Exception("A view '{$view}' não foi encontrada em: {$viewPath}");
        }
    }

    /**
     * URL de um arquivo de assets/ com a versão no fim (?v=data de modificação).
     * Cada deploy muda a data, então o navegador baixa o CSS/JS novo em vez de
     * usar o antigo do cache.
     * @param string $path Caminho dentro do projeto (ex: 'assets/css/style.css').
     */
    public static function asset(string $path): string
    {
        $file = BASE_PATH . '/' . ltrim($path, '/');
        $version = is_file($file) ? '?v=' . filemtime($file) : '';
        return BASE_URL . '/' . ltrim($path, '/') . $version;
    }
}
