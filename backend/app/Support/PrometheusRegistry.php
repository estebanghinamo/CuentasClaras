<?php

namespace App\Support;

use Prometheus\CollectorRegistry;
use Prometheus\Storage\Predis;

/**
 * Registro compartido de métricas Prometheus. Usa Redis (ya presente en el
 * proyecto vía predis/predis) como storage para que las métricas persistan
 * entre requests, sin depender de la extensión nativa phpredis.
 */
class PrometheusRegistry
{
    private static ?CollectorRegistry $instance = null;

    public static function get(): CollectorRegistry
    {
        if (self::$instance === null) {
            $storage = new Predis([
                'host' => config('database.redis.default.host'),
                'port' => (int) config('database.redis.default.port'),
                'password' => config('database.redis.default.password') ?: null,
                'database' => (int) config('database.redis.default.database'),
            ]);

            self::$instance = new CollectorRegistry($storage);
        }

        return self::$instance;
    }
}
