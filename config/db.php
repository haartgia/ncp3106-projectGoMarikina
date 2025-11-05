<?php
$host = "db.fr-pari1.bengt.wasmernet.com";
$user = "b01d5d797c138000d457051efd42";  // remote DB user
$pass = "0690b01d-5d79-7d56-8000-6f533dffeec9";
$db   = "user_db";
$port = 10272; // your MySQL port

// Establish MySQLi connection. Wrap in try/catch so an unavailable MySQL server
// doesn't produce an uncaught exception (which previously crashed Apache child
// processes). Instead we log the error and show a friendly message.
mysqli_report(MYSQLI_REPORT_OFF);
try {
    $conn = new mysqli($host, $user, $pass, $db, $port); // <— added $port here
    if ($conn->connect_error) {
        // handle non-exceptional connection errors
        error_log('DB connect error: ' . $conn->connect_error);
        http_response_code(500);
        die('Database connection unavailable. Please ensure MySQL is running.');
    }
} catch (Throwable $e) {
    // Log the underlying exception (stack trace may be written to Apache error log)
    error_log('DB connection exception: ' . $e->getMessage());
    http_response_code(500);
    // Friendly message for browser users; don't leak credentials or internals.
    die('Database connection unavailable. Please ensure MySQL (MariaDB) is running and accessible.');
}

/**
 * Get a new database connection
 * Returns a mysqli connection object
 */
function get_db_connection() {
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "user_db";
    $port = 3306; // default local port

    mysqli_report(MYSQLI_REPORT_OFF);
    try {
        $conn = new mysqli($host, $user, $pass, $db, $port); // <— added $port here too
        if ($conn->connect_error) {
            throw new Exception('DB connect error: ' . $conn->connect_error);
        }
        return $conn;
    } catch (Throwable $e) {
        error_log('DB connection exception: ' . $e->getMessage());
        throw new Exception('Database connection unavailable: ' . $e->getMessage());
    }
}
?>
