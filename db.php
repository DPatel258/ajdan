<?php
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // skip comments
        list($name, $value) = array_map('trim', explode('=', $line, 2));
        $_ENV[$name] = $value;
        putenv("$name=$value");
    }
}


// Simple DB connector (mysqli)
$DB_HOST = getenv("DB_HOST");
$DB_USER = getenv("DB_USERNAME");
$DB_PASS = getenv("DB_PASSWORD");
$DB_NAME = getenv("DB_DATABASE");
$conn = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Optional: set utf8mb4 for proper unicode handling
mysqli_set_charset($conn, "utf8mb4");
