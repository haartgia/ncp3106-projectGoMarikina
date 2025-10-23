<?php
require __DIR__ . '/config/auth.php';
require __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';

$myReports = [];
$reportCount = 0;
$userRow = null;

if (is_logged_in()) {
    $user = current_user(); 
    $user_id = (int)$user['id'];

    // Load user profile details
    $stmt = $conn->prepare("SELECT first_name, last_name, email, mobile FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $userRow = $result->fetch_assoc();
    $stmt->close();

    // Load user's reports
    try {
        $stmt2 = $conn->prepare("SELECT id, title, category, description, location, image_path, status, created_at FROM reports WHERE user_id = ? ORDER BY created_at DESC LIMIT 200");
        $stmt2->bind_param("i", $user_id);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        while ($r = $res2->fetch_assoc()) {
            $myReports[] = $r;
        }
        $stmt2->close();
        $reportCount = count($myReports);
    } catch (Throwable $e) {
        $myReports = [];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PROFILE - GO! MARIKINA</title>
    <?php $cssVersion = @filemtime(__DIR__ . '/assets/css/style.css') ?: time(); ?>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo $cssVersion; ?>">
</head>
<body>
    <div class="dashboard-layout">
        <button type="button" class="mobile-nav-toggle" data-nav-toggle aria-controls="primary-sidebar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="mobile-nav-toggle-bars" aria-hidden="true"></span>
        </button>
        <?php include './includes/navbar.php'; ?>
        <div class="mobile-nav-scrim" data-nav-scrim hidden></div>

        <!-- Main Content -->
        <main class="dashboard-main">
            <header class="dashboard-header profile-header-centered">
                <img src="./uploads/blue_smallgomarikina.png?v=<?php echo @filemtime(__DIR__ . '/uploads/blue_smallgomarikina.png') ?: time(); ?>" alt="GO! MARIKINA" class="profile-small-logo" />
            </header>

            <?php
            $redirectTarget = 'profile.php';
            include __DIR__ . '/includes/login_card.php';
            ?>

            <?php if (is_logged_in() && $userRow): ?>
            <!-- Profile Content -->
            <div id="profileContent">
                <section class="profile-section">
                    <div class="profile-hero">
                        <div class="profile-hero-head">
                            <p class="profile-hero-kicker">USER PROFILE</p>
                            <h2 class="profile-hero-title">Your profile</h2>
                            <p class="profile-hero-subtitle">Manage your personal information and keep your contact details up to date.</p>
                        </div>
                        <div class="profile-hero-inner">
                          <div class="profile-card">
                            <div class="profile-card-body">
                              <div class="profile-info">
                                <div class="profile-field">
                                    <label class="profile-label">Name</label>
                                    <input type="text" class="profile-input" 
                                        value="<?php echo htmlspecialchars(($userRow['first_name'] ?? '') . ' ' . ($userRow['last_name'] ?? '')); ?>" readonly>
                                </div>

                                <div class="profile-field">
                                    <label class="profile-label">Email</label>
                                    <input type="email" class="profile-input" 
                                        value="<?php echo htmlspecialchars($userRow['email'] ?? ''); ?>" readonly>
                                </div>

                                <div class="profile-field">
                                    <label class="profile-label">Password</label>
                                    <div class="profile-input-group">
                                        <input type="password" class="profile-input" value="*************" readonly id="passwordField">
                                        <button type="button" class="profile-edit-btn" id="editPasswordBtn" data-field="password">
                                            <svg viewBox="0 0 24 24">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="m18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <div class="profile-field">
                                    <label class="profile-label">Mobile Number</label>
                                    <div class="profile-input-group">
                                        <input type="tel" class="profile-input" 
                                            value="<?php echo htmlspecialchars($userRow['mobile'] ?? ''); ?>" readonly id="mobileField">
                                        <button type="button" class="profile-edit-btn" id="editMobileBtn" data-field="mobile">
                                            <svg viewBox="0 0 24 24">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="m18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                        </div>
                </section>

                <div class="reports-summary">
                    <h2>No. of Reports: <?php echo (int)$reportCount; ?></h2>
                </div>

                <div class="dividing-line"></div>

                <section class="reports-section">
                    <div class="reports-header">
                        <h3>Your Reports</h3>
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
                                <button type="button" class="filter-option" data-status="solved" role="menuitemradio" aria-checked="false">Solved</button>
                            </div>
                        </div>
                    </div>

                    <div class="reports-list">
                        <?php if ($reportCount === 0): ?>
                            <div class="reports-empty-state">No reports yet.</div>
                        <?php else: ?>
                            <?php foreach ($myReports as $report): ?>
                                <?php
                                    $rawStatus = strtolower((string) ($report['status'] ?? 'unresolved'));
                                    $datasetStatus = str_replace('-', '_', $rawStatus);
                                    $statusLabel = status_label($rawStatus);
                                    $statusModifier = status_chip_modifier($rawStatus);

                                    $titleDisplay = htmlspecialchars($report['title'] ?? 'Citizen report', ENT_QUOTES, 'UTF-8');
                                    $locationDisplay = htmlspecialchars($report['location'] ?? '', ENT_QUOTES, 'UTF-8');
                                    $rawCategory = (string)($report['category'] ?? '');
                                    $categoryDisplay = htmlspecialchars(category_label($rawCategory), ENT_QUOTES, 'UTF-8');
                                    $statusLabelDisplay = htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8');
                                    $statusModifierDisplay = htmlspecialchars($statusModifier, ENT_QUOTES, 'UTF-8');
                                    $submittedAttr = htmlspecialchars(format_datetime_display($report['created_at'] ?? null), ENT_QUOTES, 'UTF-8');
                                    $imageAttr = !empty($report['image_path']) ? htmlspecialchars($report['image_path'], ENT_QUOTES, 'UTF-8') : '';
                                    $summaryFull = htmlspecialchars((string)($report['description'] ?? ''), ENT_QUOTES, 'UTF-8');

                                    $summaryLimit = 160;
                                    $rawSummary = (string)($report['description'] ?? '');
                                    $rawLen = function_exists('mb_strlen') ? mb_strlen($rawSummary, 'UTF-8') : strlen($rawSummary);
                                    if ($rawLen > $summaryLimit) {
                                        $tr = function_exists('mb_substr') ? mb_substr($rawSummary, 0, $summaryLimit, 'UTF-8') : substr($rawSummary, 0, $summaryLimit);
                                        $summaryTrim = htmlspecialchars($tr . '…', ENT_QUOTES, 'UTF-8');
                                        $isTruncated = true;
                                    } else {
                                        $summaryTrim = $summaryFull;
                                        $isTruncated = false;
                                    }
                                ?>
                                <article class="report-card" tabindex="0" role="button" aria-haspopup="dialog"
                                    data-report-modal-trigger
                                    data-status="<?php echo $datasetStatus; ?>"
                                    data-title="<?php echo $titleDisplay; ?>"
                                    data-summary="<?php echo $summaryFull; ?>"
                                    data-reporter="<?php echo htmlspecialchars(($userRow['first_name'] ?? '').' '.($userRow['last_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    data-category="<?php echo $categoryDisplay; ?>"
                                    data-status-label="<?php echo $statusLabelDisplay; ?>"
                                    data-status-modifier="<?php echo $statusModifierDisplay; ?>"
                                    data-submitted="<?php echo $submittedAttr; ?>"
                                    <?php if ($locationDisplay !== ''): ?>data-location="<?php echo $locationDisplay; ?>"<?php endif; ?>
                                    <?php if ($imageAttr !== ''): ?>data-image="<?php echo $imageAttr; ?>"<?php endif; ?>
                                >
                                    <header class="report-card-header">
                                        <div class="report-author">
                                            <div class="author-avatar" aria-hidden="true">
                                                <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                                                    <circle cx="12" cy="8" r="4" />
                                                    <path d="M4 20c0-4 3-6 8-6s8 2 8 6" />
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="report-title-row">
                                                    <h3><?php echo $titleDisplay; ?></h3>
                                                    <span class="report-meta">Submitted <?php echo $submittedAttr; ?></span>
                                                </div>
                                                <p>
                                                    <?php echo htmlspecialchars(($userRow['first_name'] ?? '').' '.($userRow['last_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                                    <?php if ($locationDisplay !== ''): ?>
                                                        <span class="report-meta-separator" aria-hidden="true">•</span>
                                                        <span class="report-location"><?php echo $locationDisplay; ?></span>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="report-header-actions">
                                            <span class="chip chip-category"><?php echo $categoryDisplay; ?></span>
                                            <span class="chip chip-status <?php echo $statusModifierDisplay; ?>"><?php echo $statusLabelDisplay; ?></span>
                                        </div>
                                    </header>
                                    <?php if ($summaryTrim !== ''): ?>
                                        <p class="report-summary"><?php echo $summaryTrim; ?><?php if ($isTruncated): ?> <a href="#" class="report-see-more">See more</a><?php endif; ?></p>
                                    <?php endif; ?>
                                    <?php if ($imageAttr !== ''): ?>
                                        <figure class="report-media aspect-8-4">
                                            <img src="<?php echo $imageAttr; ?>" alt="<?php echo $titleDisplay; ?> photo">
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
                        <?php endif; ?>
                    </div>
                </section>
            </div>
            <?php endif; ?>
        </main>

        <button type="button" class="floating-action" aria-label="Create a new report" onclick="window.location.href='create-report.php'">
            <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                <rect x="11" y="5" width="2" height="14" rx="1" />
                <rect x="5" y="11" width="14" height="2" rx="1" />
            </svg>
        </button>
    </div>

    <script src="assets/js/script.js" defer></script>
</body>
</html>
