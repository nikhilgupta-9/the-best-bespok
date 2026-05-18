<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

require_once dirname(__DIR__) . '/vendor/autoload.php'; // go up from config/ to project root

use Dotenv\Dotenv;

// Load .env from project root
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Read env variables
$host = $_ENV['DB_HOST'];
$username = $_ENV['DB_USERNAME'];
$password = $_ENV['DB_PASSWORD'];
$dbName = $_ENV['DB_NAME'];
define('BASE_URL', $_ENV['SITE']);
define('RZP_KEY_ID',     $_ENV['RAZORPAY_KEY_ID']     ?? '');
define('RZP_KEY_SECRET', $_ENV['RAZORPAY_KEY_SECRET']  ?? '');

// Make `BASE_URL` global

// Create Database Connection
$conn = new mysqli($host, $username, $password, $dbName);

// Check Connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$conn->set_charset("utf8");
