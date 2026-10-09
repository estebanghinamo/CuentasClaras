<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Auth',
    description: 'Registro, login, refresh de tokens y sesion actual',
)]
class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    #[OA\Post(
        path: '/auth/register',
        tags: ['Auth'],
        summary: 'Crear una cuenta nueva',
        security: [],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Ana Gomez'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
                ],
            )),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Cuenta creada, sesion iniciada',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/AuthTokens', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 422,
                description: 'Datos invalidos o email ya registrado',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        $tokens = $this->authService->register($request->validated());

        return ApiResponse::created($tokens->toArray());
    }

    #[OA\Post(
        path: '/auth/login',
        tags: ['Auth'],
        summary: 'Iniciar sesion',
        security: [],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                    new OA\Property(property: 'device_name', type: 'string', nullable: true, example: 'web'),
                ],
            )),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login exitoso (o challenge de 2FA si el usuario lo tiene activo)',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/AuthTokens', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 401,
                description: 'Credenciales invalidas',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();
        $tokens = $this->authService->login($data['email'], $data['password'], $data['device_name'] ?? 'web');

        return ApiResponse::ok($tokens->toArray());
    }

    #[OA\Post(
        path: '/auth/refresh',
        tags: ['Auth'],
        summary: 'Renovar el access token usando el refresh token',
        security: [],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['refresh_token'],
                properties: [new OA\Property(property: 'refresh_token', type: 'string')],
            )),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Par de tokens nuevo',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/AuthTokens', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 401,
                description: 'Refresh token invalido o vencido',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        $tokens = $this->authService->refresh($request->validated()['refresh_token']);

        return ApiResponse::ok($tokens->toArray());
    }

    #[OA\Post(
        path: '/auth/logout',
        tags: ['Auth'],
        summary: 'Cerrar sesion (revoca el access token actual)',
        responses: [
            new OA\Response(response: 204, description: 'Sesion cerrada'),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return ApiResponse::noContent();
    }

    #[OA\Get(
        path: '/auth/me',
        tags: ['Auth'],
        summary: 'Obtener el usuario autenticado actual',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Usuario actual',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/User', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function me(Request $request): JsonResponse
    {
        $user = $this->authService->me($request->user()->id);

        return ApiResponse::ok($user->toArray());
    }
}
