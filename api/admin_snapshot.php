<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';

try {
    $total = 0; $unresolved = 0; $in_progress = 0; $solved = 0;

    $check = $conn->query("SHOW TABLES LIKE 'reports'");
    if ($check && $check->num_rows > 0) {
        if ($resCnt = $conn->query('SELECT COUNT(*) AS c FROM reports')) {
            $rowCnt = $resCnt->fetch_assoc();
            $total = (int)($rowCnt['c'] ?? 0);
            $resCnt->close();
        }
        if ($res = $conn->query("SELECT status, COUNT(*) AS c FROM reports GROUP BY status")) {
            while ($row = $res->fetch_assoc()) {
                $s = strtolower((string)($row['status'] ?? ''));
                $c = (int)($row['c'] ?? 0);
                if ($s === 'unresolved') $unresolved = $c;
                elseif ($s === 'in_progress') $in_progress = $c;
                elseif ($s === 'solved') $solved = $c;
            }
            $res->close();
        }
    } else {
        // Fallback to session reports if present (optional, best-effort)
        $reports = $_SESSION['reports'] ?? [];
        if (is_array($reports)) {
            $total = count($reports);
            foreach ($reports as $r) {
                $s = strtolower((string)($r['status'] ?? 'unresolved'));
                if ($s === 'solved') $solved++; elseif ($s === 'in_progress') $in_progress++; else $unresolved++;            }
        }
    }

    $pct = function(int $num, int $den): int { return $den > 0 ? (int)round(($num / $den) * 100) : 0; };
    $rates = [
        'open' => $pct($unresolved, $total),
        'in_progress' => $pct($in_progress, $total),
        'solved' => $pct($solved, $total),
    ];

    json_ok([
        'total' => $total,
        'counts' => [
            'unresolved' => $unresolved,
            'in_progress' => $in_progress,
            'solved' => $solved,
        ],
        'rates' => $rates,
    ]);
} catch (Throwable $e) {
    json_error('Failed to load snapshot: ' . $e->getMessage(), 500);
}
