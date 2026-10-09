<?php

namespace App\Http\Middleware;

use App\Support\PrometheusRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Instrumentación para el laboratorio de observabilidad (observability-lab/).
 * Registra duración y status de cada request en Prometheus (histogram +
 * counter), con la ruta como label. No se usa en producción todavía: ver
 * PRODUCTION_CHECKLIST.md.
 */
class RecordRequestMetrics
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);
        $response = $next($request);

        try {
            $registry = PrometheusRegistry::get();
            $route = $request->route()?->uri() ?? $request->path();
            $status = (string) $response->getStatusCode();
            $duration = microtime(true) - $start;

            $registry->getOrRegisterCounter(
                'app',
                'http_requests_total',
                'Total de requests HTTP',
                ['route', 'method', 'status']
            )->inc(['route' => $route, 'method' => $request->method(), 'status' => $status]);

            $registry->getOrRegisterHistogram(
                'app',
                'http_request_duration_seconds',
                'Duración de requests HTTP en segundos',
                ['route', 'method'],
                [0.05, 0.1, 0.25, 0.5, 1, 2.5, 5]
            )->observe($duration, ['route' => $route, 'method' => $request->method()]);
        } catch (Throwable $e) {
            // Si Redis no está disponible, la instrumentación nunca debe romper la app.
            report($e);
        }

        return $response;
    }
}
