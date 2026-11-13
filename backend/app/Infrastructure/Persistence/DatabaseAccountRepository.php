<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Auth\Account;
use App\Domain\Auth\RecoveryCodes;
use App\Domain\Auth\SetupCode;
use App\Domain\Port\AccountRepository;
use Illuminate\Database\ConnectionInterface;

final class DatabaseAccountRepository implements AccountRepository
{
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    public function get(): ?Account
    {
        $row = $this->db->table('accounts')->orderBy('created_at')->first();
        if ($row === null) {
            return null;
        }

        return new Account(
            (string) $row->id,
            (string) $row->password_hash,
            (string) $row->totp_secret,
            $row->last_totp_period === null ? null : (int) $row->last_totp_period,
            new RecoveryCodes(array_values(json_decode((string) $row->recovery_codes, true, flags: JSON_THROW_ON_ERROR))),
        );
    }

    public function save(Account $account): void
    {
        $now = now();
        $this->db->transaction(function () use ($account, $now) {
            // Cuenta única: al guardar una distinta, la anterior desaparece.
            $this->db->table('accounts')->where('id', '!=', $account->id)->delete();
            $this->db->table('accounts')->upsert(
                [[
                    'id' => $account->id,
                    'password_hash' => $account->passwordHash,
                    'totp_secret' => $account->encryptedTotpSecret,
                    'last_totp_period' => $account->lastTotpPeriod,
                    'recovery_codes' => json_encode($account->recoveryCodes->hashes, JSON_THROW_ON_ERROR),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]],
                ['id'],
                ['password_hash', 'totp_secret', 'last_totp_period', 'recovery_codes', 'updated_at'],
            );
        });
    }

    public function delete(): void
    {
        $this->db->table('accounts')->delete();
    }

    public function setupCode(): ?SetupCode
    {
        $hash = $this->db->table('setup_codes')->orderByDesc('id')->value('hash');

        return $hash === null ? null : SetupCode::fromHash((string) $hash);
    }

    public function saveSetupCode(SetupCode $code): void
    {
        $this->db->transaction(function () use ($code) {
            $this->db->table('setup_codes')->delete();
            $this->db->table('setup_codes')->insert(['hash' => $code->hash, 'created_at' => now()]);
        });
    }

    public function deleteSetupCode(): void
    {
        $this->db->table('setup_codes')->delete();
    }
}
