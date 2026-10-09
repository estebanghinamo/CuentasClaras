<?php

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notifications\RegisterPushDeviceRequest;
use App\Http\Requests\Notifications\UnregisterPushDeviceRequest;
use App\Services\Notifications\PushDeviceService;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'PushDevices',
    description: 'Registro de dispositivos para notificaciones push (no dependen de un workspace)',
)]
class PushDeviceController extends Controller
{
    public function __construct(private readonly PushDeviceService $pushDevices)
    {
    }

    #[OA\Post(
        path: '/push-devices',
        tags: ['PushDevices'],
        summary: 'Registrar (o actualizar) un dispositivo para notificaciones push',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['token', 'platform'],
                properties: [
                    new OA\Property(property: 'token', type: 'string', maxLength: 255),
                    new OA\Property(property: 'platform', type: 'string', enum: ['android', 'ios', 'web']),
                    new OA\Property(
                        property: 'device_name',
                        type: 'string',
                        maxLength: 100,
                        nullable: true,
                        example: 'iPhone de Ana',
                    ),
                ],
            )),
        responses: [
            new OA\Response(response: 204, description: 'Dispositivo registrado'),
            new OA\Response(
                response: 422,
                description: 'Datos invalidos',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function store(RegisterPushDeviceRequest $request): Response
    {
        $data = $request->validated();
        $this->pushDevices->register(
            $request->user()->id,
            $data['token'],
            $data['platform'],
            $data['device_name'] ?? null,
        );

        return response()->noContent();
    }

    #[OA\Delete(
        path: '/push-devices',
        tags: ['PushDevices'],
        summary: 'Dar de baja un dispositivo (deja de recibir notificaciones push)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['token'],
                properties: [new OA\Property(property: 'token', type: 'string', maxLength: 255)],
            )),
        responses: [
            new OA\Response(response: 204, description: 'Dispositivo dado de baja'),
            new OA\Response(
                response: 422,
                description: 'Datos invalidos',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function destroy(UnregisterPushDeviceRequest $request): Response
    {
        $this->pushDevices->unregister($request->user()->id, $request->validated()['token']);

        return response()->noContent();
    }
}
