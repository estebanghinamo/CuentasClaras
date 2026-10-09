<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TwoFactorConfirmRequest;
use App\Http\Requests\Auth\TwoFactorDisableRequest;
use App\Http\Requests\Auth\TwoFactorVerifyRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Auth\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TwoFactorController extends Controller
{
    public function __construct(private readonly TwoFactorService $twoFactorService)
    {
    }

    public function enable(Request $request): JsonResponse
    {
        $user = $request->user();
        $setup = $this->twoFactorService->enable((int) $user->id, $user->email);

        return ApiResponse::ok($setup->toArray());
    }

    public function confirm(TwoFactorConfirmRequest $request): JsonResponse
    {
        $codes = $this->twoFactorService->confirm((int) $request->user()->id, $request->validated()['code']);

        return ApiResponse::ok(['recovery_codes' => $codes]);
    }

    public function disable(TwoFactorDisableRequest $request): JsonResponse
    {
        $user = $request->user();
        $this->twoFactorService->disable((int) $user->id, $user->email, $request->validated()['password']);

        return ApiResponse::noContent();
    }

    public function verify(TwoFactorVerifyRequest $request): JsonResponse
    {
        $tokens = $this->twoFactorService->verifyChallenge($request->user(), $request->validated()['code']);

        return ApiResponse::ok($tokens->toArray());
    }
}
