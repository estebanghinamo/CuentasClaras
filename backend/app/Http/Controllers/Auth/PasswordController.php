<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Auth\PasswordResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PasswordController extends Controller
{
    public function __construct(private readonly PasswordResetService $passwordResetService)
    {
    }

    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $this->passwordResetService->forgot($request->validated()['email']);

        return ApiResponse::ok(null);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();
        $this->passwordResetService->reset($data['email'], $data['token'], $data['password']);

        return ApiResponse::ok(null);
    }

    public function change(ChangePasswordRequest $request): JsonResponse
    {
        $data = $request->validated();
        $this->passwordResetService->changePassword($request->user(), $data['current_password'], $data['password']);

        return ApiResponse::ok(null);
    }
}
