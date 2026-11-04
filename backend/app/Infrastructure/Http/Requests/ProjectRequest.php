<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Solo valida la forma de la petición (tipos y presencia). Las reglas de
 * negocio (formato del repo, rama válida, al menos un glob) son del dominio,
 * en App\Domain\Model\Project.
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
        ];
    }

    public function attributes(): array
    {
        return ['repository' => 'repositorio', 'branch' => 'rama', 'paths' => 'rutas', 'name' => 'nombre'];
    }

    /** En castellano aquí mismo, sin publicar ficheros de idioma para cinco mensajes. */
    public function messages(): array
    {
        return [
            'required' => 'Falta el campo :attribute.',
            'paths.min' => 'Indica al menos una ruta o glob, por ejemplo docs/**/*.md.',
            'array' => 'El campo :attribute debe ser una lista.',
            'string' => 'El campo :attribute debe ser texto.',
            'max' => 'El campo :attribute es demasiado largo.',
            'prohibited' => 'El :attribute no se puede cambiar; da de alta otro proyecto.',
        ];
    }
}
