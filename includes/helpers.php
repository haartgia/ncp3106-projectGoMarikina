<?php

if (!function_exists('parse_datetime_string')) {
    function parse_datetime_string(?string $value): ?DateTimeImmutable
    {
        if (!$value) {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            $formats = [
                'Y-m-d H:i:sP',
                'Y-m-d H:i:s',
                'Y-m-d H:i',
                DateTimeInterface::ATOM,
                DATE_ISO8601,
            ];

            foreach ($formats as $format) {
                $dateTime = \DateTimeImmutable::createFromFormat($format, $value);
                if ($dateTime instanceof \DateTimeImmutable) {
                    return $dateTime;
                }
            }
        }

        return null;
    }
}

if (!function_exists('format_datetime_display')) {
    function format_datetime_display(?string $value, string $format = 'M j, Y · g:i A'): string
    {
        $dateTime = parse_datetime_string($value);

        if (!$dateTime) {
            return $value ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : '—';
        }

        return $dateTime->format($format);
    }
}

if (!function_exists('format_datetime_attr')) {
    function format_datetime_attr(?string $value): string
    {
        $dateTime = parse_datetime_string($value);

        if (!$dateTime) {
            return $value ?? '';
        }

    return $dateTime->format(\DateTimeInterface::ATOM);
    }
}

if (!function_exists('status_label')) {
    function status_label(string $status): string
    {
        $normalized = strtolower($status);

        return match ($normalized) {
            'in_progress', 'in-progress' => 'In progress',
            'solved', 'resolved' => 'Solved',
            default => 'Unresolved',
        };
    }
}

if (!function_exists('status_chip_modifier')) {
    function status_chip_modifier(string $status): string
    {
        $normalized = strtolower($status);
        $normalized = str_replace('_', '-', $normalized);

        return match ($normalized) {
            'in-progress' => 'in-progress',
            'solved', 'resolved' => 'solved',
            default => 'unresolved',
        };
    }
}

if (!function_exists('category_label')) {
    /**
     * Convert stored category slugs like "public_safety" or "road-hazard"
     * into a human-friendly label: "Public Safety", "Road Hazard".
     */
    function category_label(?string $category): string
    {
        if ($category === null) {
            return 'Report';
        }

        $raw = trim((string)$category);
        if ($raw === '') {
            return 'Report';
        }

        // Normalize to slug-style key
        $key = strtolower($raw);
        $key = str_replace([' ', '-'], '_', $key);
        $key = preg_replace('/_+/', '_', $key);

        // Explicit mappings (authoritative display names)
        $map = [
            'public_safety'     => 'Public Safety & Infrastructure',
            'cleanliness'       => 'Cleanliness & Environment',
            'public_facilities' => 'Public Facilities',
            'community'         => 'Community',
            'other'             => 'Other Concerns',
        ];

        if (array_key_exists($key, $map)) {
            return $map[$key];
        }

        // Fallback: make a best-effort pretty label
        $s = str_replace(['_', '-'], ' ', $raw);
        $s = preg_replace('/\s+/', ' ', $s);
        if (function_exists('mb_convert_case')) {
            return mb_convert_case($s, MB_CASE_TITLE, 'UTF-8');
        }
        return ucwords(strtolower($s));
    }
}

if (!function_exists('user_initials')) {
    /**
     * Return initials from a user array or provided name components.
     * Looks for common keys: first_name/last_name or name/full_name.
     */
    function user_initials(?array $user = null, ?string $fallback = 'User'): string
    {
        $first = '';
        $last = '';
        $display = '';

        if (is_array($user)) {
            $first = trim((string)($user['first_name'] ?? $user['firstName'] ?? ''));
            $last  = trim((string)($user['last_name'] ?? $user['lastName'] ?? ''));
            $display = trim((string)($user['name'] ?? $user['full_name'] ?? ''));
        }

        if ($first !== '' || $last !== '') {
            $a = $first !== '' ? mb_substr($first, 0, 1, 'UTF-8') : '';
            $b = $last !== '' ? mb_substr($last, 0, 1, 'UTF-8') : '';
            return strtoupper($a . $b);
        }

        if ($display !== '') {
            // Take first and last word initials
            $parts = preg_split('/\s+/', $display);
            if ($parts && count($parts) > 0) {
                $firstCh = mb_substr($parts[0], 0, 1, 'UTF-8');
                $lastCh = mb_substr($parts[count($parts)-1], 0, 1, 'UTF-8');
                return strtoupper($firstCh . $lastCh);
            }
        }

        return (string)$fallback;
    }
}

if (!function_exists('summarize_location')) {
    /**
     * Produce a short, human-friendly summary for long location strings.
     * Strategy:
     *  - Split on commas and keep the first N components (default 2 for tighter cards).
     *  - Trim and join with commas. If the result is still longer than
     *    $maxLen, truncate with an ellipsis.
     *  - If the original had more components than kept, append an ellipsis.
     */
    function summarize_location(?string $location, int $parts = 2, int $maxLen = 40): string
    {
        $location = trim((string)$location);
        if ($location === '') {
            return '';
        }

        $chunks = array_filter(array_map('trim', explode(',', $location)), fn($v) => $v !== '');
        if (count($chunks) === 0) {
            return $location;
        }

        $keep = array_slice($chunks, 0, max(1, $parts));
        $out = implode(', ', $keep);

        // Use multibyte-safe functions when available
        $len = function_exists('mb_strlen') ? mb_strlen($out, 'UTF-8') : strlen($out);
        if ($len > $maxLen) {
            $out = (function_exists('mb_substr') ? mb_substr($out, 0, $maxLen - 1, 'UTF-8') : substr($out, 0, $maxLen - 1)) . '…';
        } elseif (count($chunks) > count($keep)) {
            $out .= '…';
        }

        return $out;
    }
}

if (!function_exists('truncate_text')) {
    /**
     * Truncate a string to a maximum length and append ellipsis.
     * Uses multibyte-safe functions when available.
     *
     * Examples:
     *  truncate_text('Hello world', 5) => 'Hello...'
     *  truncate_text('Short', 25) => 'Short'
     */
    function truncate_text(?string $value, int $limit = 25, string $ellipsis = '...'): string
    {
        $s = trim((string)$value);
        if ($s === '') {
            return '';
        }

        $len = function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);
        if ($len <= $limit) {
            return $s;
        }

        $slice = function_exists('mb_substr') ? mb_substr($s, 0, $limit, 'UTF-8') : substr($s, 0, $limit);
        return rtrim($slice) . $ellipsis;
    }
}

if (!function_exists('safe_image_src')) {
    /**
     * Return a safe image src or empty string if not resolvable.
     * - Allows absolute URLs (http/https) and data URIs.
     * - For relative paths like uploads/..., checks file exists before returning.
     */
    function safe_image_src(?string $path): string
    {
        $s = trim((string)$path);
        if ($s === '') return '';
        
        // Absolute URL or data URI
        if (preg_match('#^(https?:)?//#', $s) || str_starts_with($s, 'data:')) {
            return $s;
        }
        
        // Relative to project root
        if (str_starts_with($s, 'uploads/')) {
            $abs = __DIR__ . '/../' . $s;
            if (file_exists($abs)) {
                return $s; // serve as-is
            }
            return '';
        }
        
        return '';
    }
}

