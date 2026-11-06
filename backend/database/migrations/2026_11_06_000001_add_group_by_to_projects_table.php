<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 'file' = una tarjeta por fichero, como hasta ahora: los proyectos que
        // ya existen se siguen viendo igual.
        Schema::table('projects', function (Blueprint $table) {
            $table->string('group_by', 20)->default('file');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('group_by');
        });
    }
};
