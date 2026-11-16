<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $ip
 * @property bool $succeeded
 * @property string $step
 */
class LoginAttemptRecord extends Model
{
    protected $table = 'login_attempts';

    public const UPDATED_AT = null;

    protected $fillable = ['ip', 'succeeded', 'step', 'created_at'];

    protected $casts = ['succeeded' => 'boolean'];
}
