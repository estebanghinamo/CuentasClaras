<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Headers de seguridad detectados como faltantes por OWASP ZAP Baseline
     * Scan (ver CalidadYSeguridad.md). Backend 100% API sin vistas propias, asi que
     * no hace falta CSP (nada renderiza HTML/JS servido por este backend).
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        $response->headers->remove('X-Powered-By');

        return $response;
    }
}
