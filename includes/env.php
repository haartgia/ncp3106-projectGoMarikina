<?php
/**
 * Simple .env loader for local development.
 *
 * Reads key=value pairs from project root .env and calls putenv(),
 * populates $_ENV and $_SERVER if keys are not already set.
 * Lines starting with # are comments. Supports quoted values.
 */
function load_app_env(): void {
    static $loaded = false;
    if ($loaded) return;
    $loaded = true;

    $root = dirname(__DIR__);
    $candidates = [
        $root . DIRECTORY_SEPARATOR . '.env',
        $root . DIRECTORY_SEPARATOR . '.env.local'
    ];

    foreach ($candidates as $file) {
        if (!is_readable($file)) continue;
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) continue;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            // Allow inline comments using space + #
            if (strpos($line, ' #') !== false) {
                $line = preg_replace('/\s+#.*$/', '', $line);
            }
            $pos = strpos($line, '=');
            if ($pos === false) continue;
            $key = trim(substr($line, 0, $pos));
            $val = trim(substr($line, $pos + 1));
            if ($val !== '' && ($val[0] === '"' || $val[0] === "'")) {
                $quote = $val[0];
                if (substr($val, -1) === $quote) {
                    $val = substr($val, 1, -1);
                } else {
                    $val = substr($val, 1); // unbalanced, best effort
                }
            }
            // Do not override already set env
            if (getenv($key) === false) {
                putenv($key . '=' . $val);
                if (!isset($_ENV[$key])) $_ENV[$key] = $val;
                if (!isset($_SERVER[$key])) $_SERVER[$key] = $val;
            }
        }
    }
}
