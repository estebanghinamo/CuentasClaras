<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Laravel reutiliza la misma instancia de aplicación (y por lo tanto el mismo
     * guard de auth) entre varias llamadas postJson()/getJson() dentro de un mismo
     * test. `Illuminate\Auth\RequestGuard::user()` cachea el usuario resuelto en la
     * PRIMERA llamada autenticada y lo devuelve sin volver a mirar el header
     * Authorization en llamadas siguientes — rompe cualquier test que autentique con
     * un token distinto a mitad del test (ej. pasar de un access token a un challenge
     * token de 2FA). Se limpia el guard antes de cada request simulado para que cada
     * uno resuelva el usuario de nuevo a partir de su propio Bearer token, igual que
     * pasaría en un request HTTP real. Ver ERROR_LOG.md.
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        if ($this->app->bound('auth')) {
            $this->app['auth']->forgetGuards();
        }

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }
}
