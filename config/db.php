<?php

class DB {

    private static array $connections = [];

    /**
     * Retorna la conexión PDO solicitada.
     * @param string $which  'almacenes' | 'cwofran'
     */
    public static function get(string $which = 'almacenes'): PDO {

        if (isset(self::$connections[$which])) {
            return self::$connections[$which];
        }

        $configs = [
            'almacenes' => [
                'dsn'  => 'mysql:host=195.35.61.108;dbname=u826340212_almacenes;charset=utf8mb4',
                'user' => 'u826340212_almacenes',
                'pass' => 'Cwo9982061148.',
            ],
            'cwofran' => [
                'dsn'  => 'mysql:host=195.35.61.108;dbname=u826340212_cwofran;charset=utf8mb4',
                'user' => 'u826340212_cwofran',
                'pass' => 'Cwo9982061148',
            ],
        ];

        if (!isset($configs[$which])) {
            throw new InvalidArgumentException("Conexión '$which' no definida.");
        }

        $cfg = $configs[$which];

        $pdo = new PDO($cfg['dsn'], $cfg['user'], $cfg['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        self::$connections[$which] = $pdo;
        return $pdo;
    }
}