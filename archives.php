<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_admin();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'restore_report') {
        $reportId = (int) ($_POST['report_id'] ?? 0);

        if ($reportId) {
            try {
                $check = $conn->query("SHOW TABLES LIKE 'reports_archive'");
                if ($check && $check->num_rows > 0) {
                    // Move report back from archive to main table (include lat/lng so map markers are restored)
                    $stmt = $conn->prepare('INSERT INTO reports (id, user_id, title, category, description, location, image_path, latitude, longitude, status, created_at, updated_at) SELECT id, user_id, title, category, description, location, image_path, latitude, longitude, status, created_at, updated_at FROM reports_archive WHERE id = ?');
                    if ($stmt === false) {
                        $_SESSION['archive_feedback'] = 'Failed to prepare restore statement: ' . $conn->error;
                    } else {
                        $stmt->bind_param('i', $reportId);
                        $stmt->execute();
                        $stmt->close();

                        // Remove from archive
                        $stmtDel = $conn->prepare('DELETE FROM reports_archive WHERE id = ?');
                        if ($stmtDel === false) {
                            $_SESSION['archive_feedback'] = 'Failed to prepare delete statement: ' . $conn->error;
                        } else {
                            $stmtDel->bind_param('i', $reportId);
                            $stmtDel->execute();
                            $stmtDel->close();
                            $_SESSION['archive_feedback'] = 'Report restored successfully.';
                        }
                    }
                } else {
                    $_SESSION['archive_feedback'] = 'Archive table not found.';
                }
            } catch (Throwable $e) {
                $_SESSION['archive_feedback'] = 'Failed to restore report: ' . $e->getMessage();
            }
        }
        header('Location: archives.php');
        exit;
    } elseif ($action === 'restore_announcement') {
        $announcementId = (int) ($_POST['announcement_id'] ?? 0);

        if ($announcementId) {
            try {
                $check = $conn->query("SHOW TABLES LIKE 'announcements_archive'");
                $checkMain = $conn->query("SHOW TABLES LIKE 'announcements'");
                if ($check && $check->num_rows > 0 && $checkMain && $checkMain->num_rows > 0) {
                    // Move announcement back from archive to main table
                    $stmt = $conn->prepare('INSERT INTO announcements (id, title, body, image_path, created_at, updated_at) SELECT id, title, body, image_path, created_at, updated_at FROM announcements_archive WHERE id = ?');
                    if ($stmt === false) {
                        $_SESSION['archive_feedback'] = 'Failed to prepare restore statement: ' . $conn->error;
                    } else {
                        $stmt->bind_param('i', $announcementId);
                        $stmt->execute();
                        $stmt->close();

                        // Remove from archive
                        $stmtDel = $conn->prepare('DELETE FROM announcements_archive WHERE id = ?');
                        if ($stmtDel === false) {
                            $_SESSION['archive_feedback'] = 'Failed to prepare delete statement: ' . $conn->error;
                        } else {
                            $stmtDel->bind_param('i', $announcementId);
                            $stmtDel->execute();
                            $stmtDel->close();
                            $_SESSION['archive_feedback'] = 'Announcement restored successfully.';
                        }
                    }
                } else {
                    $_SESSION['archive_feedback'] = 'Archive table not found.';
                }
            } catch (Throwable $e) {
                $_SESSION['archive_feedback'] = 'Failed to restore announcement: ' . $e->getMessage();
            }
        }
        header('Location: archives.php');
        exit;
    } elseif ($action === 'delete_archived_report') {
        $reportId = (int) ($_POST['report_id'] ?? 0);

        if ($reportId) {
            try {
                $check = $conn->query("SHOW TABLES LIKE 'reports_archive'");
                if ($check && $check->num_rows > 0) {
                    // Attempt to delete associated image file first
                    try {
                        $stmtImg = $conn->prepare('SELECT image_path FROM reports_archive WHERE id = ?');
                        if ($stmtImg) {
                            $stmtImg->bind_param('i', $reportId);
                            $stmtImg->execute();
                            $res = $stmtImg->get_result();
                            if ($row = $res->fetch_assoc()) {
                                $img = $row['image_path'] ?? null;
                                if ($img) {
                                    $abs = __DIR__ . '/' . ltrim($img, '/');
                                    if (@is_file($abs)) { @unlink($abs); }
                                }
                            }
                            $stmtImg->close();
                        }
                    } catch (Throwable $ie) { /* ignore file delete errors */ }

                    // Delete row from archive
                    $stmt = $conn->prepare('DELETE FROM reports_archive WHERE id = ?');
                    if ($stmt) {
                        $stmt->bind_param('i', $reportId);
                        $stmt->execute();
                        $stmt->close();
                        $_SESSION['archive_feedback'] = 'Archived report permanently deleted.';
                    } else {
                        $_SESSION['archive_feedback'] = 'Failed to prepare delete statement: ' . $conn->error;
                    }
                } else {
                    $_SESSION['archive_feedback'] = 'Archive table not found.';
                }
            } catch (Throwable $e) {
                $_SESSION['archive_feedback'] = 'Failed to permanently delete report: ' . $e->getMessage();
            }
        }
        header('Location: archives.php');
        exit;
    } elseif ($action === 'delete_archived_announcement') {
        $announcementId = (int) ($_POST['announcement_id'] ?? 0);

        if ($announcementId) {
            try {
                $check = $conn->query("SHOW TABLES LIKE 'announcements_archive'");
                if ($check && $check->num_rows > 0) {
                    // Attempt to delete associated image file first
                    try {
                        $stmtImg = $conn->prepare('SELECT image_path FROM announcements_archive WHERE id = ?');
                        if ($stmtImg) {
                            $stmtImg->bind_param('i', $announcementId);
                            $stmtImg->execute();
                            $res = $stmtImg->get_result();
                            if ($row = $res->fetch_assoc()) {
                                $img = $row['image_path'] ?? null;
                                if ($img) {
                                    $abs = __DIR__ . '/' . ltrim($img, '/');
                                    if (@is_file($abs)) { @unlink($abs); }
                                }
                            }
                            $stmtImg->close();
                        }
                    } catch (Throwable $ie) { /* ignore file delete errors */ }

                    // Delete row from archive
                    $stmt = $conn->prepare('DELETE FROM announcements_archive WHERE id = ?');
                    if ($stmt) {
                        $stmt->bind_param('i', $announcementId);
                        $stmt->execute();
                        $stmt->close();
                        $_SESSION['archive_feedback'] = 'Archived announcement permanently deleted.';
                    } else {
                        $_SESSION['archive_feedback'] = 'Failed to prepare delete statement: ' . $conn->error;
                    }
                } else {
                    $_SESSION['archive_feedback'] = 'Archive table not found.';
                }
            } catch (Throwable $e) {
                $_SESSION['archive_feedback'] = 'Failed to permanently delete announcement: ' . $e->getMessage();
            }
        }
        header('Location: archives.php');
        exit;
    }
}

$archiveFeedback = $_SESSION['archive_feedback'] ?? null;
unset($_SESSION['archive_feedback']);

// Pagination settings
$rpage = max(1, (int)($_GET['rpage'] ?? 1));
$apage = max(1, (int)($_GET['apage'] ?? 1));
$perPage = 10;
$archivedReports = [];
$archivedAnnouncements = [];
$totalArchivedReports = 0;
$totalArchivedAnnouncements = 0;
$totalArchived = 0;
$totalPagesReports = 1;
$totalPagesAnnouncements = 1;

// Load archived reports (paged)
try {
    $check = $conn->query("SHOW TABLES LIKE 'reports_archive'");
    if ($check && $check->num_rows > 0) {
        if ($resCnt = $conn->query('SELECT COUNT(*) AS c FROM reports_archive')) {
            $rowCnt = $resCnt->fetch_assoc();
            $totalArchivedReports = (int)($rowCnt['c'] ?? 0);
            $resCnt->close();
        }
        $totalPagesReports = max(1, (int)ceil($totalArchivedReports / $perPage));
        if ($rpage > $totalPagesReports) { $rpage = $totalPagesReports; }
        $offset = ($rpage - 1) * $perPage;

        $stmt = $conn->prepare("SELECT ra.id, ra.title, ra.category, ra.description, ra.location, ra.image_path, ra.status, ra.created_at, ra.archived_at, ra.archived_by, u.first_name, u.last_name, u.email
                FROM reports_archive ra
                LEFT JOIN users u ON u.id = ra.user_id
                ORDER BY ra.archived_at DESC LIMIT ? OFFSET ?");
        if ($stmt) {
            $stmt->bind_param('ii', $perPage, $offset);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $reporter = 'Resident';
                if (!empty($r['first_name']) || !empty($r['last_name'])) {
                    $reporter = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
                } elseif (!empty($r['email'])) {
                    $reporter = $r['email'];
                }

                $archivedReports[] = [
                    'id' => (int)$r['id'],
                    'title' => $r['title'],
                    'category' => $r['category'],
                    'reporter' => $reporter,
                    'location' => $r['location'],
                    'created_at' => $r['created_at'],
                    'archived_at' => $r['archived_at'],
                    'summary' => $r['description'],
                    'image' => $r['image_path'],
                    'status' => $r['status'],
                ];
            }
            $stmt->close();
        }
    }
} catch (Throwable $e) {
    $archivedReports = [];
}

// Load archived announcements (paged)
try {
    $check = $conn->query("SHOW TABLES LIKE 'announcements_archive'");
    if ($check && $check->num_rows > 0) {
        if ($resCnt = $conn->query('SELECT COUNT(*) AS c FROM announcements_archive')) {
            $rowCnt = $resCnt->fetch_assoc();
            $totalArchivedAnnouncements = (int)($rowCnt['c'] ?? 0);
            $resCnt->close();
        }
        $totalPagesAnnouncements = max(1, (int)ceil($totalArchivedAnnouncements / $perPage));
        if ($apage > $totalPagesAnnouncements) { $apage = $totalPagesAnnouncements; }
        $offsetA = ($apage - 1) * $perPage;

        $stmt = $conn->prepare('SELECT id, title, body, image_path, created_at, archived_at FROM announcements_archive ORDER BY archived_at DESC LIMIT ? OFFSET ?');
        if ($stmt) {
            $stmt->bind_param('ii', $perPage, $offsetA);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($a = $res->fetch_assoc()) {
                $archivedAnnouncements[] = [
                    'id' => (int)$a['id'],
                    'title' => $a['title'],
                    'body' => $a['body'],
                    'image' => $a['image_path'],
                    'created_at' => $a['created_at'],
                    'archived_at' => $a['archived_at'],
                ];
            }
            $stmt->close();
        }
    }
} catch (Throwable $e) {
    $archivedAnnouncements = [];
}

$totalArchived = (int)$totalArchivedReports + (int)$totalArchivedAnnouncements;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archives · GO! MARIKINA</title>
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
                    <p class="admin-kicker">Historical records</p>
                    <h1 class="admin-title">Archives</h1>
                    <p class="admin-subtitle">View all deleted reports and announcements. Archived items are preserved for reference and compliance.</p>
                    <div class="admin-hero-actions">
                        <a class="admin-hero-button" href="admin.php">Back to admin dashboard</a>
                    </div>
                </div>
                <div class="admin-hero-card">
                    <div class="admin-hero-card-header">
                        <span class="admin-badge">Signed in as <?php echo htmlspecialchars(current_user()['email']); ?></span>
                        <a class="admin-logout" href="logout.php">Log out</a>
                    </div>
                    <p class="admin-hero-note">
                        <strong>Total archived items:</strong> <?php echo $totalArchived; ?><br>
                        <span class="admin-hero-meta"><?php echo (int)$totalArchivedReports; ?> reports · <?php echo (int)$totalArchivedAnnouncements; ?> announcements</span>
                    </p>
                </div>
            </header>

            <?php if ($archiveFeedback): ?>
                <div class="admin-feedback" role="status"><?php echo htmlspecialchars($archiveFeedback); ?></div>
            <?php endif; ?>

            <section class="admin-section archives-section" aria-labelledby="archives-heading">
                <div class="admin-section-header">
                    <div>
                        <h2 id="archives-heading">Archived records</h2>
                        <p class="admin-section-subtitle">Switch between reports and announcements using the tabs below.</p>
                    </div>
                    <div class="admin-section-tools">
                        <button type="button" id="archivesFilterToggle" class="filter-toggle" aria-haspopup="true" aria-expanded="false" aria-controls="archivesFilterMenu">
                            <span>Filter</span>
                            <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                                    <path d="M4 5h16M7 12h10m-6 7h2" />
                            </svg>
                        </button>
                        <div class="filter-menu" id="archivesFilterMenu" role="menu" hidden>
                            <div style="display:flex;flex-direction:column;gap:8px;min-width:220px;padding:6px 0;">
                                <div id="reportsFilters">
                                    <div style="font-weight:600;margin-bottom:6px;color:var(--text);">Category</div>
                                    <select id="archiveFilterCategory" class="filter-option" aria-label="Filter by category">
                                        <option value="">All categories</option>
                                        <option value="infrastructure">Infrastructure</option>
                                        <option value="safety">Public Safety</option>
                                        <option value="environment">Environment</option>
                                        <option value="utilities">Utilities</option>
                                        <option value="other">Other</option>
                                    </select>
                                    <div style="font-weight:600;margin:12px 0 6px;color:var(--text);">Status</div>
                                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                        <button type="button" class="filter-option active" data-status="all" role="menuitemradio" aria-checked="true">All</button>
                                        <button type="button" class="filter-option" data-status="unresolved" role="menuitemradio" aria-checked="false">Unresolved</button>
                                        <button type="button" class="filter-option" data-status="in_progress" role="menuitemradio" aria-checked="false">In Progress</button>
                                        <button type="button" class="filter-option" data-status="solved" role="menuitemradio" aria-checked="false">Solved</button>
                                    </div>
                                </div>
                                <div id="announcementsFilters" hidden>
                                    <div style="font-weight:600;margin-bottom:6px;color:var(--text);">Date Range</div>
                                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                        <button type="button" class="filter-option active" data-date="all" role="menuitemradio" aria-checked="true">All Time</button>
                                        <button type="button" class="filter-option" data-date="week" role="menuitemradio" aria-checked="false">Last Week</button>
                                        <button type="button" class="filter-option" data-date="month" role="menuitemradio" aria-checked="false">Last Month</button>
                                        <button type="button" class="filter-option" data-date="year" role="menuitemradio" aria-checked="false">Last Year</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="archives-tabs" role="tablist">
                    <button type="button" class="archives-tab active" role="tab" aria-selected="true" aria-controls="reports-panel" id="reports-tab" data-tab="reports">
                        Reports (<?php echo (int)$totalArchivedReports; ?>)
                    </button>
                    <button type="button" class="archives-tab" role="tab" aria-selected="false" aria-controls="announcements-panel" id="announcements-tab" data-tab="announcements">
                        Announcements (<?php echo (int)$totalArchivedAnnouncements; ?>)
                    </button>
                </div>

                <div class="archives-panels">
                    <!-- Reports Panel -->
                    <div class="archives-panel active" role="tabpanel" id="reports-panel" aria-labelledby="reports-tab">
                        <?php if ($archivedReports): ?>
                            <div class="admin-table-wrapper">
                                <table class="admin-table" id="reportsTable">
                                    <thead>
                                        <tr>
                                            <th scope="col">Title</th>
                                            <th scope="col">Category</th>
                                            <th scope="col">Reporter</th>
                                            <th scope="col">Originally Filed</th>
                                            <th scope="col">Archived On</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($archivedReports as $report): ?>
                                            <tr data-category="<?php echo htmlspecialchars($report['category'], ENT_QUOTES, 'UTF-8'); ?>" data-status="<?php echo htmlspecialchars($report['status'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <td data-title="Title"><?php echo htmlspecialchars($report['title']); ?></td>
                                                <td data-title="Category"><?php echo htmlspecialchars(category_label($report['category'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td data-title="Reporter"><?php echo htmlspecialchars($report['reporter']); ?></td>
                                                <td data-title="Originally Filed"><?php echo htmlspecialchars(format_datetime_display($report['created_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td data-title="Archived On"><?php echo htmlspecialchars(format_datetime_display($report['archived_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td data-title="Status">
                                                    <?php 
                                                    $statusLabel = 'Unresolved';
                                                    if ($report['status'] === 'in_progress') $statusLabel = 'In Progress';
                                                    if ($report['status'] === 'solved') $statusLabel = 'Solved';
                                                    ?>
                                                    <span class="status-badge status-<?php echo htmlspecialchars($report['status'], ENT_QUOTES, 'UTF-8'); ?>">
                                                        <?php echo $statusLabel; ?>
                                                    </span>
                                                </td>
                                                <td data-title="Actions">
                                                    <div style="display:flex;gap:8px;align-items:center;">
                                                        <button type="button" class="archive-view-btn" data-report='<?php echo htmlspecialchars(json_encode($report), ENT_QUOTES, "UTF-8"); ?>' title="View details">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none">
                                                                        <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                                        <path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                                    </svg>
                                                                </button>
                                                        <form method="post" style="display:inline;">
                                                            <input type="hidden" name="action" value="restore_report">
                                                            <input type="hidden" name="report_id" value="<?php echo (int) $report['id']; ?>">
                                                            <button type="submit" class="archive-restore-btn" title="Restore report">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none">
                                                                    <path d="M16.023 9.348h4.992V4.356" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                                    <path d="M20.716 7.05A9 9 0 1 0 6.343 19.707" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                                </svg>
                                                            </button>
                                                        </form>
                                                                <form method="post" style="display:inline;" data-confirm-message="Permanently delete this archived report? This cannot be undone.">
                                                                    <input type="hidden" name="action" value="delete_archived_report">
                                                                    <input type="hidden" name="report_id" value="<?php echo (int) $report['id']; ?>">
                                                                    <button type="submit" class="archive-delete-btn" title="Delete permanently" aria-label="Delete permanently">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
                                                                            <path d="M3 6h18" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                                            <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                                            <path d="M10 11v6M14 11v6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                                        </svg>
                                                                        <span class="visually-hidden">Delete</span>
                                                                    </button>
                                                                </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if ($totalPagesReports > 1):
                                $baseUrl = strtok($_SERVER['REQUEST_URI'], '?');
                                $qs = $_GET; unset($qs['rpage']);
                                $buildUrl = function($p) use ($baseUrl, $qs){ $qs2 = $qs; $qs2['rpage'] = $p; return htmlspecialchars($baseUrl . '?' . http_build_query($qs2), ENT_QUOTES, 'UTF-8'); };
                            ?>
                            <nav class="pager" aria-label="Archived reports pagination">
                                <div class="pager-inner">
                                    <a class="pager-btn" href="<?= $buildUrl(1) ?>" aria-label="First page"<?= $rpage <= 1 ? ' aria-disabled="true" tabindex="-1"' : '' ?>>« First</a>
                                    <a class="pager-btn" href="<?= $buildUrl(max(1, $rpage-1)) ?>" aria-label="Previous page"<?= $rpage <= 1 ? ' aria-disabled="true" tabindex="-1"' : '' ?>>‹ Prev</a>
                                    <span class="pager-info">Page <?= (int)$rpage ?> of <?= (int)$totalPagesReports ?></span>
                                    <a class="pager-btn" href="<?= $buildUrl(min($totalPagesReports, $rpage+1)) ?>" aria-label="Next page"<?= $rpage >= $totalPagesReports ? ' aria-disabled="true" tabindex="-1"' : '' ?>>Next ›</a>
                                    <a class="pager-btn" href="<?= $buildUrl($totalPagesReports) ?>" aria-label="Last page"<?= $rpage >= $totalPagesReports ? ' aria-disabled="true" tabindex="-1"' : '' ?>>Last »</a>
                                </div>
                            </nav>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="admin-empty-card">
                                <h3>No archived reports</h3>
                                <p>Deleted reports will appear here for reference and audit purposes.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Announcements Panel -->
                    <div class="archives-panel" role="tabpanel" id="announcements-panel" aria-labelledby="announcements-tab" hidden>
                        <?php if ($archivedAnnouncements): ?>
                            <div class="archives-announcements-grid" id="announcementsGrid">
                                <?php foreach ($archivedAnnouncements as $announcement): ?>
                                    <article class="archived-announcement-card" data-archived-at="<?php echo htmlspecialchars($announcement['archived_at'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <header class="archived-announcement-header">
                                            <h3><?php echo htmlspecialchars($announcement['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                            <div class="archived-announcement-dates">
                                                <time datetime="<?php echo htmlspecialchars(format_datetime_attr($announcement['created_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?>">
                                                    Published <?php echo htmlspecialchars(format_datetime_display($announcement['created_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?>
                                                </time>
                                                <time datetime="<?php echo htmlspecialchars(format_datetime_attr($announcement['archived_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?>" class="archived-date">
                                                    Archived <?php echo htmlspecialchars(format_datetime_display($announcement['archived_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?>
                                                </time>
                                            </div>
                                        </header>
                                        <div class="archived-announcement-body">
                                            <?php 
                                                // Show only the first 150 characters in the card list
                                                $summary = truncate_text($announcement['body'] ?? '', 150, '…');
                                                echo htmlspecialchars($summary, ENT_QUOTES, 'UTF-8');
                                            ?>
                                        </div>
                                        <footer class="archived-announcement-footer">
                                            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                                                <button type="button" class="archive-view-btn" data-announcement='<?php echo htmlspecialchars(json_encode($announcement), ENT_QUOTES, "UTF-8"); ?>' title="View details">
                                                    View Details
                                                </button>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="action" value="restore_announcement">
                                                    <input type="hidden" name="announcement_id" value="<?php echo (int)$announcement['id']; ?>">
                                                    <button type="submit" class="archive-restore-btn" title="Restore announcement">Restore</button>
                                                </form>
                                                <form method="post" style="display:inline;" data-confirm-message="Permanently delete this archived announcement? This cannot be undone.">
                                                    <input type="hidden" name="action" value="delete_archived_announcement">
                                                    <input type="hidden" name="announcement_id" value="<?php echo (int)$announcement['id']; ?>">
                                                    <button type="submit" class="archive-delete-btn" title="Delete permanently">Delete</button>
                                                </form>
                                            </div>
                                        </footer>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($totalPagesAnnouncements > 1):
                                $baseUrl = strtok($_SERVER['REQUEST_URI'], '?');
                                $qs = $_GET; unset($qs['apage']);
                                $buildUrlA = function($p) use ($baseUrl, $qs){ $qs2 = $qs; $qs2['apage'] = $p; return htmlspecialchars($baseUrl . '?' . http_build_query($qs2), ENT_QUOTES, 'UTF-8'); };
                            ?>
                            <nav class="pager" aria-label="Archived announcements pagination">
                                <div class="pager-inner">
                                    <a class="pager-btn" href="<?= $buildUrlA(1) ?>" aria-label="First page"<?= $apage <= 1 ? ' aria-disabled="true" tabindex="-1"' : '' ?>>« First</a>
                                    <a class="pager-btn" href="<?= $buildUrlA(max(1, $apage-1)) ?>" aria-label="Previous page"<?= $apage <= 1 ? ' aria-disabled="true" tabindex="-1"' : '' ?>>‹ Prev</a>
                                    <span class="pager-info">Page <?= (int)$apage ?> of <?= (int)$totalPagesAnnouncements ?></span>
                                    <a class="pager-btn" href="<?= $buildUrlA(min($totalPagesAnnouncements, $apage+1)) ?>" aria-label="Next page"<?= $apage >= $totalPagesAnnouncements ? ' aria-disabled="true" tabindex="-1"' : '' ?>>Next ›</a>
                                    <a class="pager-btn" href="<?= $buildUrlA($totalPagesAnnouncements) ?>" aria-label="Last page"<?= $apage >= $totalPagesAnnouncements ? ' aria-disabled="true" tabindex="-1"' : '' ?>>Last »</a>
                                </div>
                            </nav>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="admin-empty-card">
                                <h3>No archived announcements</h3>
                                <p>Deleted announcements will be stored here for future reference.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Report View Modal (shared include) -->
    <?php include __DIR__ . '/includes/report_modal.php'; ?>

    <!-- Announcement Details Modal (archives: single announcement) -->
    <div id="announcementDetailsModal" class="modal" hidden>
        <div class="modal-content ann-modal-content" role="dialog" aria-modal="true" aria-labelledby="announcementDetailsTitle">
            <div class="ann-modal-header">
                <div class="ann-modal-title">
                    <p class="ann-modal-kicker">Archived announcement</p>
                    <h2 id="announcementDetailsTitle" data-ann-detail-title>—</h2>
                    <p class="ann-modal-subtitle">
                        <span>Published </span><time data-ann-detail-published>—</time>
                        <span style="margin:0 6px;opacity:.7">·</span>
                        <span>Archived </span><time data-ann-detail-archived>—</time>
                    </p>
                </div>
                <button type="button" class="modal-close" data-ann-detail-close aria-label="Close">
                    <svg viewBox="0 0 24 24" role="presentation" focusable="false" width="24" height="24">
                        <path d="M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="m6 6 12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>
            <div class="ann-modal-body">
                <article class="ann-card" id="announcementDetailsCard">
                    <header class="ann-card__header">
                        <h3 data-ann-detail-title-secondary>—</h3>
                    </header>
                    <figure class="ann-card__media" data-ann-detail-media hidden>
                        <img data-ann-detail-image alt="Announcement image" />
                    </figure>
                    <div class="ann-card__body" data-ann-detail-body>—</div>
                </article>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('.archives-tab');
        const panels = document.querySelectorAll('.archives-panel');
        const filterToggle = document.getElementById('archivesFilterToggle');
        const filterMenu = document.getElementById('archivesFilterMenu');
        const reportsFilters = document.getElementById('reportsFilters');
        const announcementsFilters = document.getElementById('announcementsFilters');

        // Tab switching
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const targetPanel = this.getAttribute('aria-controls');
                const isReportsTab = this.dataset.tab === 'reports';
                
                // Update tabs
                tabs.forEach(t => {
                    t.classList.remove('active');
                    t.setAttribute('aria-selected', 'false');
                });
                this.classList.add('active');
                this.setAttribute('aria-selected', 'true');

                // Update panels
                panels.forEach(p => {
                    p.classList.remove('active');
                    p.hidden = true;
                });
                const panel = document.getElementById(targetPanel);
                if (panel) {
                    panel.classList.add('active');
                    panel.hidden = false;
                }

                // Switch filter options
                if (reportsFilters && announcementsFilters) {
                    reportsFilters.hidden = !isReportsTab;
                    announcementsFilters.hidden = isReportsTab;
                }
            });
        });

        // Filter menu toggle
        if (filterToggle && filterMenu) {
            filterToggle.addEventListener('click', function(e) {
                e.preventDefault();
                const expanded = filterToggle.getAttribute('aria-expanded') === 'true';
                filterMenu.hidden = expanded;
                filterToggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            });

            // Close when clicking outside
            document.addEventListener('click', function(e) {
                if (filterMenu.hidden) return;
                if (filterMenu.contains(e.target) || filterToggle.contains(e.target)) return;
                filterMenu.hidden = true;
                filterToggle.setAttribute('aria-expanded', 'false');
            });
        }

        // Reports filtering
        const reportsTable = document.getElementById('reportsTable');
        const categorySelect = document.getElementById('archiveFilterCategory');
        const statusButtons = document.querySelectorAll('#reportsFilters [data-status]');

        function applyReportsFilter() {
            if (!reportsTable) return;
            const selectedCategory = categorySelect?.value || '';
            const activeStatusBtn = Array.from(statusButtons).find(b => b.classList.contains('active'));
            const selectedStatus = activeStatusBtn ? (activeStatusBtn.dataset.status || 'all') : 'all';

            const rows = reportsTable.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const rowCategory = row.dataset.category || '';
                const rowStatus = row.dataset.status || '';
                let show = true;
                if (selectedCategory && rowCategory !== selectedCategory) show = false;
                if (selectedStatus && selectedStatus !== 'all' && rowStatus !== selectedStatus) show = false;
                row.style.display = show ? '' : 'none';
            });
        }

        categorySelect?.addEventListener('change', applyReportsFilter);
        statusButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                statusButtons.forEach(b => {
                    b.classList.remove('active');
                    b.setAttribute('aria-checked', 'false');
                });
                btn.classList.add('active');
                btn.setAttribute('aria-checked', 'true');
                applyReportsFilter();
            });
        });

        // Announcements filtering
        const announcementsGrid = document.getElementById('announcementsGrid');
        const dateButtons = document.querySelectorAll('#announcementsFilters [data-date]');

        function applyAnnouncementsFilter() {
            if (!announcementsGrid) return;
            const activeDateBtn = Array.from(dateButtons).find(b => b.classList.contains('active'));
            const selectedDate = activeDateBtn ? (activeDateBtn.dataset.date || 'all') : 'all';

            const cards = announcementsGrid.querySelectorAll('.archived-announcement-card');
            const now = new Date();
            
            cards.forEach(card => {
                const archivedAt = new Date(card.dataset.archivedAt);
                let show = true;

                if (selectedDate === 'week') {
                    const weekAgo = new Date(now);
                    weekAgo.setDate(weekAgo.getDate() - 7);
                    show = archivedAt >= weekAgo;
                } else if (selectedDate === 'month') {
                    const monthAgo = new Date(now);
                    monthAgo.setMonth(monthAgo.getMonth() - 1);
                    show = archivedAt >= monthAgo;
                } else if (selectedDate === 'year') {
                    const yearAgo = new Date(now);
                    yearAgo.setFullYear(yearAgo.getFullYear() - 1);
                    show = archivedAt >= yearAgo;
                }

                card.style.display = show ? '' : 'none';
            });
        }

        dateButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                dateButtons.forEach(b => {
                    b.classList.remove('active');
                    b.setAttribute('aria-checked', 'false');
                });
                btn.classList.add('active');
                btn.setAttribute('aria-checked', 'true');
                applyAnnouncementsFilter();
            });
        });

        // Archive page: use shared report modal
        const reportModal = document.getElementById('reportModal');
        const modalDialog = reportModal ? reportModal.querySelector('.report-modal__dialog') : null;
        const modalBackdrop = reportModal ? reportModal.querySelector('[data-report-modal-backdrop]') : null;
        const modalCloseButtons = Array.from(reportModal ? reportModal.querySelectorAll('[data-report-modal-close]') : []);
        const mTitle = reportModal ? reportModal.querySelector('[data-report-modal-title]') : null;
        const mSubmitted = reportModal ? reportModal.querySelector('[data-report-modal-submitted]') : null;
        const mReporter = reportModal ? reportModal.querySelector('[data-report-modal-reporter]') : null;
        const mLocation = reportModal ? reportModal.querySelector('[data-report-modal-location]') : null;
        const mCategory = reportModal ? reportModal.querySelector('[data-report-modal-category]') : null;
        const mStatus = reportModal ? reportModal.querySelector('[data-report-modal-status]') : null;
        const mSummary = reportModal ? reportModal.querySelector('[data-report-modal-summary]') : null;
        const mMedia = reportModal ? reportModal.querySelector('[data-report-modal-media]') : null;
        const mImage = reportModal ? reportModal.querySelector('[data-report-modal-image]') : null;
        const mPlaceholder = reportModal ? reportModal.querySelector('[data-report-modal-placeholder]') : null;
        const mActions = reportModal ? reportModal.querySelector('[data-report-modal-actions]') : null;
        const mOpenFull = reportModal ? reportModal.querySelector('[data-report-open-full]') : null;
        const mDownload = reportModal ? reportModal.querySelector('[data-report-download]') : null;
        const mViewer = reportModal ? reportModal.querySelector('[data-report-image-viewer]') : null;
        const mViewerImg = reportModal ? reportModal.querySelector('[data-report-viewer-image]') : null;
        const mViewerClose = reportModal ? reportModal.querySelector('[data-report-viewer-close]') : null;

        function applyStatusChip(el, status) {
            if (!el) return;
            const st = String(status || '').toLowerCase();
            const label = (st === 'in_progress' || st === 'in-progress') ? 'In progress' : (st === 'solved' || st === 'resolved' ? 'Solved' : 'Unresolved');
            const modifier = (st === 'in_progress' || st === 'in-progress') ? 'in_progress' : (st === 'solved' || st === 'resolved' ? 'solved' : 'unresolved');
            el.textContent = label;
            el.classList.remove('unresolved','in_progress','solved');
            el.classList.add(modifier);
        }

        function updateMedia(imageUrl, titleText) {
            if (!mImage || !mPlaceholder || !mMedia) return;
            const hasImage = !!imageUrl;
            mMedia.classList.toggle('has-image', hasImage);
            mMedia.classList.toggle('no-image', !hasImage);
            if (mActions) mActions.hidden = !hasImage;

            if (mImage._onloadHandler) mImage.removeEventListener('load', mImage._onloadHandler);
            if (mImage._onerrorHandler) mImage.removeEventListener('error', mImage._onerrorHandler);

            if (hasImage) {
                mImage._onloadHandler = function(){
                    mImage.hidden = false;
                    mPlaceholder.hidden = true;
                    mPlaceholder.style.display = 'none';
                    if (modalDialog && mImage.naturalWidth && mImage.naturalHeight) {
                        const isPortrait = (mImage.naturalHeight / mImage.naturalWidth) > 1.05;
                        modalDialog.classList.toggle('report-modal--portrait-image', isPortrait);
                    }
                };
                mImage._onerrorHandler = function(){
                    mImage.removeAttribute('src');
                    mImage.hidden = true;
                    mPlaceholder.hidden = false;
                    mPlaceholder.style.removeProperty('display');
                    if (modalDialog) modalDialog.classList.remove('report-modal--portrait-image');
                };
                mImage.addEventListener('load', mImage._onloadHandler, { once: true });
                mImage.addEventListener('error', mImage._onerrorHandler, { once: true });

                mImage.src = imageUrl;
                mImage.alt = (titleText || 'Report') + ' photo';
                if (mImage.complete && mImage.naturalWidth) {
                    mImage.hidden = false;
                    mPlaceholder.hidden = true;
                    mPlaceholder.style.display = 'none';
                }
            } else {
                mImage.removeAttribute('src');
                mImage.hidden = true;
                mPlaceholder.hidden = false;
                mPlaceholder.style.removeProperty('display');
                if (modalDialog) modalDialog.classList.remove('report-modal--portrait-image');
            }
        }

        function formatTo12hDisplayLocal(input) {
            try {
                if (typeof window.formatTo12hDisplay === 'function') return window.formatTo12hDisplay(input);
                if (!input) return '—';
                var s = String(input).trim();
                if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/.test(s)) { s = s.replace(' ', 'T'); }
                var d = new Date(s);
                if (isNaN(d.getTime())) return s || '—';
                var formatted = d.toLocaleString('en-US', { year:'numeric', month:'short', day:'numeric', hour:'numeric', minute:'2-digit', hour12:true });
                return formatted.replace(/,\s(?=\d)/, ' · ');
            } catch (e) { return input || '—'; }
        }

        function openArchiveSharedModal(data){
            if (!reportModal) return;
            if (mTitle) mTitle.textContent = data.title || 'Archived Report';
            if (mSubmitted) mSubmitted.textContent = formatTo12hDisplayLocal(data.submitted_at || data.created_at || '');
            if (mReporter) mReporter.textContent = data.reporter || '—';
            if (mLocation) mLocation.textContent = data.location || '—';
            if (mCategory) {
                const cat = data.category || '';
                mCategory.textContent = cat ? (cat.charAt(0).toUpperCase() + cat.slice(1)) : 'Category';
                mCategory.hidden = !cat;
            }
            applyStatusChip(mStatus, data.status);
            if (mSummary) mSummary.textContent = data.summary || data.description || 'No description provided.';
            updateMedia(data.image || data.image_path || '', data.title);

            reportModal.removeAttribute('hidden');
            document.body.classList.add('modal-open');
            reportModal.classList.add('is-open');
            if (modalDialog && typeof modalDialog.focus === 'function') { modalDialog.focus({ preventScroll: true }); }
        }

        function closeArchiveSharedModal(){
            if (!reportModal) return;
            reportModal.classList.remove('is-open');
            reportModal.setAttribute('hidden','hidden');
            document.body.classList.remove('modal-open');
            if (mImage) mImage.removeAttribute('src');
            if (modalDialog) modalDialog.classList.remove('report-modal--portrait-image');
        }

        function openImageViewer(){
            if (!mViewer || !mViewerImg || !mImage) return;
            const src = mImage.getAttribute('src');
            if (!src) return;
            mViewerImg.src = src;
            mViewerImg.alt = (mTitle && mTitle.textContent) || 'Report image';
            mViewer.classList.add('open');
            mViewer.removeAttribute('hidden');
        }
        function closeImageViewer(){
            if (!mViewer || !mViewerImg) return;
            mViewer.classList.remove('open');
            mViewer.setAttribute('hidden','hidden');
            mViewerImg.removeAttribute('src');
        }
        if (mOpenFull) mOpenFull.addEventListener('click', openImageViewer);
        if (mViewerClose) mViewerClose.addEventListener('click', closeImageViewer);
        if (mViewer) mViewer.addEventListener('click', function(e){ if (e.target === mViewer) closeImageViewer(); });
        if (mDownload) {
            mDownload.addEventListener('click', function(){
                const src = mImage && mImage.getAttribute ? mImage.getAttribute('src') : null;
                if (!src) return;
                const a = document.createElement('a');
                a.href = src;
                var titleText = (mTitle && mTitle.textContent) || 'report-image';
                a.download = titleText.replace(/\s+/g,'-').toLowerCase() + '.jpg';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            });
        }
        if (modalBackdrop) modalBackdrop.addEventListener('click', closeArchiveSharedModal);
        modalCloseButtons.forEach(b => b.addEventListener('click', closeArchiveSharedModal));
        if (reportModal) reportModal.addEventListener('keydown', function(e){ if (e.key==='Escape'){ e.preventDefault(); if (mViewer && mViewer.classList.contains('open')) { closeImageViewer(); return; } closeArchiveSharedModal(); }});

        // === Announcement details modal (archives) ===
        const annModal = document.getElementById('announcementDetailsModal');
        const annTitle = annModal ? annModal.querySelector('[data-ann-detail-title]') : null;
        const annTitleSecondary = annModal ? annModal.querySelector('[data-ann-detail-title-secondary]') : null;
        const annPublished = annModal ? annModal.querySelector('[data-ann-detail-published]') : null;
        const annArchived = annModal ? annModal.querySelector('[data-ann-detail-archived]') : null;
        const annBody = annModal ? annModal.querySelector('[data-ann-detail-body]') : null;
        const annMedia = annModal ? annModal.querySelector('[data-ann-detail-media]') : null;
        const annImage = annModal ? annModal.querySelector('[data-ann-detail-image]') : null;
        const annCloseBtn = annModal ? annModal.querySelector('[data-ann-detail-close]') : null;

        function openAnnouncementDetails(data) {
            if (!annModal) return;
            // Populate
            const title = data.title || 'Announcement';
            if (annTitle) annTitle.textContent = title;
            if (annTitleSecondary) annTitleSecondary.textContent = title;
            if (annPublished) annPublished.textContent = (typeof formatTo12hDisplayLocal === 'function') ? formatTo12hDisplayLocal(data.created_at) : (data.created_at || '—');
            if (annArchived) annArchived.textContent = (typeof formatTo12hDisplayLocal === 'function') ? formatTo12hDisplayLocal(data.archived_at) : (data.archived_at || '—');
            if (annBody) annBody.textContent = data.body || '';

            // Image handling
            const hasImg = !!(data.image);
            if (annMedia) annMedia.hidden = !hasImg;
            if (annImage) {
                if (hasImg) {
                    annImage.src = data.image;
                    annImage.alt = title + ' image';
                } else {
                    annImage.removeAttribute('src');
                    annImage.alt = '';
                }
            }

            // Open modal
            annModal.hidden = false;
            document.body.classList.add('modal-open');
            setTimeout(() => annModal.setAttribute('open',''), 10);
        }

        function closeAnnouncementDetails() {
            if (!annModal) return;
            annModal.removeAttribute('open');
            document.body.classList.remove('modal-open');
            setTimeout(() => { annModal.hidden = true; }, 250);
        }

        if (annCloseBtn) annCloseBtn.addEventListener('click', closeAnnouncementDetails);
        if (annModal) annModal.addEventListener('click', (e) => { if (e.target === annModal) closeAnnouncementDetails(); });
        // View buttons in archived table
        Array.prototype.forEach.call(document.querySelectorAll('.archive-view-btn'), function(btn){
            btn.addEventListener('click', function(){
                var dataStr = btn.getAttribute('data-report');
                var annStr = btn.getAttribute('data-announcement');
                if (dataStr) {
                    try {
                        var report = JSON.parse(dataStr);
                        var data = {
                            title: report.title || 'Archived Report',
                            category: report.category || '',
                            reporter: report.reporter || '—',
                            submitted_at: report.created_at || '—',
                            status: report.status || 'unresolved',
                            location: report.location || '—',
                            image: report.image || report.image_path || '',
                            summary: report.summary || report.description || ''
                        };
                        openArchiveSharedModal(data);
                    } catch (e) { try { console.error('Failed to parse report data', e); } catch(_) {} }
                } else if (annStr) {
                    try {
                        var announcement = JSON.parse(annStr);
                        openAnnouncementDetails(announcement);
                    } catch (e) { try { console.error('Failed to parse announcement data', e); } catch(_) {} }
                }
            });
        });
    });
    </script>
    <script src="assets/js/script.js" defer></script>
</body>
</html>
