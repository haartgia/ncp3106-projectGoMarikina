<?php
// Hardened, hosted-friendly session bootstrap
if (session_status() !== PHP_SESSION_ACTIVE) {
    // Detect HTTPS behind proxies (Cloudflare/ELB/etc.)
    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) ||
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
        (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
    );

    // Normalize cookie domain (strip port)
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host) {
        $host = preg_replace('/[:]\d+$/', '', $host);
    }

    // Use a stable custom session name to avoid collisions with other apps on the same host
    if (!headers_sent()) {
        @session_name('GOMKSESSID');
    }

    // Configure cookie params with SameSite and Secure where applicable
    $cookieParams = [
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => $host ?: '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax', // Safe default for regular navigation
    ];

    // Apply cookie params (PHP >= 7.3 supports array with samesite)
    if (PHP_VERSION_ID >= 70300) {
        @session_set_cookie_params($cookieParams);
    } else {
        // Fallback for very old PHP: best-effort via ini settings (no explicit samesite)
        @ini_set('session.cookie_secure', $isHttps ? '1' : '0');
        @ini_set('session.cookie_httponly', '1');
        if ($host) { @ini_set('session.cookie_path', '/'); @ini_set('session.cookie_domain', $host); }
    }

    // Use strict mode to prevent uninitialized session fixation
    @ini_set('session.use_strict_mode', '1');

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
