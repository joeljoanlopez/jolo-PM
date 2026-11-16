<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Model\Auth\Account;
use App\Domain\Model\Auth\SetupCode;
use App\Domain\Port\AccountRepository;
use App\Infrastructure\Persistence\Models\AccountRecord;
use App\Infrastructure\Persistence\Models\SetupCodeRecord;
use Illuminate\Support\Facades\DB;

final class EloquentAccountRepository implements AccountRepository
{
    /** Única fila de `setup_codes`. */
    private const SETUP_CODE_ID = 1;

    public function find(): ?Account
    {
        return AccountRecord::query()->first()?->toDomain();
    }

    public function save(Account $account): void
    {
        DB::transaction(function () use ($account) {
            // Usuario único: guardar una cuenta distinta sustituye a la anterior.
            AccountRecord::query()->where('id', '!=', $account->id)->delete();
            AccountRecord::query()->updateOrCreate(['id' => $account->id], [
                'password_hash' => $account->passwordHash,
                'totp_secret' => $account->encryptedTotpSecret,
                'last_totp_period' => $account->lastTotpPeriod,
                'recovery_codes' => $account->recoveryCodes->hashes,
            ]);
        });
    }

    public function delete(): void
    {
        DB::transaction(function () {
            AccountRecord::query()->delete();
            SetupCodeRecord::query()->delete();
        });
    }

    public function findSetupCode(): ?SetupCode
    {
        $record = SetupCodeRecord::find(self::SETUP_CODE_ID);

        return $record === null ? null : new SetupCode($record->hash);
    }

    public function saveSetupCode(SetupCode $code): void
    {
        SetupCodeRecord::query()->updateOrCreate(['id' => self::SETUP_CODE_ID], ['hash' => $code->hash]);
    }

    public function deleteSetupCode(): void
    {
        SetupCodeRecord::query()->delete();
    }
}
