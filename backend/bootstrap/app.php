<?php

use App\Exceptions\DomainException;
use App\Exceptions\StoredProcedureException;
use App\Http\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // Backend 100% API (Sanctum Bearer tokens, sin vistas ni sesiones web):
        // sin grupo 'web' no hay ninguna ruta que dispare StartSession/cookies
        // de sesion (ver CalidadYSeguridad.md, hallazgo real de ZAP Baseline Scan).
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Backend 100% API: no hay vistas de login web a las que redirigir un guest.
        $middleware->redirectGuestsTo(fn () => null);

        // Fix real de un bug de tunel/proxy (ver EnsureContentLength) - global para
        // cubrir cualquier respuesta dinamica, no solo /api/*.
        $middleware->append(\App\Http\Middleware\EnsureContentLength::class);

        // Headers de seguridad (ver CalidadYSeguridad.md, hallazgo real de ZAP Baseline Scan).
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Instrumentación para observability-lab/ (estudio personal, ver su README).
        // No se usa en producción todavía: ver PRODUCTION_CHECKLIST.md.
        $middleware->append(\App\Http\Middleware\RecordRequestMetrics::class);

        // Laravel 11+ no registra solo estos alias de Sanctum; hace falta a mano.
        $middleware->alias([
            'ability' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
            'abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            'workspace.member' => \App\Http\Middleware\EnsureWorkspaceMember::class,
            'workspace.owner' => \App\Http\Middleware\EnsureWorkspaceOwner::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Backend 100% API (sin vistas ni login web): toda excepción se responde en JSON,
        // siempre con el envelope de ESPECIFICACION_TECNICA.md §0.4.
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => true);

        $exceptions->render(function (DomainException $e) {
            return ApiResponse::error($e->errorCode, $e->getMessage(), $e->status, $e->details);
        });

        $exceptions->render(function (ValidationException $e) {
            return ApiResponse::error('VALIDATION_ERROR', 'Datos inválidos.', 422, $e->errors());
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            $header = $request->header('Authorization', '');
            $token = str_starts_with($header, 'Bearer ') ? substr($header, 7) : null;
            $code = 'UNAUTHENTICATED';
            $message = 'No estás autenticado.';

            if ($token !== null) {
                $accessToken = PersonalAccessToken::findToken($token);
                if ($accessToken !== null && $accessToken->expires_at !== null && $accessToken->expires_at->isPast()) {
                    $code = 'TOKEN_EXPIRED';
                    $message = 'La sesión venció, iniciá sesión de nuevo.';
                }
            }

            return ApiResponse::error($code, $message, 401);
        });

        // Laravel envuelve cualquier AuthorizationException sin status explícito (incluida
        // Sanctum\Exceptions\MissingAbilityException, que es la que realmente se lanza acá)
        // en AccessDeniedHttpException antes de llegar a los render() (ver Handler::prepareException).
        $exceptions->render(function (AccessDeniedHttpException $e) {
            return ApiResponse::error('FORBIDDEN', 'No tenés permiso para realizar esta acción.', 403);
        });

        $exceptions->render(function (ThrottleRequestsException $e) {
            return ApiResponse::error('RATE_LIMITED', 'Demasiados intentos. Probá de nuevo en unos minutos.', 429);
        });

        $exceptions->render(function (PostTooLargeException $e) {
            return ApiResponse::error('PAYLOAD_TOO_LARGE', 'El archivo o los datos enviados son demasiado grandes.', 413);
        });

        $exceptions->render(function (NotFoundHttpException $e) {
            return ApiResponse::error('NOT_FOUND', 'El recurso solicitado no existe.', 404);
        });

        $exceptions->render(function (StoredProcedureException $e) {
            // Un Service debería haber mapeado esto antes con SpErrorMapper. Si llegó
            // hasta acá sin mapear, es un caso no contemplado: se loguea completo y se
            // responde genérico, nunca el código interno del SP al cliente.
            Log::error('Stored procedure sin mapear', [
                'procedure' => $e->procedure,
                'sp_code' => $e->spCode,
            ]);

            return ApiResponse::error('INTERNAL_ERROR', 'Ocurrió un error interno. Ya fue registrado.', 500);
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            Log::error($e->getMessage(), ['exception' => $e]);

            $debug = config('app.debug')
                ? ['class' => $e::class, 'message' => $e->getMessage()]
                : null;

            return ApiResponse::error('INTERNAL_ERROR', 'Ocurrió un error interno. Ya fue registrado.', 500, null, $debug);
        });
    })
    ->create();
