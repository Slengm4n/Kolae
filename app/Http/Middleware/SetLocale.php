<?php
// app/Http/Middleware/SetLocale.php
 
namespace App\Http\Middleware;
 
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
 
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('idioma', 'pt-br');
        app()->setLocale($locale);
 
        return $next($request);
    }
}