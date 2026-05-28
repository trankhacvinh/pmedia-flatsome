<?php
if (!defined('ABSPATH')) { exit; }

if (!function_exists('pmfai_str_starts_with')) {
    function pmfai_str_starts_with(string $haystack, string $needle): bool
    {
        if ($needle === '') { return true; }
        return substr($haystack, 0, strlen($needle)) === $needle;
    }
}

if (!function_exists('pmfai_str_ends_with')) {
    function pmfai_str_ends_with(string $haystack, string $needle): bool
    {
        if ($needle === '') { return true; }
        return substr($haystack, -strlen($needle)) === $needle;
    }
}

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return pmfai_str_starts_with($haystack, $needle);
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool
    {
        return pmfai_str_ends_with($haystack, $needle);
    }
}
