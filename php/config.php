<?php
/**
 * Database Configuration — HopeHands Foundation
 * Update credentials below to match your MySQL setup.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'ngo_donation');
define('DB_USER', 'root');
define('DB_PASS', 'WJ28@krhps');        // default XAMPP/WAMP password is empty

/**
 * Create and return a PDO connection.
 */
function getDBConnection() {
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        die('Database connection failed: ' . $e->getMessage());
    }
}
