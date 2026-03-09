<?php

namespace MiSocio;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?PDO $connection = null;

    /**
     * Retorna la conexion PDO (singleton).
     *
     * @throws RuntimeException si no se puede conectar
     */
    public static function connect(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $host   = env('DB_HOST', '127.0.0.1');
        $port   = env('DB_PORT', '3306');
        $dbname = env('DB_DATABASE', 'misocio');
        $user   = env('DB_USERNAME', 'root');
        $pass   = env('DB_PASSWORD', '');

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

        try {
            self::$connection = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException(
                'Error de conexion a la base de datos: ' . $e->getMessage()
            );
        }

        return self::$connection;
    }

    /**
     * Prueba la conexion y retorna un array con el resultado.
     */
    public static function test(): array
    {
        try {
            $pdo = self::connect();
            $pdo->query('SELECT 1');
            return ['success' => true, 'message' => 'Conexion exitosa a la base de datos'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Resetea la conexion (util al cambiar credenciales).
     */
    public static function reset(): void
    {
        self::$connection = null;
    }
}
