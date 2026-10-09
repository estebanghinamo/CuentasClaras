<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureContentLength
{
    /**
     * php artisan serve (servidor embebido de PHP, ADR-004) no manda Content-Length
     * en respuestas dinámicas - depende de cerrar la conexión TCP para indicar "esto
     * es todo". El túnel de VS Code (Dev Tunnels) reescribe eso a Transfer-Encoding:
     * chunked + Connection: keep-alive; si el chunk final no llega bien, el navegador
     * queda esperando el cierre del stream para siempre aunque el body ya haya
     * llegado completo (net::ERR_HTTP2_PROTOCOL_ERROR / requests colgados minutos).
     * Mandar Content-Length explícito le da al cliente una forma inequívoca de saber
     * que terminó, sin depender de cómo el túnel interprete el cierre de conexión.
     */
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$response->headers->has('Content-Length')) {
            $content = $response->getContent();
            if ($content !== false) {
                $response->headers->set('Content-Length', (string) strlen($content));
            }
        }

        return $response;
    }
}
