<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';

$debugOn = (getenv('DEBUG') === '1') || (($_GET['debug'] ?? '0') === '1') || (($_SERVER['HTTP_X_DEBUG'] ?? '0') === '1');
if (!$debugOn) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Diagnostics disabled. Append ?debug=1 to enable.']);
    exit;
}

function table_exists(mysqli $conn, string $name): bool {
    try {
        $res = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($name) . "'");
        $ok = $res && $res->num_rows > 0;
        if ($res) $res->close();
        return $ok;
    } catch (Throwable $e) { return false; }
}

function columns(mysqli $conn, string $name): array {
    $cols = [];
    try {
        $res = $conn->query("SHOW COLUMNS FROM `" . $conn->real_escape_string($name) . "`");
        if ($res) {
            while ($row = $res->fetch_assoc()) { $cols[] = $row['Field']; }
            $res->close();
        }
    } catch (Throwable $e) {}
    return $cols;
}

function indexes(mysqli $conn, string $name): array {
    $idx = [];
    try {
        $res = $conn->query("SHOW INDEXES FROM `" . $conn->real_escape_string($name) . "`");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $key = $row['Key_name'];
                if (!isset($idx[$key])) $idx[$key] = [];
                $idx[$key][] = $row['Column_name'];
            }
            $res->close();
        }
    } catch (Throwable $e) {}
    return $idx;
}

$out = [
    'success' => true,
    'db_ok' => false,
    'tables' => [],
];

try {
    $out['db_ok'] = isset($conn) && ($conn instanceof mysqli);
    if ($out['db_ok']) {
        foreach (['users','reports','reports_archive','notifications','announcements'] as $t) {
            $exists = table_exists($conn, $t);
            $out['tables'][$t] = [
                'exists' => $exists,
                'columns' => $exists ? columns($conn, $t) : [],
                'indexes' => $exists ? indexes($conn, $t) : [],
            ];
        }
    }
} catch (Throwable $e) {
    $out['error'] = $e->getMessage();
}

echo json_encode($out);
