<?php
// Simple DB health check page for local dev. Includes `config/db.php` which
// will either establish a connection or exit with a friendly message.
// Open this page in your browser to verify the application can connect.
header('Content-Type: text/plain; charset=utf-8');
echo "DB test started\n";
try {
    require_once __DIR__ . '/config/db.php';
    if (isset($conn) && $conn instanceof mysqli && $conn->ping()) {
        echo "OK: Connected to database ({$conn->host_info})\n";
    } else {
        echo "FAIL: DB connection object not available or ping failed.\n";
    }
} catch (Throwable $e) {
    // config/db.php may call die() with a friendly message; catch any throwables too.
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}
echo "Done.\n";

?>
