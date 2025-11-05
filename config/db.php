<?php
$host = "db.fr-pari1.bengt.wasmernet.com";
$user = "b57f42dd7c53800021d5d5455fa3";      // default user in XAMPP
$pass = "0690b57f-42dd-7f4f-8000-16010189e02d";          // leave blank (default)
$db   = "gomarikina";   // your database name
$port = 10272;

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

/**
 * Get a new database connection
 * Returns a mysqli connection object
 */
function get_db_connection() {
    $host = "db.fr-pari1.bengt.wasmernet.com";
    $user = "b57f42dd7c53800021d5d5455fa3";
    $pass = "0690b57f-42dd-7f4f-8000-16010189e02d";
    $db   = "gomarikina";
    $port = 10272;
    
    mysqli_report(MYSQLI_REPORT_OFF);
    try {
        $conn = new mysqli($host, $user, $pass, $db, $port);
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
