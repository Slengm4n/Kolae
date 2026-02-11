<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        //Verifica se o usuário está logado e o role é admin
        if (auth()->check() && auth()->user()->hasRole('admin')) {
            return $next($request);
        }

        return redirect('/dashboard')->with('error', 'Acesso negado.');
    }
}