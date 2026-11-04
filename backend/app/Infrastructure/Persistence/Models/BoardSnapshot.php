<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class BoardSnapshot extends Model
{
    protected $table = 'board_snapshots';

    protected $primaryKey = 'project_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['project_id', 'board', 'error', 'attempted_at'];

    protected $casts = [
        'board' => 'array',
        'attempted_at' => 'immutable_datetime',
    ];
}
