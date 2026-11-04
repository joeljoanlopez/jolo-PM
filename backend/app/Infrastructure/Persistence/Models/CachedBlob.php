<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class CachedBlob extends Model
{
    protected $table = 'cached_blobs';

    protected $primaryKey = 'sha';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['sha', 'content', 'last_used_at'];

    protected $casts = ['last_used_at' => 'immutable_datetime'];
}
