<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

require_once __DIR__ . '/_database/db_connection.php';

date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json');

// Global input (for JSON APIs)
global $input;
$input = json_decode(file_get_contents('php://input'), true);

// Autoload models (api/model/*.php)
spl_autoload_register(function ($class) {
    $baseDir = __DIR__ . '/model/';
    $file = $baseDir . $class . '.php'; // No namespace, just direct class filename

    if (file_exists($file)) {
        require_once $file;
    }
});
