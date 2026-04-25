<?php
/**
 * Database Setup Script — Run once to create the database and tables.
 * Visit: http://localhost/DONATION/php/db_setup.php
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'WJ28@krhps');
define('DB_NAME', 'ngo_donation');

try {
    // Connect without database to create it
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Database '" . DB_NAME . "' created successfully.<br>";

    // Switch to the database
    $pdo->exec("USE `" . DB_NAME . "`");

    // Create donations table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `donations` (
            `id`             INT AUTO_INCREMENT PRIMARY KEY,
            `name`           VARCHAR(255) NOT NULL,
            `email`          VARCHAR(255) NOT NULL,
            `phone`          VARCHAR(20) DEFAULT NULL,
            `amount`         DECIMAL(10,2) NOT NULL,
            `cause`          VARCHAR(100) DEFAULT 'general',
            `payment_method` VARCHAR(50) DEFAULT 'upi',
            `message`        TEXT DEFAULT NULL,
            `anonymous`      TINYINT(1) DEFAULT 0,
            `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");
    echo "✅ Table 'donations' created successfully.<br>";

    // Create contacts table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `contacts` (
            `id`         INT AUTO_INCREMENT PRIMARY KEY,
            `name`       VARCHAR(255) NOT NULL,
            `email`      VARCHAR(255) NOT NULL,
            `subject`    VARCHAR(255) NOT NULL,
            `message`    TEXT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");
    echo "✅ Table 'contacts' created successfully.<br>";

    echo "<br>🎉 <strong>Database setup complete!</strong> You can now use the website.";
    echo "<br><br><a href='../index.html' style='color:#10b981'>← Go to Homepage</a>";

} catch (PDOException $e) {
    die("❌ Setup failed: " . $e->getMessage());
}
