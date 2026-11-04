<?php

// Textos para el usuario de la API y la consola. Las claves las ponen los
// Message del dominio; las de `request` y `attributes` son de ProjectRequest.
return [
    'validation_failed' => 'Hay campos no válidos.',
    'duplicate_project' => 'El repositorio :repository ya está dado de alta.',
    'project_not_found' => 'No existe el proyecto :id.',
    'route_not_found' => 'Ruta no encontrada.',
    'repository_not_accessible' => 'GitHub no encuentra :resource o el token no tiene acceso.',
    'missing_token' => 'Falta GITHUB_TOKEN: configúralo en el .env del servidor.',
    'connection_failed' => 'No se puede conectar con GitHub: :detail',
    'token_rejected' => 'GitHub rechaza el token (401). Revisa GITHUB_TOKEN.',
    'rate_limited' => 'Límite de peticiones de GitHub agotado; se reintentará en la próxima sincronización.',
    'unexpected_response' => 'GitHub ha respondido :status al leer :resource: :detail',
    // Errores guardados antes de que hubiera traducciones: ya vienen en texto.
    'legacy' => ':text',

    'resource' => [
        'repository' => 'el repositorio :repository',
        'repositories' => 'tus repositorios',
        'branch' => 'la rama :branch de :repository',
        'blob' => 'el fichero :sha de :repository',
        'pulls' => 'las PRs de :repository',
        'pull' => 'la PR #:number de :repository',
    ],

    'validation' => [
        'repository_format' => 'El repositorio debe tener la forma "propietario/nombre".',
        'branch_invalid' => 'Nombre de rama no válido.',
        'paths_required' => 'Indica al menos una ruta o glob, por ejemplo docs/**/*.md.',
        'name_too_long' => 'El nombre no puede pasar de 100 caracteres.',
    ],

    'request' => [
        'required' => 'Falta el campo :attribute.',
        'paths.min' => 'Indica al menos una ruta o glob, por ejemplo docs/**/*.md.',
        'array' => 'El campo :attribute debe ser una lista.',
        'string' => 'El campo :attribute debe ser texto.',
        'max' => 'El campo :attribute es demasiado largo.',
        'prohibited' => 'El :attribute no se puede cambiar; da de alta otro proyecto.',
    ],

    'attributes' => [
        'repository' => 'repositorio',
        'branch' => 'rama',
        'paths' => 'rutas',
        'name' => 'nombre',
    ],
];
