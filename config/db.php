<?php
$host = "localhost";
$user = "root";      // default user in XAMPP
$pass = "";          // leave blank (default)
$db   = "user_db";   // your database name

// Establish MySQLi connection. Wrap in try/catch so an unavailable MySQL server
// doesn't produce an uncaught exception (which previously crashed Apache child
// processes). Instead we log the error and show a friendly message.
mysqli_report(MYSQLI_REPORT_OFF);
try {
    $conn = new mysqli($host, $user, $pass, $db);
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
?>
