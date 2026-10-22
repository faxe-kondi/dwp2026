<?php
class Database
{
    private static ?PDO $connection = null;

    private const HOST = 'localhost';
    private const NAME = 'deadstock';
    private const USER = 'dsadmin';
    private const PASS = 'IAmAdmin12345!';

    public static function connect(): PDO
    {
        if (self::$connection === null) {
            try {
                self::$connection = new PDO(
                    'mysql:host=' . self::HOST . ';dbname=' . self::NAME . ';charset=utf8mb4',
                    self::USER,
                    self::PASS,
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]
                );
            } catch (PDOException $e) {
                error_log('Database connection failed: ' . $e->getMessage());
                http_response_code(500);
                die('Something went wrong on our end. Please try again shortly.');
            }
        }

        return self::$connection;
    }
}
