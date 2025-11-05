<?php
// Harden session cookie settings before starting the session
if (session_status() !== PHP_SESSION_ACTIVE) {
    // Ensure sessions are stored in a stable, writable path (helps on hosts where default /tmp isn't shared/persistent)
    $defaultPath = __DIR__ . '/../tmp_sessions';
    $savePath = $defaultPath;
    try {
        if (!is_dir($defaultPath)) { @mkdir($defaultPath, 0777, true); }
        if (!is_writable($defaultPath)) { $savePath = sys_get_temp_dir(); }
    } catch (Throwable $e) { $savePath = sys_get_temp_dir(); }
    if (is_string($savePath) && $savePath !== '') { @session_save_path($savePath); }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    // Ensure cookies work across the whole site and are HTTP-only
    // SameSite=Lax allows POST->redirect flows to carry the cookie
    if (function_exists('session_set_cookie_params')) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    // Use a custom session name to avoid conflicts with other PHP apps on the same host
    if (function_exists('session_name')) {
        @session_name('GOMKSESSID');
    }
    // Strengthen session behavior
    if (function_exists('ini_set')) {
        @ini_set('session.use_strict_mode', '1');
        @ini_set('session.use_only_cookies', '1');
        @ini_set('session.cookie_httponly', '1');
        // Allow Lax cookie for top-level POST redirects; upgrade to Strict if app is fully same-site navigations
        @ini_set('session.cookie_samesite', 'Lax');
    }
    session_start();
}

const ADMIN_EMAIL = 'admin';
const ADMIN_PASSWORD = 'admin';

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

function is_admin(): bool
{
    return isset($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'admin';
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_admin(): void
{
    if (!is_admin()) {
        header('Location: profile.php');
        exit;
    }
}

function initialize_demo_state(): void
{
    if (!isset($_SESSION['reports'])) {
        $_SESSION['reports'] = [
            [
                'id' => 1,
                'title' => 'Flooding at Bulelak Street',
                'category' => 'Community',
                'status' => 'unresolved',
                'reporter' => 'Miguel De Guzman',
                'location' => 'Barangay San Roque',
                'submitted_at' => '2025-09-20 08:24',
                'summary' => 'Heavy rainfall overnight caused knee-deep flooding along Bulelak Street. Residents report difficulty accessing tricycle terminals and need drainage assistance.',
                'image' => 'uploads/flooding.png',
                'tags' => ['community', 'flooding', 'drainage'],
            ],
            [
                'id' => 2,
                'title' => 'Illegal Parking along Riverbanks',
                'category' => 'Public Safety',
                'status' => 'in_progress',
                'reporter' => 'Aira Mendoza',
                'location' => 'Riverbanks Center',
                'submitted_at' => '2025-09-22 14:08',
                'summary' => 'Multiple private vehicles are blocking the emergency lane at Riverbanks. Traffic aides have already been notified but require towing support.',
                'image' => 'uploads/no-parking.png',
                'tags' => ['public-safety', 'traffic', 'riverbanks'],
            ],
            [
                'id' => 3,
                'title' => 'Potholes at J.P. Rizal',
                'category' => 'Infrastructure',
                'status' => 'solved',
                'reporter' => 'Luis Santos',
                'location' => 'J.P. Rizal Street',
                'submitted_at' => '2025-09-26 09:47',
                'summary' => 'Large potholes have appeared near the public market. DPWH crew already patched the affected lane and reopened traffic.',
                'image' => 'uploads/road-construction.png',
                'tags' => ['infrastructure', 'roads'],
            ],
            [
                'id' => 4,
                'title' => 'Riverbank tree trimming',
                'category' => 'Maintenance',
                'status' => 'unresolved',
                'reporter' => 'Jessa Cruz',
                'location' => 'Marikina River Park',
                'submitted_at' => '2025-09-29 07:32',
                'summary' => 'Overgrown branches are leaning over the jogging path and risk falling on passersby. Residents request trimming before the weekend fun run.',
                'image' => null,
                'tags' => ['maintenance', 'parks'],
            ],
        ];
    }

    if (!isset($_SESSION['announcements'])) {
        $_SESSION['announcements'] = [
            [
                'id' => 1,
                'title' => 'Scheduled road repairs on Shoe Avenue',
                'body' => 'Maintenance crews will be onsite from Oct 8-10. Expect partial lane closures.',
                'created_at' => date('c'),
                'image' => null,
            ],
        ];
    }
}

initialize_demo_state();
