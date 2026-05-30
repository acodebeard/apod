<?php

function apod_asset_url(string $path): string
{
    $relative = ltrim($path, '/');
    $file = APOD_ROOT . '/' . $relative;
    $version = is_file($file) ? (string) filemtime($file) : '1';

    return APOD_BASE_PATH . '/' . $relative . '?v=' . rawurlencode($version);
}
