<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\ListRepositories;
use App\Domain\Model\RepositoryInfo;
use Illuminate\Http\JsonResponse;

final class RepositoryController
{
    public function index(ListRepositories $list): JsonResponse
    {
        return response()->json(array_map(static fn (RepositoryInfo $r) => [
            'fullName' => $r->fullName(),
            'defaultBranch' => $r->defaultBranch,
            'private' => $r->private,
            'description' => $r->description,
            'url' => $r->htmlUrl,
        ], $list->execute()));
    }
}
