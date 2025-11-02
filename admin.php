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
            // Archive the report row first, preserving original data including lat/lng
            try {
                $archiver = (int)(current_user()['id'] ?? 0);
                $stmtA = $conn->prepare('INSERT INTO reports_archive (id, user_id, title, category, description, location, image_path, latitude, longitude, status, created_at, updated_at, archived_at, archived_by) SELECT id, user_id, title, category, description, location, image_path, latitude, longitude, status, created_at, updated_at, NOW(), ? FROM reports WHERE id = ?');
                $stmtA->bind_param('ii', $archiver, $reportId);
                $stmtA->execute();
                $stmtA->close();
            } catch (Throwable $e) {
                // If archiving fails, continue with delete but log feedback
            }

            $stmt = $conn->prepare('DELETE FROM reports WHERE id = ?');
            $stmt->bind_param('i', $reportId);
            $stmt->execute();
            $stmt->close();
            $_SESSION['admin_feedback'] = 'Report removed and archived.';
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
    // Join with users to show reporter name/email to admins when available
    $sql = "SELECT r.id, r.title, r.category, r.description, r.location, r.image_path, r.status, r.created_at, r.user_id, u.first_name, u.last_name, u.email
            FROM reports r
            LEFT JOIN users u ON u.id = r.user_id
            ORDER BY r.created_at DESC LIMIT 500";
    $res = $conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $reporter = 'Resident';
            if (!empty($r['first_name']) || !empty($r['last_name'])) {
                $reporter = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
            } elseif (!empty($r['email'])) {
                $reporter = $r['email'];
            }

            $reports[] = [
                'id' => (int)$r['id'],
                'title' => $r['title'],
                'category' => $r['category'],
                'reporter' => $reporter,
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

// Build a unique list of categories for the admin filter menu
$categories = [];
foreach ($reports as $rpt) {
    $lbl = category_label($rpt['category'] ?? '');
    if ($lbl === '') continue;
    if (!in_array($lbl, $categories, true)) $categories[] = $lbl;
}
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
                    <div class="admin-section-tools">
                        <div class="reports-filter">
                            <button type="button" id="adminReportFilterToggle" class="filter-toggle" aria-haspopup="true" aria-expanded="false" aria-controls="adminReportFilterMenu">
                                <span>Filter</span>
                                <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                                    <path d="M4 5h16M7 12h10m-6 7h2" />
                                </svg>
                            </button>

                            <div class="filter-menu" id="adminReportFilterMenu" role="menu" hidden>
                                <div style="display:flex;flex-direction:column;gap:8px;min-width:220px;padding:6px 0;">
                                    <div>
                                        <div style="font-weight:600;margin-bottom:6px;color:var(--text);">Category</div>
                                        <select id="adminFilterCategory" class="filter-option" aria-label="Filter by category">
                                            <option value="">All categories</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div>
                                        <div style="font-weight:600;margin-bottom:6px;color:var(--text);">Submitted</div>
                                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                            <button type="button" class="filter-option" data-sort="newest">Newest</button>
                                            <button type="button" class="filter-option" data-sort="oldest">Oldest</button>
                                        </div>
                                    </div>

                                    <div>
                                        <div style="font-weight:600;margin-bottom:6px;color:var(--text);">Status</div>
                                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                            <button type="button" class="filter-option active" data-status="all" role="menuitemradio" aria-checked="true">All</button>
                                            <button type="button" class="filter-option" data-status="unresolved" role="menuitemradio" aria-checked="false">Unresolved</button>
                                            <button type="button" class="filter-option" data-status="in_progress" role="menuitemradio" aria-checked="false">In Progress</button>
                                            <button type="button" class="filter-option" data-status="solved" role="menuitemradio" aria-checked="false">Solved</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                                    <tr data-status="<?php echo htmlspecialchars($report['status'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-submitted="<?php echo htmlspecialchars($report['submitted_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
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
                                            <button
                                                type="button"
                                                class="admin-view-report admin-view-btn"
                                                title="View report"
                                                aria-label="View report"
                                                data-title="<?php echo htmlspecialchars($report['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                data-category="<?php echo htmlspecialchars($report['category'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                data-reporter="<?php echo htmlspecialchars($report['reporter'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                data-submitted="<?php echo htmlspecialchars(format_datetime_display($report['submitted_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?>"
                                                data-status="<?php echo htmlspecialchars($report['status'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                data-location="<?php echo htmlspecialchars($report['location'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                data-image="<?php echo htmlspecialchars($report['image'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                data-summary="<?php echo htmlspecialchars($report['summary'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
                                                    <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                <span>View</span>
                                            </button>
                                            <form method="post" class="admin-inline-form" data-confirm-message="Delete this report?" style="display:inline;">
                                                <input type="hidden" name="action" value="delete_report">
                                                <input type="hidden" name="report_id" value="<?php echo (int) $report['id']; ?>">
                                                <button type="submit" class="admin-delete" aria-label="Delete report">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
                                                        <path d="M3 6h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                        <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                        <path d="M10 11v6M14 11v6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                    </svg>
                                                    <span>Delete</span>
                                                </button>
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
    <!-- Report View Modal (shared include) -->
    <?php include __DIR__ . '/includes/report_modal.php'; ?>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function(){
        try { console.log('[Admin] DOM ready: wiring report modal'); } catch(_) {}
        // Modal logic using shared report modal design
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

            // Helper: apply orientation-based layout classes on the dialog
            function applyOrientation() {
                if (!modalDialog || !mImage || !mImage.naturalWidth || !mImage.naturalHeight) return;
                const ratio = mImage.naturalHeight / mImage.naturalWidth;
                const isPortrait = ratio > 1.05; // small tolerance so nearly-square stays landscape layout
                modalDialog.classList.toggle('report-modal--portrait-image', isPortrait);
            }

            const hasImage = !!imageUrl;
            mMedia.classList.toggle('has-image', hasImage);
            mMedia.classList.toggle('no-image', !hasImage);
            if (mActions) {
                mActions.hidden = !hasImage;
            }

            // Clear previous listeners to avoid stacking
            if (mImage._onloadHandler) mImage.removeEventListener('load', mImage._onloadHandler);
            if (mImage._onerrorHandler) mImage.removeEventListener('error', mImage._onerrorHandler);

            if (hasImage) {
                mImage._onloadHandler = function() {
                    mImage.hidden = false;
                    mPlaceholder.hidden = true;
                    mPlaceholder.style.display = 'none';
                    applyOrientation();
                };
                mImage._onerrorHandler = function() {
                    // Fallback to placeholder on error
                    mImage.removeAttribute('src');
                    mImage.hidden = true;
                    mPlaceholder.hidden = false;
                    mPlaceholder.style.removeProperty('display');
                    if (modalDialog) modalDialog.classList.remove('report-modal--portrait-image');
                };
                mImage.addEventListener('load', mImage._onloadHandler, { once: true });
                mImage.addEventListener('error', mImage._onerrorHandler, { once: true });

                mImage.src = imageUrl;
                mImage.alt = `${titleText || 'Report'} photo`;

                // If the image is cached, load may not fire; apply immediately when dimensions are available
                if (mImage.complete && mImage.naturalWidth) {
                    mImage.hidden = false;
                    mPlaceholder.hidden = true;
                    mPlaceholder.style.display = 'none';
                    applyOrientation();
                }
            } else {
                mImage.removeAttribute('src');
                mImage.hidden = true;
                mPlaceholder.hidden = false;
                mPlaceholder.style.removeProperty('display');
                if (modalDialog) modalDialog.classList.remove('report-modal--portrait-image');
            }
        }

        function openAdminReportModal(data) {
            if (!reportModal) return;
            if (mTitle) { mTitle.textContent = data.title || 'Citizen report'; }
            if (mSubmitted) { mSubmitted.textContent = (window.formatTo12hDisplay ? window.formatTo12hDisplay(data.submitted_at || data.submitted || '') : (data.submitted_at || data.submitted || '—')); }
            if (mReporter) { mReporter.textContent = data.reporter || '—'; }
            if (mLocation) { mLocation.textContent = data.location || '—'; }
            if (mCategory) {
                const cat = data.category || '';
                mCategory.textContent = cat ? (cat.charAt(0).toUpperCase() + cat.slice(1)) : 'Category';
                mCategory.hidden = !cat;
            }
            applyStatusChip(mStatus, data.status);
            if (mSummary) { mSummary.textContent = data.summary || 'No summary provided.'; }
            updateMedia(data.image, data.title);

            reportModal.removeAttribute('hidden');
            document.body.classList.add('modal-open');
            reportModal.classList.add('is-open');
            if (modalDialog && typeof modalDialog.focus === 'function') { modalDialog.focus({ preventScroll: true }); }
        }

        function closeAdminReportModal() {
            if (!reportModal) return;
            reportModal.classList.remove('is-open');
            reportModal.setAttribute('hidden','hidden');
            document.body.classList.remove('modal-open');
            if (mImage) mImage.removeAttribute('src');
            // Reset orientation class on close so next open recomputes cleanly
            if (modalDialog) modalDialog.classList.remove('report-modal--portrait-image');
        }

        const viewButtons = Array.from(document.querySelectorAll('.admin-view-report'));
        try { console.log('[Admin] Found view buttons:', viewButtons.length); } catch(_) {}
        viewButtons.forEach(btn => {
            btn.addEventListener('click', (ev) => {
                ev.preventDefault();
                ev.stopPropagation();
                try { console.log('[Admin] View clicked'); } catch(_) {}
                const ds = btn.dataset || {};
                let data = {
                    title: ds.title || 'Citizen report',
                    category: ds.category || '',
                    reporter: ds.reporter || '—',
                    submitted_at: ds.submitted || '—',
                    status: ds.status || '',
                    location: ds.location || '—',
                    image: ds.image || '',
                    summary: ds.summary || ''
                };
                // Fallback to row text if any critical field is empty
                if (!data.title || data.title === 'Citizen report') {
                    const row = btn.closest('tr');
                    data.title = row?.querySelector('td[data-title="Title"]')?.textContent?.trim() || data.title;
                    data.category = row?.querySelector('td[data-title="Category"]')?.textContent?.trim() || data.category;
                    data.reporter = row?.querySelector('td[data-title="Reporter"]')?.textContent?.trim() || data.reporter;
                    data.submitted_at = row?.querySelector('td[data-title="Submitted"]')?.textContent?.trim() || data.submitted_at;
                    data.status = row?.getAttribute('data-status') || data.status;
                }
                try { console.log('[Admin] Modal data:', data); } catch(_) {}
                openAdminReportModal(data);
            });
        });

        // Event delegation fallback in case buttons are re-rendered dynamically
        document.addEventListener('click', function(ev){
            var tgt = ev.target;
            var btn = (tgt && typeof tgt.closest === 'function') ? tgt.closest('.admin-view-report') : null;
            if (!btn) return;
            ev.preventDefault();
            ev.stopPropagation();
            const ds = btn.dataset || {};
            const data = {
                title: ds.title || 'Citizen report',
                category: ds.category || '',
                reporter: ds.reporter || '—',
                submitted_at: ds.submitted || '—',
                status: ds.status || '',
                location: ds.location || '—',
                image: ds.image || '',
                summary: ds.summary || ''
            };
            openAdminReportModal(data);
        }, { capture: true });

        // Wire media action buttons once (they reference current image src when clicked)
        function openImageViewer(){
            try {
                if (!mViewer || !mViewerImg) return;
                const src = mImage && mImage.getAttribute ? mImage.getAttribute('src') : null;
                if (!src) return;
                mViewerImg.src = src;
                mViewerImg.alt = (mTitle && mTitle.textContent) || 'Report image';
                mViewer.classList.add('open');
                mViewer.removeAttribute('hidden');
            } catch (e) {}
        }
        function closeImageViewer(){
            if (!mViewer || !mViewerImg) return;
            mViewer.classList.remove('open');
            mViewer.setAttribute('hidden','hidden');
            mViewerImg.removeAttribute('src');
        }
        if (mOpenFull) { mOpenFull.addEventListener('click', openImageViewer); }
        if (mViewerClose) { mViewerClose.addEventListener('click', closeImageViewer); }
        if (mViewer) { mViewer.addEventListener('click', function(e){ if (e.target === mViewer) closeImageViewer(); }); }
        if (mDownload) {
            mDownload.addEventListener('click', function(){
                try {
                    const src = mImage && mImage.getAttribute ? mImage.getAttribute('src') : null;
                    if (!src) return;
                    const a = document.createElement('a');
                    a.href = src;
                    var titleText = (mTitle && mTitle.textContent) || 'report-image';
                    a.download = titleText.replace(/\s+/g,'-').toLowerCase() + '.jpg';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                } catch (e) {}
            });
        }
        if (modalBackdrop) { modalBackdrop.addEventListener('click', closeAdminReportModal); }
        modalCloseButtons.forEach(b => b.addEventListener('click', closeAdminReportModal));
        if (reportModal) reportModal.addEventListener('keydown', (e)=>{
            if(e.key==='Escape'){
                e.preventDefault();
                if (mViewer && mViewer.classList.contains('open')) { closeImageViewer(); return; }
                closeAdminReportModal();
            }
        });
        const toggle = document.getElementById('adminReportFilterToggle');
        const menu = document.getElementById('adminReportFilterMenu');
        const categorySelect = document.getElementById('adminFilterCategory');
        const tableBody = document.querySelector('.admin-table tbody');
        if (!tableBody || !toggle || !menu) return;

        const sortButtons = Array.from(menu.querySelectorAll('[data-sort]'));
        const statusButtons = Array.from(menu.querySelectorAll('[data-status]'));

        // Toggle menu visibility
        toggle.addEventListener('click', function(e){
            e.preventDefault();
            const expanded = toggle.getAttribute('aria-expanded') === 'true';
            menu.hidden = expanded;
            toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        });

        // Close when clicking outside
        document.addEventListener('click', function(e){
            if (menu.hidden) return;
            if (menu.contains(e.target) || toggle.contains(e.target)) return;
            menu.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
        });

        // Core filter + sort logic
        function applyAdminFilters(sortMode){
            const selectedCat = (((categorySelect ? categorySelect.value : '') || '').trim().toLowerCase());
            const activeStatusBtn = statusButtons.find(b=>b.classList.contains('active'));
            const selectedStatus = activeStatusBtn ? (activeStatusBtn.dataset.status || 'all') : 'all';

            const rows = Array.from(tableBody.querySelectorAll('tr'));
            rows.forEach(row => {
                var catEl = row.querySelector('td[data-title="Category"]');
                const catText = (((catEl ? catEl.textContent : '') || '').trim().toLowerCase());
                const rowStatus = (row.dataset.status || '').trim().toLowerCase();
                let show = true;
                if (selectedCat && catText !== selectedCat) show = false;
                if (selectedStatus && selectedStatus !== 'all' && rowStatus !== selectedStatus) show = false;
                row.style.display = show ? '' : 'none';
            });

            if (sortMode) {
                // Sort only visible rows
                const visibleRows = Array.from(tableBody.querySelectorAll('tr')).filter(r=>r.style.display !== 'none');
                visibleRows.sort((a,b) => {
                    const da = new Date(a.dataset.submitted || 0).getTime() || 0;
                    const db = new Date(b.dataset.submitted || 0).getTime() || 0;
                    return sortMode === 'newest' ? db - da : da - db;
                });
                visibleRows.forEach(r => tableBody.appendChild(r));
            }
        }

        // Wire category change
    if (categorySelect) { categorySelect.addEventListener('change', function(){ applyAdminFilters(); }); }

        // Wire status buttons
        statusButtons.forEach(btn => {
            btn.addEventListener('click', function(){
                statusButtons.forEach(b=>{ b.classList.remove('active'); b.setAttribute('aria-checked','false'); });
                btn.classList.add('active'); btn.setAttribute('aria-checked','true');
                applyAdminFilters();
            });
        });

        // Wire sort buttons
        sortButtons.forEach(btn => {
            btn.addEventListener('click', function(){
                sortButtons.forEach(b=>b.classList.remove('active'));
                btn.classList.add('active');
                applyAdminFilters(btn.dataset.sort || 'newest');
            });
        });

        // Initial apply
        applyAdminFilters();
    });
    </script>
    <script src="assets/js/script.js" defer></script>
</body>
</html>
