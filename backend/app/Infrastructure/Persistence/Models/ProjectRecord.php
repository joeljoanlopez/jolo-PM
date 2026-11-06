<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use App\Domain\Model\GroupBy;
use App\Domain\Model\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Fila de la tabla `projects`. Es un detalle de infraestructura: fuera de
 * Persistence se trabaja siempre con App\Domain\Model\Project.
 */
class ProjectRecord extends Model
{
    protected $table = 'projects';

    protected $fillable = ['owner', 'name', 'branch', 'paths', 'display_name', 'group_by'];

    protected $casts = ['paths' => 'array'];

    public function snapshot(): HasOne
    {
        return $this->hasOne(BoardSnapshot::class, 'project_id');
    }

    public function toDomain(): Project
    {
        return new Project(
            $this->id,
            $this->owner,
            $this->name,
            $this->branch,
            array_values($this->paths ?? []),
            $this->display_name,
            GroupBy::tryFrom((string) $this->group_by) ?? GroupBy::File,
        );
    }
}
