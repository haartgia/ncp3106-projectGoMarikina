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

    // Configure cookie + GC lifetime (env override supported)
    $lifetime = 0; // default: session cookie (until browser is closed)
    $envLifetime = getenv('SESSION_LIFETIME');
    if ($envLifetime !== false && ctype_digit((string)$envLifetime)) {
        $lifetime = max(0, (int)$envLifetime);
    } else {
        // Use a sensible default of 7 days for more persistent logins
        $lifetime = 60 * 60 * 24 * 7; // 604800 seconds
    }

    // Detect HTTPS robustly (supports proxies/CDNs)
    $xfProto = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    $xfSsl   = strtolower((string)($_SERVER['HTTP_X_FORWARDED_SSL'] ?? ''));
    $cfVis   = (string)($_SERVER['HTTP_CF_VISITOR'] ?? ''); // e.g., {"scheme":"https"}
    $cfHttps = stripos($cfVis, '"https"') !== false;
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443')
        || ($xfProto === 'https')
        || ($xfSsl === 'on')
        || $cfHttps;

    // Derive a stable cookie path that works across the whole app folder
    // Priority 1: allow override via env APP_BASE_PATH (e.g., "/gomarikina/")
    $basePathEnv = getenv('APP_BASE_PATH');
    $basePath = '';
    if (is_string($basePathEnv) && $basePathEnv !== '') {
        $bp = str_replace('\\', '/', trim($basePathEnv));
        if ($bp === '' || $bp === '.' || $bp === '/') {
            $basePath = '/';
        } else {
            if ($bp[0] !== '/') { $bp = '/' . $bp; }
            if (substr($bp, -1) !== '/') { $bp .= '/'; }
            $basePath = $bp;
        }
    } else {
        // Priority 2: infer from script path by locating the project folder name
        $scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '/');
        $projectFolder = basename(dirname(__DIR__)); // e.g., "gomarikina"
        $needle = '/' . $projectFolder . '/';
        if (strpos($scriptName, $needle) !== false) {
            $basePath = $needle; // e.g., "/gomarikina/"
        } else {
            // Fallback: site root
            $basePath = '/';
        }
    }

    // Only set cookie domain when it is a plain hostname (avoid setting for IPs)
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    $host = preg_replace('/:\\d+$/', '', $host); // strip port
    $isIp = (bool)preg_match('/^\d+\.\d+\.\d+\.\d+$/', $host);
    $cookieDomain = $isIp || $host === 'localhost' || $host === '' ? '' : $host;

    // Ensure cookies work across the app and are HTTP-only; SameSite=Lax supports POST->redirect
    if (function_exists('session_set_cookie_params')) {
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => $basePath ?: '/',
            'domain' => $cookieDomain,
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    // Use a custom session name to avoid conflicts with other PHP apps on the same host
    if (function_exists('session_name')) {
        @session_name('GOMKSESSID');
    }
    // Strengthen session behavior and align GC to cookie lifetime
    if (function_exists('ini_set')) {
        @ini_set('session.use_strict_mode', '1');
        @ini_set('session.use_only_cookies', '1');
        @ini_set('session.cookie_httponly', '1');
        // Allow Lax cookie for top-level POST redirects; upgrade to Strict if app is fully same-site navigations
        @ini_set('session.cookie_samesite', 'Lax');
        // Keep server-side session files around at least as long as the cookie
        @ini_set('session.gc_maxlifetime', (string)max(1440, $lifetime));
        // Reasonable GC defaults (1% probability)
        @ini_set('session.gc_probability', '1');
        @ini_set('session.gc_divisor', '100');
    }
    // Avoid default cache limiter adding no-cache headers that can interfere with back/forward nav
    if (function_exists('session_cache_limiter')) { @session_cache_limiter(''); }
    session_start();
    // Proactively refresh the cookie expiry on each request when using a finite lifetime
    if ($lifetime > 0) {
        try {
            setcookie(session_name(), session_id(), [
                'expires' => time() + (int)$lifetime,
                'path' => $basePath ?: '/',
                'domain' => $cookieDomain ?: '',
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        } catch (Throwable $e) { /* ignore */ }
    }
}

const ADMIN_EMAIL = 'admin';
const ADMIN_PASSWORD = 'admin';

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

function is_admin(): bool
{
    if (!isset($_SESSION['user'])) return false;
    $role = strtolower(trim((string)($_SESSION['user']['role'] ?? '')));
    if (in_array($role, ['admin','administrator','superadmin','super_admin'], true)) return true;
    // Optional override: allow specific emails to be treated as admin via env ADMIN_EMAILS="a@x.com,b@y.com"
    $email = strtolower(trim((string)($_SESSION['user']['email'] ?? '')));
    $adminEmails = getenv('ADMIN_EMAILS');
    if ($adminEmails) {
        $list = array_filter(array_map('trim', explode(',', strtolower($adminEmails))));
        if ($email !== '' && in_array($email, $list, true)) return true;
    }
    return false;
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
