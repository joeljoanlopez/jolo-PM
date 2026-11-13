<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // La cuenta única. Nunca hay más de una fila.
        Schema::create('accounts', function (Blueprint $table) {
            $table->string('id', 32)->primary();
            $table->string('password_hash');
            // Cifrado con APP_KEY (puerto SecretCipher): nunca en claro.
            $table->text('totp_secret');
            $table->unsignedBigInteger('last_totp_period')->nullable();
            // Hashes de los códigos de recuperación que quedan sin usar.
            $table->json('recovery_codes');
            $table->timestamps();
        });

        // Hash del código de configuración inicial, mientras no hay cuenta.
        Schema::create('setup_codes', function (Blueprint $table) {
            $table->id();
            $table->string('hash', 64);
            $table->timestamp('created_at');
        });

        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45);
            // password, totp o recovery_code
            $table->string('method', 20);
            $table->boolean('success');
            $table->timestamp('created_at')->index();
        });

        // Sesiones de Laravel (SESSION_DRIVER=database): sobreviven a reinicios.
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('login_attempts');
        Schema::dropIfExists('setup_codes');
        Schema::dropIfExists('accounts');
    }
};
