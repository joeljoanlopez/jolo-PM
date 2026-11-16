<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\Auth\CompleteSetup;
use App\Domain\Exception\AuthenticationFailed;
use App\Domain\Exception\SetupState;
use App\Domain\Port\AccountRepository;
use App\Infrastructure\Http\Auth\AuthSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Asistente de configuración inicial, en pasos:
 *  1. POST /auth/setup/code      {setupCode}            → secreto TOTP nuevo y su URI
 *  2. POST /auth/setup/password  {password, passwordConfirmation} → solo valida
 *  3. POST /auth/setup/complete  {password, passwordConfirmation, code}
 *     → crea la cuenta, deja la sesión iniciada y devuelve los códigos de recuperación.
 *
 * El código de configuración y el secreto se quedan en la sesión (cifrada)
 * entre pasos; la contraseña no, la manda la web otra vez al final. Con la
 * cuenta creada, todos los pasos responden 404.
 */
final class SetupController
{
    public function __construct(private readonly AuthSession $auth)
    {
    }

    public function show(AccountRepository $accounts): JsonResponse
    {
        return response()->json(['required' => $accounts->find() === null]);
    }

    public function code(Request $request, CompleteSetup $setup): JsonResponse
    {
        $input = Input::validate($request, ['setupCode' => ['required', 'string', 'max:100']]);

        $setup->checkCode($input['setupCode'], (string) $request->ip());
        $enrollment = $setup->newSecret();
        $this->auth->putSetup(['code' => $input['setupCode'], 'secret' => $enrollment['secret']]);

        return response()->json($enrollment);
    }

    public function password(Request $request, CompleteSetup $setup): Response
    {
        $this->setupInProgress();
        $input = Input::validate($request, self::passwordRules());

        $setup->checkPassword($input['password'], $input['passwordConfirmation']);

        return response()->noContent();
    }

    public function complete(Request $request, CompleteSetup $setup): JsonResponse
    {
        $state = $this->setupInProgress();
        $input = Input::validate($request, [...self::passwordRules(), 'code' => ['required', 'string', 'max:20']]);

        $result = $setup->execute(
            $state['code'],
            $input['password'],
            $input['passwordConfirmation'],
            $state['secret'],
            $input['code'],
            (string) $request->ip(),
        );
        $this->auth->authenticate($result['accountId']);

        return response()->json(['recoveryCodes' => $result['recoveryCodes']], 201);
    }

    /**
     * El estado del paso 1, que tiene que estar hecho en esta sesión.
     *
     * @return array{code: string, secret: string}
     */
    private function setupInProgress(): array
    {
        if ($this->auth->state() !== 'setup') {
            throw SetupState::alreadyDone();
        }
        $state = $this->auth->setup();
        if (!isset($state['code'], $state['secret'])) {
            throw AuthenticationFailed::setupCode();
        }

        return ['code' => $state['code'], 'secret' => $state['secret']];
    }

    /** @return array<string, list<string>> */
    private static function passwordRules(): array
    {
        return [
            'password' => ['required', 'string', 'max:1000'],
            'passwordConfirmation' => ['required', 'string', 'max:1000'],
        ];
    }
}
