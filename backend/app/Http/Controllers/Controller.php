<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Cuentas Claras API',
    description: 'API REST de Cuentas Claras. Todas las respuestas usan el envelope '
        . '{success,data,meta?} en exito o {success:false,error:{code,message,details}} en error '
        . '(ver ApiErrorSchema/ApiSuccessSchema mas abajo).',
)]
#[OA\Server(url: '/api', description: 'API base path')]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    description: 'Token de Sanctum obtenido via POST /auth/login. Enviar como "Bearer {token}".',
)]
#[OA\Schema(
    schema: 'ApiError',
    description: 'Envelope de error uniforme (ApiResponse::error)',
    required: ['success', 'error'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(
            property: 'error',
            required: ['code', 'message'],
            properties: [
                new OA\Property(property: 'code', type: 'string', example: 'VALIDATION_ERROR'),
                new OA\Property(property: 'message', type: 'string', example: 'Los datos enviados no son validos.'),
                new OA\Property(
                    property: 'details',
                    type: 'object',
                    nullable: true,
                    additionalProperties: new OA\AdditionalProperties(
                        type: 'array',
                        items: new OA\Items(type: 'string'),
                    ),
                ),
            ],
            type: 'object',
        ),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'PaginationMeta',
    description: 'Metadata de paginacion (ApiResponse::paginated)',
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'per_page', type: 'integer', example: 20),
        new OA\Property(property: 'total', type: 'integer', example: 57),
        new OA\Property(property: 'last_page', type: 'integer', example: 3),
    ],
    type: 'object',
)]
abstract class Controller
{
    //
}
