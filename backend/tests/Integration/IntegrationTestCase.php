<?php

declare(strict_types=1);

namespace Tests\Integration;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Base de la suite `Integration`: los adaptadores contra una base SQLite en
 * fichero con las migraciones de verdad, el mismo motor que en producción.
 *
 * A diferencia de `Feature` (SQLite en memoria y una transacción por test),
 * aquí cada test migra desde cero y deshace las migraciones al acabar, así que
 * también se ejercitan los `down()` y las claves foráneas.
 */
abstract class IntegrationTestCase extends TestCase
{
    use DatabaseMigrations;

    private static ?string $database = null;

    public function createApplication()
    {
        $app = parent::createApplication();
        $app['config']->set('database.connections.sqlite.database', self::database());

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Después del rollback de DatabaseMigrations: sin cerrar la conexión,
        // la app del test anterior sigue con el fichero abierto y bloqueado.
        $this->beforeApplicationDestroyed(fn () => DB::disconnect());
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$database !== null && file_exists(self::$database)) {
            unlink(self::$database);
        }
        self::$database = null;
        parent::tearDownAfterClass();
    }

    /** Un fichero por proceso: no choca con otras ejecuciones a la vez. */
    private static function database(): string
    {
        if (self::$database === null) {
            self::$database = sys_get_temp_dir() . '/jolopm-integration-' . getmypid() . '.sqlite';
            touch(self::$database);
        }

        return self::$database;
    }
}
