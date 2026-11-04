<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\AddProject;
use App\Application\ProjectOverview;
use App\Application\RemoveProject;
use App\Application\SyncProject;
use App\Application\UpdateProject;
use App\Infrastructure\Http\Requests\ProjectRequest;
use App\Infrastructure\I18n\Translator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Adaptador de entrada HTTP: traduce peticiones a casos de uso y su resultado
 * a JSON. Sin lógica de negocio; las excepciones las traduce bootstrap/app.php
 * y el error guardado de la última sincronización, Translator.
 */
final class ProjectController
{
    public function index(ProjectOverview $overview): JsonResponse
    {
        return response()->json(array_map(Translator::translateErrorIn(...), $overview->list()));
    }

    public function store(ProjectRequest $request, AddProject $add, ProjectOverview $overview): JsonResponse
    {
        $project = $add->execute(
            $request->string('repository')->toString(),
            $request->input('branch'),
            $request->input('paths'),
            $request->input('name'),
        );

        return response()->json(Translator::translateErrorIn($overview->board((int) $project->id)), 201);
    }

    public function update(int $id, ProjectRequest $request, UpdateProject $update, ProjectOverview $overview): JsonResponse
    {
        $update->execute($id, $request->input('branch'), $request->input('paths'), $request->input('name'));

        return response()->json(Translator::translateErrorIn($overview->board($id)));
    }

    public function destroy(int $id, RemoveProject $remove): Response
    {
        $remove->execute($id);

        return response()->noContent();
    }

    public function sync(int $id, SyncProject $sync, ProjectOverview $overview): JsonResponse
    {
        $sync->execute($id);

        return response()->json(Translator::translateErrorIn($overview->board($id)));
    }

    public function board(int $id, ProjectOverview $overview): JsonResponse
    {
        return response()->json(Translator::translateErrorIn($overview->board($id)));
    }
}
