<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_admin();

function store_announcement_image(array $image, int $nextId): ?string
{
    $uploadsDir = __DIR__ . '/uploads/announcements';

    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0775, true);
    }

    $extension = strtolower(pathinfo($image['name'] ?? '', PATHINFO_EXTENSION) ?: 'jpg');
    $safeExtension = preg_replace('/[^a-z0-9]/i', '', $extension) ?: 'jpg';
    $filename = sprintf('announcement-%d-%s.%s', $nextId, uniqid(), $safeExtension);
    $targetPath = $uploadsDir . '/' . $filename;

    $temporaryFile = $image['tmp_name'] ?? null;
    if (!$temporaryFile || !is_readable($temporaryFile)) {
        return null;
    }

    $imageInfo = @getimagesize($temporaryFile);
    $gdAvailable = extension_loaded('gd');

    if (!$gdAvailable || $imageInfo === false) {
        if (move_uploaded_file($temporaryFile, $targetPath)) {
            return 'uploads/announcements/' . $filename;
        }

        return null;
    }

    [$width, $height] = $imageInfo;
    $mime = $imageInfo['mime'] ?? '';
    $maxDimension = 1600;
    $scale = min($maxDimension / max($width, 1), $maxDimension / max($height, 1), 1);
    $targetWidth = (int) round($width * $scale);
    $targetHeight = (int) round($height * $scale);

    $createFrom = null;
    $outputHandler = null;

    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            if (function_exists('imagecreatefromjpeg') && function_exists('imagejpeg')) {
                $createFrom = 'imagecreatefromjpeg';
                $outputHandler = static fn($resource, $path) => imagejpeg($resource, $path, 82);
            }
            break;
        case 'image/png':
            if (function_exists('imagecreatefrompng') && function_exists('imagepng')) {
                $createFrom = 'imagecreatefrompng';
                $outputHandler = static fn($resource, $path) => imagepng($resource, $path, 7);
            }
            break;
        case 'image/webp':
            if (function_exists('imagecreatefromwebp') && function_exists('imagewebp')) {
                $createFrom = 'imagecreatefromwebp';
                $outputHandler = static fn($resource, $path) => imagewebp($resource, $path, 82);
            }
            break;
    }

    if (!$createFrom || !$outputHandler) {
        if (move_uploaded_file($temporaryFile, $targetPath)) {
            return 'uploads/announcements/' . $filename;
        }

        return null;
    }

    $sourceImage = @$createFrom($temporaryFile);
    if (!$sourceImage) {
        if (move_uploaded_file($temporaryFile, $targetPath)) {
            return 'uploads/announcements/' . $filename;
        }

        return null;
    }

    $destinationImage = imagecreatetruecolor($targetWidth, $targetHeight);

    if ($mime === 'image/png' || $mime === 'image/webp') {
        imagealphablending($destinationImage, false);
        imagesavealpha($destinationImage, true);
    }

    imagecopyresampled($destinationImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

    $outputSuccess = $outputHandler($destinationImage, $targetPath);

    imagedestroy($destinationImage);
    imagedestroy($sourceImage);

    if ($outputSuccess) {
        return 'uploads/announcements/' . $filename;
    }

    if (move_uploaded_file($temporaryFile, $targetPath)) {
        return 'uploads/announcements/' . $filename;
    }

    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_announcement') {
        $title = trim($_POST['announcement_title'] ?? '');
        $body = trim($_POST['announcement_body'] ?? '');
        $image = $_FILES['announcement_image'] ?? null;

        if ($title !== '' && $body !== '') {
            $nextId = time();

            $storedImagePath = null;
            if ($image && ($image['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $storedImagePath = store_announcement_image($image, $nextId);
            }

            // DB-first; fallback to session
            try {
                $check = $conn->query("SHOW TABLES LIKE 'announcements'");
                if ($check && $check->num_rows > 0) {
                    $stmt = $conn->prepare('INSERT INTO announcements (title, body, image_path, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())');
                    $stmt->bind_param('sss', $title, $body, $storedImagePath);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    $_SESSION['announcements'][] = [
                        'id' => $nextId,
                        'title' => $title,
                        'body' => $body,
                        'created_at' => date('c'),
                        'image' => $storedImagePath,
                    ];
                }
                $_SESSION['announcement_feedback'] = 'Announcement published successfully.';
            } catch (Throwable $e) {
                $_SESSION['announcement_feedback'] = 'Failed to publish announcement.';
            }
        } else {
            $_SESSION['announcement_feedback'] = 'Please complete both the title and message fields before publishing.';
        }
    } elseif ($action === 'delete_announcement' || $action === 'archive_announcement') {
        $isArchiveRequest = ($action === 'archive_announcement');
        $announcementId = (int) ($_POST['announcement_id'] ?? 0);

        if ($announcementId) {
            try {
                $check = $conn->query("SHOW TABLES LIKE 'announcements'");
                if ($check && $check->num_rows > 0) {
                    // Try to archive first if archive table exists; otherwise proceed with hard delete
                    $shouldArchive = false;
                    try {
                        $checkArchive = $conn->query("SHOW TABLES LIKE 'announcements_archive'");
                        $shouldArchive = ($checkArchive && $checkArchive->num_rows > 0);
                    } catch (Throwable $ie) { $shouldArchive = false; }

                    if ($shouldArchive) {
                        try {
                            $stmtA = $conn->prepare('INSERT INTO announcements_archive (id, title, body, image_path, created_at, updated_at, archived_at, archived_by) SELECT id, title, body, image_path, created_at, updated_at, NOW(), ? FROM announcements WHERE id = ?');
                            $archiver = (int)(current_user()['id'] ?? 0);
                            $stmtA->bind_param('ii', $archiver, $announcementId);
                            $stmtA->execute();
                            $stmtA->close();
                        } catch (Throwable $ie) {
                            // If archiving fails, continue with delete
                        }
                    }

                    $stmt = $conn->prepare('DELETE FROM announcements WHERE id = ?');
                    $stmt->bind_param('i', $announcementId);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    if (!empty($_SESSION['announcements'])) {
                        foreach ($_SESSION['announcements'] as $existing) {
                            if ((int) ($existing['id'] ?? 0) === $announcementId) {
                                if (!empty($existing['image'])) {
                                    $imagePath = __DIR__ . '/' . $existing['image'];
                                    if (is_file($imagePath)) { @unlink($imagePath); }
                                }
                                break;
                            }
                        }
                    }
                    $_SESSION['announcements'] = array_values(array_filter(
                        $_SESSION['announcements'] ?? [],
                        static fn($announcement) => (int) ($announcement['id'] ?? 0) !== $announcementId
                    ));
                }
                $_SESSION['announcement_feedback'] = $isArchiveRequest ? 'Announcement archived.' : 'Announcement removed.';
            } catch (Throwable $e) {
                $_SESSION['announcement_feedback'] = $isArchiveRequest ? 'Failed to archive announcement.' : 'Failed to remove announcement.';
            }
        }
    }

    header('Location: announcements.php');
    exit;
}

$announcements = [];
$announcementFeedback = $_SESSION['announcement_feedback'] ?? null;
unset($_SESSION['announcement_feedback']);

// Pagination: 3 per page for published announcements list
$page = max(1, (int)($_GET['apage'] ?? 1));
$perPage = 3;
$totalAnnouncements = 0;
$totalPages = 1;
$latestAnnouncement = null;

try {
    $check = $conn->query("SHOW TABLES LIKE 'announcements'");
    if ($check && $check->num_rows > 0) {
        // Totals
        if ($resCnt = $conn->query('SELECT COUNT(*) AS c FROM announcements')) {
            $rowCnt = $resCnt->fetch_assoc();
            $totalAnnouncements = (int)($rowCnt['c'] ?? 0);
            $resCnt->close();
        }
        $totalPages = max(1, (int)ceil($totalAnnouncements / $perPage));
        if ($page > $totalPages) { $page = $totalPages; }
        $offset = ($page - 1) * $perPage;

        // Latest announcement for hero card note
        if ($resLatest = $conn->query('SELECT id, title, created_at FROM announcements ORDER BY created_at DESC LIMIT 1')) {
            $la = $resLatest->fetch_assoc();
            if ($la) { $latestAnnouncement = $la; }
            $resLatest->close();
        }

        // Page subset
        $stmt = $conn->prepare('SELECT id, title, body, image_path, created_at FROM announcements ORDER BY created_at DESC LIMIT ? OFFSET ?');
        if ($stmt) {
            $stmt->bind_param('ii', $perPage, $offset);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($a = $res->fetch_assoc()) {
                $announcements[] = [
                    'id' => (int)($a['id'] ?? 0),
                    'title' => $a['title'] ?? '',
                    'body' => $a['body'] ?? '',
                    'image' => $a['image_path'] ?? null,
                    'created_at' => $a['created_at'] ?? null,
                ];
            }
            $stmt->close();
        }
    } else {
        // Session fallback: reverse for newest first, then slice
        $all = $_SESSION['announcements'] ?? [];
        // ensure array
        if (!is_array($all)) $all = [];
        $totalAnnouncements = count($all);
        $totalPages = max(1, (int)ceil($totalAnnouncements / $perPage));
        if ($page > $totalPages) { $page = $totalPages; }
        if (!empty($all)) {
            $latestAnnouncement = $all[array_key_last($all)];
        }
        $allRev = array_reverse($all);
        $slice = array_slice($allRev, ($page - 1) * $perPage, $perPage);
        $announcements = $slice;
    }
} catch (Throwable $e) {
    $all = $_SESSION['announcements'] ?? [];
    if (!is_array($all)) $all = [];
    $totalAnnouncements = count($all);
    $totalPages = max(1, (int)ceil($totalAnnouncements / $perPage));
    if ($page > $totalPages) { $page = $totalPages; }
    if (!empty($all)) {
        $latestAnnouncement = $all[array_key_last($all)];
    }
    $announcements = array_slice(array_reverse($all), ($page - 1) * $perPage, $perPage);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements · GO! MARIKINA</title>
    <?php 
        $BASE = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        $cssVersion = @filemtime(__DIR__ . '/assets/css/style.css') ?: time();
        $jsVersion = @filemtime(__DIR__ . '/assets/js/script.js') ?: time();
    ?>
    <link rel="stylesheet" href="<?= $BASE ?>/assets/css/style.css?v=<?= $cssVersion ?>">
</head>
<body id="top">
    <div class="dashboard-layout admin-layout">
        <button type="button" class="mobile-nav-toggle" data-nav-toggle aria-controls="primary-sidebar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="mobile-nav-toggle-bars" aria-hidden="true"></span>
        </button>
        <?php include './includes/navbar.php'; ?>
        <div class="mobile-nav-scrim" data-nav-scrim hidden></div>

        <main class="dashboard-main admin-main" id="main-content">
            <header class="admin-hero announcements-hero">
                <div class="admin-hero-text">
                    <p class="admin-kicker">Public information desk</p>
                    <h1 class="admin-title">City Announcements</h1>
                    <p class="admin-subtitle">Review previously published notices and share timely updates with residents.</p>
                    <div class="admin-hero-actions">
                        <a class="admin-hero-button admin-hero-button--primary" href="#compose">Create announcement</a>
                        <a class="admin-hero-button" href="admin.php">Back to admin dashboard</a>
                    </div>
                </div>
                <div class="admin-hero-card announcements-hero-card">
                    <div class="admin-hero-card-header">
                        <span class="admin-badge">Signed in as <?php echo htmlspecialchars(current_user()['email'] ?? 'administrator'); ?></span>
                        <a class="admin-logout" href="logout.php">Log out</a>
                    </div>
                    <p class="admin-hero-note">
                        <?php if ($latestAnnouncement): ?>
                            Latest notice&nbsp;<strong><?php echo htmlspecialchars($latestAnnouncement['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong><br>
                            <span class="admin-hero-meta">Published <?php echo htmlspecialchars(format_datetime_display($latestAnnouncement['created_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php else: ?>
                            No announcements published yet — start with the composer to the right.
                        <?php endif; ?>
                    </p>
                </div>
            </header>

            <?php if ($announcementFeedback): ?>
                <div class="announcement-feedback" role="status"><?php echo htmlspecialchars($announcementFeedback); ?></div>
            <?php endif; ?>

            <div class="announcement-layout">
                <section class="admin-section announcement-feed" aria-labelledby="announcements-heading" data-announcements-view id="announcements">
                    <div class="admin-section-header">
                        <div>
                            <h2 id="announcements-heading">Published announcements</h2>
                            <p class="admin-section-subtitle">Newest updates appear at the top of the list.</p>
                        </div>
                        <div class="admin-section-actions">
                            <button type="button" class="view-all-btn" onclick="event.preventDefault(); openAnnouncementsModal();">View all announcements</button>
                            <span class="admin-count">Total: <?php echo (int)$totalAnnouncements; ?></span>
                        </div>
                    </div>

                    <?php if ($announcements): ?>
                        <ul id="announcementsList" class="announcement-list" data-announcements-list>
                            <?php foreach ($announcements as $announcement): ?>
                                <li class="announcement-card" data-announcement-id="<?php echo (int) $announcement['id']; ?>">
                                    <header class="announcement-card__header">
                                        <h3><?php echo htmlspecialchars($announcement['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h3>
                                        <time datetime="<?php echo htmlspecialchars(format_datetime_attr($announcement['created_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?>">Published <?php echo htmlspecialchars(format_datetime_display($announcement['created_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?></time>
                                    </header>
                                    <?php if (!empty($announcement['image'])): ?>
                                        <figure class="announcement-card__media">
                                            <img src="<?php echo htmlspecialchars($announcement['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars(($announcement['title'] ?? '') . ' image', ENT_QUOTES, 'UTF-8'); ?>">
                                        </figure>
                                    <?php endif; ?>
                                    <div class="announcement-card__body">
                                        <?php echo htmlspecialchars($announcement['body'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <footer class="announcement-card__footer">
                                        <form method="post" class="announcement-delete-form" data-confirm-message="Archive this announcement?">
                                            <input type="hidden" name="action" value="archive_announcement">
                                            <input type="hidden" name="announcement_id" value="<?php echo (int) $announcement['id']; ?>">
                                            <button type="submit" class="announcement-delete">Archive</button>
                                        </form>
                                    </footer>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if ($totalPages > 1):
                            $baseUrl = strtok($_SERVER['REQUEST_URI'], '?');
                            $qs = $_GET; unset($qs['apage']);
                            $buildUrl = function($p) use ($baseUrl, $qs){ $qs2 = $qs; $qs2['apage'] = $p; return htmlspecialchars($baseUrl . '?' . http_build_query($qs2), ENT_QUOTES, 'UTF-8'); };
                        ?>
                        <nav id="announcementsPager" class="pager" aria-label="Announcements pagination">
                            <div class="pager-inner">
                                <a class="pager-btn" href="<?= $buildUrl(1) ?>" aria-label="First page"<?= $page <= 1 ? ' aria-disabled="true" tabindex="-1"' : '' ?>>« First</a>
                                <a class="pager-btn" href="<?= $buildUrl(max(1, $page-1)) ?>" aria-label="Previous page"<?= $page <= 1 ? ' aria-disabled="true" tabindex="-1"' : '' ?>>‹ Prev</a>
                                <span class="pager-info">Page <?= (int)$page ?> of <?= (int)$totalPages ?></span>
                                <a class="pager-btn" href="<?= $buildUrl(min($totalPages, $page+1)) ?>" aria-label="Next page"<?= $page >= $totalPages ? ' aria-disabled="true" tabindex="-1"' : '' ?>>Next ›</a>
                                <a class="pager-btn" href="<?= $buildUrl($totalPages) ?>" aria-label="Last page"<?= $page >= $totalPages ? ' aria-disabled="true" tabindex="-1"' : '' ?>>Last »</a>
                            </div>
                        </nav>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="announcement-empty">
                            <h3>No announcements yet</h3>
                            <p>Once you publish your first message, it will appear here for residents and field teams.</p>
                        </div>
                    <?php endif; ?>
                </section>

                <aside class="admin-section announcement-composer" aria-labelledby="compose-heading" id="compose">
                    <div class="admin-section-header">
                        <div>
                            <h2 id="compose-heading">Create announcement</h2>
                            <p class="admin-section-subtitle">Share advisories, closures, or safety reminders in just a few steps.</p>
                        </div>
                    </div>
                    <form method="post" class="announcement-form" enctype="multipart/form-data" novalidate>
                        <input type="hidden" name="action" value="add_announcement">
                        <label>
                            <div class="announcement-label-header">
                                <span>Headline</span>
                                <small class="char-counter"><span id="headline-count">0</span>/100</small>
                            </div>
                            <input type="text" name="announcement_title" placeholder="e.g. Road closure along JP Rizal" maxlength="100" required>
                        </label>
                        <label>
                            <div class="announcement-label-header">
                                <span>Message</span>
                                <small class="char-counter"><span id="message-count">0</span>/300</small>
                            </div>
                            <textarea name="announcement_body" rows="6" placeholder="Add key details, affected areas, and timelines." maxlength="300" required></textarea>
                        </label>
                        <label class="announcement-file-label">
                            <span>Featured image (optional)</span>
                            <input type="file" name="announcement_image" accept="image/*">
                        </label>
                        <button type="submit" class="announcement-submit">Publish announcement</button>
                    </form>
                    <div class="announcement-hint">
                        <p><strong>Need ideas?</strong> Use this space for weather alerts, traffic reroutes, or emergency hotlines. Messages push instantly to the citizen portal.</p>
                    </div>
                </aside>
            </div>
        </main>
    </div>
    <!-- Announcements Modal -->
    <div id="announcementsModal" class="modal" hidden>
        <div class="modal-content">
            <div class="modal-header">
                <h2>All Announcements</h2>
                <button type="button" class="modal-close" aria-label="Close modal">&times;</button>
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
    <script src="<?= $BASE ?>/assets/js/script.js?v=<?= $jsVersion ?>" defer></script>
</body>
</html>
