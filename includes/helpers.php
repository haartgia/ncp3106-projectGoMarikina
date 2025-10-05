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

