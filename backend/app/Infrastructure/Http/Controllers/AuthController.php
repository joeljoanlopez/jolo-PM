<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\Auth\Login;
use App\Application\Auth\RegenerateRecoveryCodes;
use App\Application\Auth\VerifySecondFactor;
use App\Infrastructure\Http\Auth\AuthSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Login en dos pasos: contraseña (POST /auth/login) y segundo factor
 * (POST /auth/two-factor). Las reglas están en los casos de uso; aquí solo se
 * lleva el estado de la sesión.
 */
final class AuthController
{
    public function __construct(private readonly AuthSession $auth)
    {
    }

    /** Quién soy y en qué paso estoy: setup, login, two_factor o authenticated. */
    public function me(): JsonResponse
    {
        return response()->json(['state' => $this->auth->state()]);
    }

    public function login(Request $request, Login $login): JsonResponse
    {
        $input = Input::validate($request, ['password' => ['required', 'string', 'max:1000']]);

        $accountId = $login->execute($input['password'], (string) $request->ip());
        $this->auth->startPending($accountId);

        return $this->me();
    }

    public function twoFactor(Request $request, VerifySecondFactor $verify): JsonResponse
    {
        $input = Input::validate($request, [
            'code' => ['required_without:recoveryCode', 'nullable', 'string', 'max:20'],
            'recoveryCode' => ['required_without:code', 'nullable', 'string', 'max:40'],
        ]);
        $accountId = $this->auth->pendingAccount();
        $ip = (string) $request->ip();

        $remaining = null;
        if (($input['code'] ?? null) !== null) {
            $verify->withTotp($accountId, $input['code'], $ip);
        } else {
            $remaining = $verify->withRecoveryCode($accountId, (string) $input['recoveryCode'], $ip);
        }
        $this->auth->authenticate($accountId);

        return response()->json(['state' => 'authenticated', 'recoveryCodesLeft' => $remaining]);
    }

    public function logout(): Response
    {
        $this->auth->logout();

        return response()->noContent();
    }

    public function regenerateRecoveryCodes(Request $request, RegenerateRecoveryCodes $regenerate): JsonResponse
    {
        $input = Input::validate($request, ['code' => ['required', 'string', 'max:20']]);

        return response()->json([
            'recoveryCodes' => $regenerate->execute($input['code'], (string) $request->ip()),
        ]);
    }
}
