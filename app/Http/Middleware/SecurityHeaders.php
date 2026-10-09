<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Eleva a HTTPS cualquier recurso o envío que llegue por HTTP.
        // Evita el aviso de "datos no protegidos" por contenido mixto
        // (por ejemplo, GIFs con URL http://) en producción.
        $response->headers->set('Content-Security-Policy', 'upgrade-insecure-requests');

        return $response;
    }
}
