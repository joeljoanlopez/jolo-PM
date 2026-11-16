<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use App\Domain\Model\Auth\Account;
use App\Domain\Model\Auth\RecoveryCodes;
use Illuminate\Database\Eloquent\Model;

/**
 * Fila de la tabla `accounts`. Fuera de Persistence se trabaja con
 * App\Domain\Model\Auth\Account.
 *
 * @property string $id
 * @property string $password_hash
 * @property string $totp_secret
 * @property int|null $last_totp_period
 * @property list<string>|null $recovery_codes
 */
class AccountRecord extends Model
{
    protected $table = 'accounts';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['id', 'password_hash', 'totp_secret', 'last_totp_period', 'recovery_codes'];

    protected $casts = ['recovery_codes' => 'array', 'last_totp_period' => 'integer'];

    public function toDomain(): Account
    {
        return new Account(
            $this->id,
            $this->password_hash,
            $this->totp_secret,
            $this->last_totp_period,
            new RecoveryCodes($this->recovery_codes ?? []),
        );
    }
}
