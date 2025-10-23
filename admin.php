<?php
require __DIR__ . '/config/auth.php';
require __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_admin();

// Non-JS fallback: allow status updates/deletes via POST to this page
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $reportId = (int)($_POST['report_id'] ?? 0);
        $status = $_POST['status'] ?? 'unresolved';
        $validStatuses = ['unresolved', 'in_progress', 'solved'];
        if ($reportId && in_array($status, $validStatuses, true)) {
            $stmt = $conn->prepare('UPDATE reports SET status = ?, updated_at = NOW() WHERE id = ?');
            $stmt->bind_param('si', $status, $reportId);
            $stmt->execute();
            $stmt->close();
            $_SESSION['admin_feedback'] = 'Report status updated.';
        }
    } elseif ($action === 'delete_report') {
        $reportId = (int)($_POST['report_id'] ?? 0);
        if ($reportId) {
            $stmt = $conn->prepare('DELETE FROM reports WHERE id = ?');
            $stmt->bind_param('i', $reportId);
            $stmt->execute();
            $stmt->close();
            $_SESSION['admin_feedback'] = 'Report deleted.';
        }
    }

    header('Location: admin.php');
    exit;
}

// Load reports from DB
$reports = [];
$feedback = $_SESSION['admin_feedback'] ?? null;
unset($_SESSION['admin_feedback']);

try {
    $res = $conn->query('SELECT id, title, category, description, location, image_path, status, created_at FROM reports ORDER BY created_at DESC LIMIT 500');
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $reports[] = [
                'id' => (int)$r['id'],
                'title' => $r['title'],
                'category' => $r['category'],
                'reporter' => 'Resident',
                'location' => $r['location'],
                'submitted_at' => $r['created_at'],
                'summary' => $r['description'],
                'image' => $r['image_path'],
                'status' => $r['status'],
            ];
        }
    }
} catch (Throwable $e) {
    $reports = [];
}

$totalReports = count($reports);
$statusCounts = [
    'unresolved' => 0,
    'in_progress' => 0,
    'solved' => 0,
];

foreach ($reports as $report) {
    $status = $report['status'] ?? 'unresolved';
    if (isset($statusCounts[$status])) {
        $statusCounts[$status]++;
    }
}

$resolvedRate = $totalReports > 0
    ? round(($statusCounts['solved'] / $totalReports) * 100)
    : 0;
$openRate = $totalReports > 0
    ? round(($statusCounts['unresolved'] / $totalReports) * 100)
    : 0;
$inProgressRate = $totalReports > 0
    ? round(($statusCounts['in_progress'] / $totalReports) * 100)
    : 0;

$latestReport = $reports[0] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel · GO! MARIKINA</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body id="top">
    <div class="dashboard-layout admin-layout">
        <button type="button" class="mobile-nav-toggle" data-nav-toggle aria-controls="primary-sidebar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="mobile-nav-toggle-bars" aria-hidden="true"></span>
        </button>
        <?php include './includes/navbar.php'; ?>
        <div class="mobile-nav-scrim" data-nav-scrim hidden></div>

        <main class="dashboard-main admin-main" id="main-content">
            <header class="admin-hero">
                <div class="admin-hero-text">
                    <p class="admin-kicker">City operations command</p>
                    <h1 class="admin-title">Administrator Dashboard</h1>
                    <p class="admin-subtitle">Coordinate citizen concerns, route tasks to the field, and monitor progress without leaving this view.</p>
                    <div class="admin-hero-actions">
                        <a class="admin-hero-button admin-hero-button--primary" href="#reports">Review reports</a>
                        <a class="admin-hero-button" href="announcements.php">Manage announcements</a>
                    </div>
                </div>
                <div class="admin-hero-card">
                    <div class="admin-hero-card-header">
                        <span class="admin-badge">Signed in as <?php echo htmlspecialchars(current_user()['email']); ?></span>
                        <a class="admin-logout" href="logout.php">Log out</a>
                    </div>
                    <p class="admin-hero-note">
                        <?php if ($latestReport): ?>
                            Latest submission&nbsp;<strong><?php echo htmlspecialchars($latestReport['title']); ?></strong><br>
                            <span class="admin-hero-meta">Filed <?php echo htmlspecialchars(format_datetime_display($latestReport['submitted_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?> by <?php echo htmlspecialchars($latestReport['reporter']); ?></span>
                        <?php else: ?>
                            You're all caught up — no citizen reports yet.
                        <?php endif; ?>
                    </p>
                    <div class="admin-hero-chip-group">
                        <span class="admin-hero-chip" aria-label="Open report percentage"><?php echo $openRate; ?>% open</span>
                        <span class="admin-hero-chip admin-hero-chip--accent" aria-label="Resolved report percentage"><?php echo $resolvedRate; ?>% resolved</span>
                    </div>
                </div>
            </header>

            <?php if ($feedback): ?>
                <div class="admin-feedback" role="status"><?php echo htmlspecialchars($feedback); ?></div>
            <?php endif; ?>

            <section class="admin-section admin-summary" aria-label="Report overview">
                <div class="admin-section-header">
                    <div>
                        <h2>Operations snapshot</h2>
                        <p class="admin-section-subtitle">Monitor workload at a glance and rebalance assignments fast.</p>
                    </div>
                    <span class="admin-count">Updated live from recent submissions</span>
                </div>
                <div class="admin-summary-grid">
                    <article class="admin-summary-card admin-summary-card--total">
                        <span class="admin-summary-label">Total reports</span>
                        <h3 class="admin-summary-value"><?php echo $totalReports; ?></h3>
                        <p class="admin-summary-note">Across every category logged in the system.</p>
                    </article>
                    <article class="admin-summary-card admin-summary-card--open">
                        <span class="admin-summary-label">Awaiting triage</span>
                        <h3 class="admin-summary-value"><?php echo $statusCounts['unresolved']; ?></h3>
                        <div class="admin-summary-meter" role="presentation">
                            <span class="admin-summary-meter-fill" style="width: <?php echo $openRate; ?>%;"></span>
                        </div>
                        <p class="admin-summary-note"><?php echo $openRate; ?>% of all requests are still unresolved.</p>
                    </article>
                    <article class="admin-summary-card admin-summary-card--progress">
                        <span class="admin-summary-label">In progress</span>
                        <h3 class="admin-summary-value"><?php echo $statusCounts['in_progress']; ?></h3>
                        <div class="admin-summary-meter" role="presentation">
                            <span class="admin-summary-meter-fill" style="width: <?php echo $inProgressRate; ?>%;"></span>
                        </div>
                        <p class="admin-summary-note"><?php echo $inProgressRate; ?>% have teams presently dispatched.</p>
                    </article>
                    <article class="admin-summary-card admin-summary-card--resolved">
                        <span class="admin-summary-label">Resolved</span>
                        <h3 class="admin-summary-value"><?php echo $statusCounts['solved']; ?></h3>
                        <div class="admin-summary-meter" role="presentation">
                            <span class="admin-summary-meter-fill" style="width: <?php echo $resolvedRate; ?>%;"></span>
                        </div>
                        <p class="admin-summary-note">Resolution rate holding at <?php echo $resolvedRate; ?>%.</p>
                    </article>
                </div>
            </section>

            <section class="admin-section admin-reports" aria-labelledby="reports-heading" id="reports">
                <div class="admin-section-header">
                    <div>
                        <h2 id="reports-heading">Live report queue</h2>
                        <p class="admin-section-subtitle">Update statuses as field teams respond and keep residents informed.</p>
                    </div>
                    <div class="admin-section-tools" aria-hidden="true">
                        <span class="admin-chip admin-chip--open">Unresolved</span>
                        <span class="admin-chip admin-chip--progress">In progress</span>
                        <span class="admin-chip admin-chip--resolved">Solved</span>
                    </div>
                </div>

                <?php if ($reports): ?>
                    <div class="admin-table-wrapper">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th scope="col">Title</th>
                                    <th scope="col">Category</th>
                                    <th scope="col">Reporter</th>
                                    <th scope="col">Submitted</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reports as $report): ?>
                                    <tr>
                                        <td data-title="Title"><?php echo htmlspecialchars($report['title']); ?></td>
                                        <td data-title="Category"><?php echo htmlspecialchars(category_label($report['category'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td data-title="Reporter"><?php echo htmlspecialchars($report['reporter']); ?></td>
                                        <td data-title="Submitted"><?php echo htmlspecialchars(format_datetime_display($report['submitted_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td data-title="Status">
                                            <form method="post" class="admin-inline-form" aria-label="Update status for <?php echo htmlspecialchars($report['title']); ?>">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="report_id" value="<?php echo (int) $report['id']; ?>">
                                                <label class="admin-select-wrapper">
                                                    <span class="visually-hidden">Select status</span>
                                                    <select name="status" class="admin-select">
                                                        <option value="unresolved"<?php if ($report['status'] === 'unresolved') echo ' selected'; ?>>Unresolved</option>
                                                        <option value="in_progress"<?php if ($report['status'] === 'in_progress') echo ' selected'; ?>>In Progress</option>
                                                        <option value="solved"<?php if ($report['status'] === 'solved') echo ' selected'; ?>>Solved</option>
                                                    </select>
                                                </label>
                                            </form>
                                        </td>
                                        <td data-title="Actions">
                                            <form method="post" class="admin-inline-form" data-confirm-message="Delete this report?">
                                                <input type="hidden" name="action" value="delete_report">
                                                <input type="hidden" name="report_id" value="<?php echo (int) $report['id']; ?>">
                                                <button type="submit" class="admin-delete">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="admin-empty-card">
                        <h3>No reports yet</h3>
                        <p>Once residents submit issues through the mobile app, they'll appear here for triage.</p>
                    </div>
                <?php endif; ?>

                <footer class="admin-section-footer">
                    <p><strong>Tip:</strong> Need to update residents? Publish a notice from the <a href="announcements.php">Announcements workspace</a>.</p>
                </footer>
            </section>
        </main>
    </div>
    <script src="assets/js/script.js" defer></script>
</body>
</html>
