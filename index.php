<?php
require __DIR__ . '/config/auth.php';
require __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';

// Load announcements from DB if table exists; otherwise fallback to session
$announcements = [];
try {
    $check = $conn->query("SHOW TABLES LIKE 'announcements'");
    if ($check && $check->num_rows > 0) {
        $res = $conn->query("SELECT id, title, body, image_path, created_at FROM announcements ORDER BY created_at DESC LIMIT 200");
        if ($res) {
            while ($a = $res->fetch_assoc()) {
                $announcements[] = [
                    'id' => (int)($a['id'] ?? 0),
                    'title' => $a['title'] ?? '',
                    'body' => $a['body'] ?? '',
                    'image' => $a['image_path'] ?? null,
                    'created_at' => $a['created_at'] ?? null,
                ];
            }
        }
    } else {
        $announcements = $_SESSION['announcements'] ?? [];
    }
} catch (Throwable $e) {
    $announcements = $_SESSION['announcements'] ?? [];
}
$announcementCount = is_array($announcements) ? count($announcements) : 0;

// Load reports from DB for consistency across users
$reports = [];
try {
    $sql = "SELECT id, title, category, description, location, image_path, status, created_at FROM reports ORDER BY created_at DESC LIMIT 200";
    $res = $conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $reports[] = [
                'id' => (int)$r['id'],
                'title' => $r['title'] ?? 'Citizen report',
                'category' => $r['category'] ?? 'Report',
                'status' => $r['status'] ?? 'unresolved',
                'reporter' => 'Resident',
                'location' => $r['location'] ?? '',
                'submitted_at' => $r['created_at'] ?? null,
                'summary' => $r['description'] ?? '',
                'image' => $r['image_path'] ?? null,
                'tags' => [],
            ];
        }
    }
} catch (Throwable $e) {
    // Fallback to session if DB unavailable
    $reports = $_SESSION['reports'] ?? [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GO! MARIKINA</title>
    <link rel="stylesheet" href="./assets/css/style.css">
</head>
<body id="top">
    <div class="dashboard-layout">
        <button type="button" class="mobile-nav-toggle" data-nav-toggle aria-controls="primary-sidebar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="mobile-nav-toggle-bars" aria-hidden="true"></span>
        </button>
        <?php include './includes/navbar.php'; ?>
        <div class="mobile-nav-scrim" data-nav-scrim hidden></div>

        <main class="dashboard-main" id="main-content">
            <!-- Search bar + quick actions -->
            <header class="dashboard-header">
                <form class="dashboard-search" role="search">
                    <button type="submit" class="search-button" aria-label="Search reports">
                        <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                            <circle cx="11" cy="11" r="7" />
                            <path d="m20 20-3.5-3.5" />
                        </svg>
                    </button>
                    <input type="search" id="reportSearch" name="q" placeholder="Search for Reports, Date, Status" autocomplete="off" aria-label="Search for reports by text or status">
                </form>

                <div class="dashboard-actions">
                    <a href="profile.php" class="action-icon" aria-label="Open profile">
                        <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                            <circle cx="12" cy="8" r="4" />
                            <path d="M4 20c0-4 3-6 8-6s8 2 8 6" />
                        </svg>
                    </a>
                    <div class="notification" data-notification>
                        <button type="button" class="action-icon notification-toggle" aria-label="Open notifications" aria-expanded="false" aria-haspopup="true" data-notification-toggle data-notification-target="dashboardNotifications">
                            <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                                <path d="M18 16v-5a6 6 0 0 0-12 0v5l-2 2h16z" />
                                <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                            </svg>
                            <span class="notification-dot" aria-hidden="true" hidden></span>
                        </button>
                        <section class="notification-panel" id="dashboardNotifications" aria-labelledby="notificationPanelTitle" role="dialog" aria-modal="false" tabindex="-1" hidden data-notification-panel>
                            <header class="notification-panel-header">
                                <h3 id="notificationPanelTitle">Notifications</h3>
                                <button type="button" class="notification-action" data-notification-mark-read>Mark all as read</button>
                            </header>
                            <div class="notification-list" role="list">
                                <article class="notification-item" role="listitem">
                                    <div class="notification-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                                            <path d="M7.5 12.5 10.8 15.5 16.5 9" />
                                        </svg>
                                    </div>
                                    <div class="notification-content">
                                        <p class="notification-title">Road construction report resolved</p>
                                        <p class="notification-meta">5 minutes ago · Barangay San Roque</p>
                                    </div>
                                </article>
                                <article class="notification-item" role="listitem">
                                    <div class="notification-icon warning" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                                            <path d="M12 9v4" />
                                            <path d="M12 17h.01" />
                                            <path d="M10.29 3.86 1.82 18a1 1 0 0 0 .86 1.5h18.64a1 1 0 0 0 .86-1.5L12.71 3.86a1 1 0 0 0-1.72 0z" />
                                        </svg>
                                    </div>
                                    <div class="notification-content">
                                        <p class="notification-title">New flooding report near Sto. Niño</p>
                                        <p class="notification-meta">12 minutes ago · Awaiting assignment</p>
                                    </div>
                                </article>
                                <article class="notification-item" role="listitem">
                                    <div class="notification-icon info" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                                            <path d="M12 12v4" />
                                            <path d="M12 8h.01" />
                                            <circle cx="12" cy="12" r="10" />
                                        </svg>
                                    </div>
                                    <div class="notification-content">
                                        <p class="notification-title">Maintenance scheduled for Riverbanks</p>
                                        <p class="notification-meta">45 minutes ago · Public works</p>
                                    </div>
                                </article>
                            </div>
                            
                        </section>
                    </div>
                </div>
            </header>

            <!-- Hero now shows announcements -->
            <section class="dashboard-hero" id="hero" aria-label="Announcements">
                <section class="announcements-section" id="announcements" aria-labelledby="announcements-title">
                <div class="announcements-header">
                    <div>
                        <p class="announcements-kicker">City updates</p>
                        <h2 id="announcements-title">Latest announcements</h2>
                        <p class="announcements-subtitle">Stay informed about advisories, closures, and safety reminders from city hall.</p>
                    </div>

                    <!-- Widgets removed as requested -->
                </div>

                <?php if ($announcements): ?>
                    <div class="announcements-grid">
                        <?php foreach (array_reverse($announcements) as $announcement): ?>
                            <article class="public-announcement-card" data-announcement-id="<?php echo (int) $announcement['id']; ?>">
                                <header class="public-announcement-card__header">
                                    <h3><?php echo htmlspecialchars($announcement['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <time datetime="<?php echo htmlspecialchars(format_datetime_attr($announcement['created_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?>">Published <?php echo htmlspecialchars(format_datetime_display($announcement['created_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?></time>
                                </header>
                                <?php if (!empty($announcement['image'])): ?>
                                    <figure class="public-announcement-card__media">
                                        <img src="<?php echo htmlspecialchars($announcement['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars(($announcement['title'] ?? '') . ' image', ENT_QUOTES, 'UTF-8'); ?>">
                                    </figure>
                                <?php endif; ?>
                                <p class="public-announcement-card__body"><?php echo htmlspecialchars($announcement['body'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                                <footer class="public-announcement-card__footer">
                                    <button type="button" class="public-announcement-link" onclick="openAnnouncementsModal()">View all announcements</button>
                                </footer>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="announcements-empty">
                        <h3>No city announcements yet</h3>
                        <p>New advisories from the local government will appear here. Check back soon or subscribe in your profile preferences.</p>
                    </div>
                <?php endif; ?>
                </section>
            </section>

            <!-- Reports listing; search/filter logic is coordinated via assets/js/script.js -->
            <section class="reports-section" id="reports">
                <div class="reports-header">
                    <h2>Current Reports</h2>
                    <div class="reports-filter">
                        <button type="button" class="filter-toggle" aria-haspopup="true" aria-expanded="false" aria-controls="reportFilterMenu">
                            <span>Filter</span>
                            <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                                <path d="M4 5h16M7 12h10m-6 7h2" />
                            </svg>
                        </button>
                        <div class="filter-menu" id="reportFilterMenu" role="menu" hidden>
                            <button type="button" class="filter-option active" data-status="all" role="menuitemradio" aria-checked="true">All Reports</button>
                            <button type="button" class="filter-option" data-status="unresolved" role="menuitemradio" aria-checked="false">Unresolved</button>
                            <button type="button" class="filter-option" data-status="in_progress" role="menuitemradio" aria-checked="false">In Progress</button>
                            <button type="button" class="filter-option" data-status="solved" role="menuitemradio" aria-checked="false">Solved</button>
                        </div>
                    </div>
                </div>

                <?php if ($reports): ?>
                    <div class="reports-list" data-empty-message="No reports match your filters yet.">
                        <?php foreach ($reports as $report): ?>
                            <?php
                                $rawStatus = strtolower((string) ($report['status'] ?? 'unresolved'));
                                $datasetStatus = str_replace('-', '_', $rawStatus);
                                $statusLabel = status_label($rawStatus);
                                $statusModifier = status_chip_modifier($rawStatus);
                                $tagsAttribute = '';

                                if (!empty($report['tags']) && is_array($report['tags'])) {
                                    $tagsAttribute = htmlspecialchars(implode(' ', $report['tags']), ENT_QUOTES, 'UTF-8');
                                }

                                $submittedDisplay = format_datetime_display($report['submitted_at'] ?? null);
                                $imagePath = $report['image'] ?? null;
                                $titleDisplay = htmlspecialchars($report['title'] ?? 'Citizen report', ENT_QUOTES, 'UTF-8');
                                $reporterDisplay = htmlspecialchars($report['reporter'] ?? 'Resident', ENT_QUOTES, 'UTF-8');
                                $locationDisplay = !empty($report['location']) ? htmlspecialchars($report['location'], ENT_QUOTES, 'UTF-8') : '';
                                // Full, sanitized summary for attributes/modal
                                $rawSummary = (string)($report['summary'] ?? '');
                                $summaryDisplayFull = htmlspecialchars($rawSummary, ENT_QUOTES, 'UTF-8');
                                // Truncated summary for card
                                // Shorter card preview: reduce to 40 characters and
                                // truncate at the last whole word so we don't cut mid-word.
                                $summaryLimit = 40;
                                $rawLen = function_exists('mb_strlen') ? mb_strlen($rawSummary, 'UTF-8') : strlen($rawSummary);
                                $isTruncated = $rawLen > $summaryLimit;
                                if ($isTruncated) {
                                    if (function_exists('mb_substr') && function_exists('mb_strrpos')) {
                                        $tr = mb_substr($rawSummary, 0, $summaryLimit, 'UTF-8');
                                        $lastSpace = mb_strrpos($tr, ' ', 0, 'UTF-8');
                                        if ($lastSpace !== false) {
                                            $tr = mb_substr($tr, 0, $lastSpace, 'UTF-8');
                                        }
                                    } else {
                                        $tr = substr($rawSummary, 0, $summaryLimit);
                                        $lastSpace = strrpos($tr, ' ');
                                        if ($lastSpace !== false) {
                                            $tr = substr($tr, 0, $lastSpace);
                                        }
                                    }
                                    $tr .= '…';
                                    $summaryTrimDisplay = htmlspecialchars($tr, ENT_QUOTES, 'UTF-8');
                                } else {
                                    $summaryTrimDisplay = $summaryDisplayFull;
                                }
                                $categoryDisplay = htmlspecialchars(category_label($report['category'] ?? ''), ENT_QUOTES, 'UTF-8');
                                $statusLabelDisplay = htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8');
                                $statusModifierDisplay = htmlspecialchars($statusModifier, ENT_QUOTES, 'UTF-8');
                                $submittedAttr = htmlspecialchars($submittedDisplay, ENT_QUOTES, 'UTF-8');
                                $imageAttr = $imagePath ? htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8') : '';
                                $ariaLabel = htmlspecialchars('View report details for ' . ($report['title'] ?? 'Citizen report'), ENT_QUOTES, 'UTF-8');
                            ?>
                            <article
                                class="report-card"
                                tabindex="0"
                                role="button"
                                aria-haspopup="dialog"
                                aria-label="<?php echo $ariaLabel; ?>"
                                data-report-modal-trigger
                                data-id="<?php echo htmlspecialchars($report['id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                <?php if (!empty($report['latitude'])): ?>data-lat="<?php echo htmlspecialchars($report['latitude'], ENT_QUOTES, 'UTF-8'); ?>"<?php endif; ?>
                                <?php if (!empty($report['longitude'])): ?>data-lng="<?php echo htmlspecialchars($report['longitude'], ENT_QUOTES, 'UTF-8'); ?>"<?php endif; ?>
                                data-status="<?php echo htmlspecialchars($datasetStatus, ENT_QUOTES, 'UTF-8'); ?>"
                                data-title="<?php echo $titleDisplay; ?>"
                                data-summary="<?php echo $summaryDisplayFull; ?>"
                                data-reporter="<?php echo $reporterDisplay; ?>"
                                data-category="<?php echo $categoryDisplay; ?>"
                                data-status-label="<?php echo $statusLabelDisplay; ?>"
                                data-status-modifier="<?php echo $statusModifierDisplay; ?>"
                                data-submitted="<?php echo $submittedAttr; ?>"
                                <?php if ($locationDisplay !== ''): ?>data-location="<?php echo $locationDisplay; ?>"<?php endif; ?>
                                <?php if ($imageAttr !== ''): ?>data-image="<?php echo $imageAttr; ?>"<?php endif; ?>
                                <?php if ($tagsAttribute !== ''): ?>data-tags="<?php echo $tagsAttribute; ?>"<?php endif; ?>
                            >
                                <header class="report-card-header">
                                    <div class="report-author">
                                        <div class="author-avatar" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet" role="presentation" focusable="false">
                                                <circle cx="12" cy="8" r="4" />
                                                <path d="M4 20c0-4 3-6 8-6s8 2 8 6" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="report-title-row">
                                                <h3 title="<?php echo $titleDisplay; ?>"><?php echo $titleDisplay; ?></h3>
                                                <span class="report-meta">Submitted <?php echo $submittedAttr; ?></span>
                                            </div>
                                            <p class="report-meta-row">
                                                <span class="report-reporter"><?php echo $reporterDisplay; ?></span>
                                                <?php if ($locationDisplay !== ''): ?>
                                                    <span class="report-meta-separator" aria-hidden="true">•</span>
                                                    <?php
                                                        // Show a summarized location in the card, keep full in title
                                                        // Use 2 components and a shorter max length so the card stays compact
                                                        $locationShort = function_exists('summarize_location') ? summarize_location($locationDisplay, 2, 40) : $locationDisplay;
                                                    ?>
                                                    <span class="report-location" title="<?php echo htmlspecialchars($locationDisplay, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($locationShort, ENT_QUOTES, 'UTF-8'); ?></span>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="report-header-actions">
                                        <button type="button" class="icon-button location-button" aria-label="View location on map">
                                            <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                                                <path d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7zm0 9.5a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5z" />
                                            </svg>
                                        </button>
                                        <button type="button" class="icon-button share-button" aria-label="Share report">
                                            <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                                                <path d="M4 12v7a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-7" />
                                                <path d="m7 9 5-6 5 6" />
                                                <path d="M12 3v13" />
                                            </svg>
                                        </button>
                                        <span class="chip chip-category" data-report-modal-category-label><?php echo $categoryDisplay; ?></span>
                                        <span class="chip chip-status <?php echo $statusModifierDisplay; ?>" data-report-modal-status-label><?php echo $statusLabelDisplay; ?></span>
                                    </div>
                                </header>
                                <?php if ($summaryTrimDisplay !== ''): ?>
                                    <p class="report-summary" data-expanded="false">
                                        <span class="report-summary__text" title="<?php echo $summaryDisplayFull; ?>"><?php echo $summaryTrimDisplay; ?><?php if ($isTruncated): ?> <a href="#" class="report-see-more">See more</a><?php endif; ?></span>
                                    </p>
                                <?php endif; ?>
                                <?php if ($imageAttr !== ''): ?>
                                    <figure class="report-media aspect-8-4">
                                        <img src="<?php echo $imageAttr; ?>" alt="<?php echo htmlspecialchars(($report['title'] ?? 'Report') . ' photo', ENT_QUOTES, 'UTF-8'); ?>">
                                    </figure>
                                <?php else: ?>
                                    <figure class="report-media report-media--placeholder" aria-hidden="true">
                                        <div class="report-media--placeholder-icon">
                                            <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                                                <rect x="3" y="5" width="18" height="14" rx="2" />
                                                <circle cx="8.5" cy="10.5" r="2" />
                                                <path d="M21 15.5 16.5 11 6 19" />
                                            </svg>
                                        </div>
                                        <span>No photo provided</span>
                                    </figure>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="reports-list" data-empty-message="No reports match your filters yet.">
                        <div class="reports-empty-state">No reports available yet.</div>
                    </div>
                <?php endif; ?>
            </section>
        </main>

    <div class="report-modal" id="reportModal" hidden>
        <div class="report-modal__backdrop" data-report-modal-close data-report-modal-backdrop></div>
    <div class="report-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="reportModalTitle" aria-describedby="reportModalSummary" tabindex="-1">
            <button type="button" class="report-modal__close" data-report-modal-close aria-label="Close report details">
                <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                    <path d="M18 6 6 18" />
                    <path d="m6 6 12 12" />
                </svg>
            </button>
            <div class="report-modal__content">
                <header class="report-modal__header">
                    <div class="report-modal__header-info">
                        <h3 id="reportModalTitle" data-report-modal-title>Citizen report</h3>
                        <p class="report-modal__submitted">Submitted <span data-report-modal-submitted>—</span></p>
                    </div>
                    <div class="report-modal__badges">
                        <span class="chip chip-category" data-report-modal-category>Category</span>
                        <span class="chip chip-status" data-report-modal-status>Status</span>
                    </div>
                </header>
                <dl class="report-modal__meta-grid">
                    <div class="report-modal__meta-item">
                        <dt>Reporter</dt>
                        <dd data-report-modal-reporter>—</dd>
                    </div>
                    <div class="report-modal__meta-item" data-report-modal-meta="location">
                        <dt>Location</dt>
                        <dd data-report-modal-location>—</dd>
                    </div>
                </dl>
                <div class="report-modal__summary">
                    <h4>Summary</h4>
                    <p id="reportModalSummary" data-report-modal-summary>—</p>
                </div>
                <div class="report-modal__media" data-report-modal-media>
                    <img data-report-modal-image alt="" hidden>
                    <div class="report-modal__media-placeholder" data-report-modal-placeholder>
                        <div class="report-media--placeholder-icon">
                            <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                                <rect x="3" y="5" width="18" height="14" rx="2" />
                                <circle cx="8.5" cy="10.5" r="2" />
                                <path d="M21 15.5 16.5 11 6 19" />
                            </svg>
                        </div>
                        <span>No photo provided</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating action button stays pinned bottom-right -->
    <button type="button" class="floating-action" aria-label="Create a new report" onclick="window.location.href='create-report.php'">
            <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                <rect x="11" y="5" width="2" height="14" rx="1" />
                <rect x="5" y="11" width="14" height="2" rx="1" />
            </svg>
        </button>
    </div>

    <!-- Announcements Modal -->
    <div id="announcementsModal" class="modal" hidden>
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2>City Announcements</h2>
                    <p class="modal-subtitle">Stay informed about advisories, closures, and safety reminders from city hall.</p>
                </div>
                <button type="button" class="modal-close" aria-label="Close modal">
                    <svg viewBox="0 0 24 24" role="presentation" focusable="false" width="24" height="24">
                        <path d="M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="m6 6 12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <?php if (!empty($announcements)): ?>
                    <ul class="modal-announcements-list">
                        <?php foreach ($announcements as $announcement): ?>
                            <li class="modal-announcement-item">
                                <h3><?php echo htmlspecialchars($announcement['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p class="announcement-date"><?php echo date('M j, Y · h:i A', strtotime($announcement['created_at'])); ?></p>
                                <?php if (!empty($announcement['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($announcement['image'], ENT_QUOTES, 'UTF-8'); ?>" 
                                         alt="" class="modal-announcement-image">
                                <?php endif; ?>
                                <div class="announcement-content">
                                    <?php echo htmlspecialchars($announcement['body'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="modal-empty">No announcements available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Map view modal (used when clicking location on a report card) -->
    <div id="mapModal" class="modal" aria-hidden="true" style="display:none;">
        <div class="modal-content map-modal-content" role="dialog" aria-modal="true">
            <div class="modal-header">
                <h2>View location</h2>
                <button type="button" class="modal-close" id="mapModalClose" aria-label="Close">×</button>
            </div>
            <div class="modal-body">
                <div class="map-picker-wrap">
                    <input type="search" id="leafletPlaceInput" class="form-input" placeholder="Search for a place or address..." />
                    <div id="reportMap" class="report-map" style="height:420px;"></div>
                    <div id="infowindow-content" class="visually-hidden">
                        <span id="place-name" data-key="place-name"></span>
                        <span id="place-address" data-key="place-address"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="map-footer-actions">
                    <button type="button" id="mapClearSelection" class="btn-map-clear">CLEAR</button>
                    <button type="button" id="mapUsePlace" class="btn-map-use">USE THIS PLACE</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MarkerCluster plugin for Leaflet (optional; script is loaded before app script) -->
    <script defer src="https://unpkg.com/leaflet.markercluster/dist/leaflet.markercluster.js"></script>
    <script src="./assets/js/script.js" defer></script>
</body>
</html>