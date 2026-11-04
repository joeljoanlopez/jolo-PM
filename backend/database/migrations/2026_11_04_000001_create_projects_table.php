<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('owner');
            $table->string('name');
            // Nulo = rama principal del repo, consultada en cada sincronización.
            $table->string('branch')->nullable();
            $table->json('paths');
            $table->string('display_name')->nullable();
            $table->timestamps();
            $table->unique(['owner', 'name']);
        });

        // Último tablero calculado. Se guarda como JSON porque solo se lee
        // entero: no hay consultas sobre tarjetas o tareas sueltas.
        Schema::create('board_snapshots', function (Blueprint $table) {
            $table->foreignId('project_id')->primary()->constrained()->cascadeOnDelete();
            $table->json('board')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('attempted_at')->nullable();
        });

        // Contenido de ficheros por SHA de blob. Un blob no cambia nunca, así
        // que no hace falta invalidar: solo purgar lo que ya no se usa.
        Schema::create('cached_blobs', function (Blueprint $table) {
            $table->string('sha', 64)->primary();
            $table->longText('content');
            $table->timestamp('last_used_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cached_blobs');
        Schema::dropIfExists('board_snapshots');
        Schema::dropIfExists('projects');
    }
};
