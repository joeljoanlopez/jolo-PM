<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\CompleteSetup;
use App\Application\Login;
use App\Application\RegenerateRecoveryCodes;
use App\Application\SessionStatus;
use App\Application\VerifySecondFactor;
use App\Domain\Exception\LoginRequired;
use App\Domain\Exception\SetupNotAvailable;
use App\Infrastructure\Auth\AuthSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Adaptador HTTP del login: configuración inicial, login en dos pasos y
 * cierre de sesión. El estado entre pasos vive en la sesión (AuthSession).
 */
final class AuthController
{
    /** Quién soy y en qué paso estoy: setup, login, two_factor o authenticated. */
    public function me(Request $request, SessionStatus $status): JsonResponse
    {
        $session = self::session($request);

        return response()->json($status->describe($session->authenticatedAccountId(), $session->pending()));
    }

    public function setupStatus(CompleteSetup $setup): JsonResponse
    {
        if (!$setup->isAvailable()) {
            throw new SetupNotAvailable();
        }

        return response()->json(['required' => true]);
    }

    /** Paso 1: el código de configuración de los logs. */
    public function setupCode(Request $request, CompleteSetup $setup): Response
    {
        $setup->checkCode(self::text($request, 'setupCode'), (string) $request->ip());

        return response()->noContent();
    }

    /** Paso 2: contraseña. Devuelve el secreto TOTP propuesto para el QR. */
    public function setupPassword(Request $request, CompleteSetup $setup): JsonResponse
    {
        $enrollment = $setup->choosePassword(
            self::text($request, 'setupCode'),
            self::text($request, 'password'),
            self::text($request, 'passwordConfirmation'),
            (string) $request->ip(),
        );
        self::session($request)->startEnrollment($enrollment);

        return response()->json(['secret' => $enrollment->secret, 'otpauthUri' => $enrollment->provisioningUri]);
    }

    /** Pasos 3 y 4: confirma el 2FA, crea la cuenta y devuelve los códigos de recuperación. */
    public function setupTwoFactor(Request $request, CompleteSetup $setup): JsonResponse
    {
        $session = self::session($request);
        $enrollment = $session->enrollment() ?? throw LoginRequired::setupExpired();

        $result = $setup->confirmTwoFactor(
            self::text($request, 'setupCode'),
            $enrollment,
            self::text($request, 'code'),
            (string) $request->ip(),
        );
        $session->authenticate($result['account']->id);

        return response()->json(['recoveryCodes' => $result['recoveryCodes']]);
    }

    public function login(Request $request, Login $login): JsonResponse
    {
        $pending = $login->execute(self::text($request, 'password'), (string) $request->ip());
        self::session($request)->startPending($pending);

        return response()->json(['state' => SessionStatus::TWO_FACTOR]);
    }

    public function twoFactor(Request $request, VerifySecondFactor $verify): JsonResponse
    {
        $session = self::session($request);
        $account = $verify->execute(
            $session->pending(),
            self::text($request, 'code'),
            self::text($request, 'recoveryCode'),
            (string) $request->ip(),
        );
        $session->authenticate($account->id);

        return response()->json([
            'state' => SessionStatus::AUTHENTICATED,
            'recoveryCodesLeft' => $account->recoveryCodes->remaining(),
        ]);
    }

    public function logout(Request $request): Response
    {
        self::session($request)->logout();

        return response()->noContent();
    }

    public function regenerateRecoveryCodes(RegenerateRecoveryCodes $regenerate): JsonResponse
    {
        return response()->json(['recoveryCodes' => $regenerate->execute()]);
    }

    private static function session(Request $request): AuthSession
    {
        return new AuthSession($request->session());
    }

    /** Campo de texto del cuerpo; cualquier otra cosa cuenta como vacío. */
    private static function text(Request $request, string $key): string
    {
        $value = $request->input($key);

        return is_string($value) ? $value : '';
    }
}
