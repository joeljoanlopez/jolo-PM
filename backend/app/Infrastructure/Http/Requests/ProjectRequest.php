<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Solo valida la forma de la petición (tipos y presencia). Las reglas de
 * negocio (formato del repo, rama válida, al menos un glob, agrupado conocido)
 * son del dominio, en App\Domain\Model\Project.
 */
class ProjectRequest extends FormRequest
{
    public function rules(): array
    {
        $creating = $this->isMethod('POST');

        return [
            'repository' => [$creating ? 'required' : 'prohibited', 'string', 'max:200'],
            'branch' => ['nullable', 'string', 'max:255'],
            'paths' => ['required', 'array', 'min:1', 'max:50'],
            // nullable: Laravel convierte "   " en null; el dominio descarta las vacías.
            'paths.*' => ['nullable', 'string', 'max:500'],
            'name' => ['nullable', 'string', 'max:100'],
            'groupBy' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function attributes(): array
    {
        return __('errors.attributes');
    }

    public function messages(): array
    {
        return __('errors.request');
    }
}
