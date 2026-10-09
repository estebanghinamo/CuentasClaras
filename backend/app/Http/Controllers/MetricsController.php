<?php

namespace App\Http\Controllers;

use App\Support\PrometheusRegistry;
use Illuminate\Http\Response;
use Prometheus\RenderTextFormat;

/**
 * Endpoint de scraping para Prometheus (observability-lab/). Sin auth: es
 * texto plano en formato Prometheus, no expone datos de negocio.
 */
class MetricsController extends Controller
{
    public function __invoke(): Response
    {
        $renderer = new RenderTextFormat();
        $body = $renderer->render(PrometheusRegistry::get()->getMetricFamilySamples());

        return response($body, 200)->header('Content-Type', RenderTextFormat::MIME_TYPE);
    }
}
