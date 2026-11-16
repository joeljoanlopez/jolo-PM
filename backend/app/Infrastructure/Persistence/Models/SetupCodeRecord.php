<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $hash
 */
class SetupCodeRecord extends Model
{
    protected $table = 'setup_codes';

    public $incrementing = false;

    public const UPDATED_AT = null;

    protected $fillable = ['id', 'hash'];
}
