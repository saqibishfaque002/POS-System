<?php

/**
 * Vercel Serverless Function Entrypoint for Laravel
 *
 * Initializes required temporary directories in /tmp for the read-only
 * serverless filesystem before passing control to Laravel's front controller.
 */

// Ensure all writable framework directories exist in /tmp on cold start
$storagePath = '/tmp/storage';
$subDirs = [
    $storagePath . '/framework/views',
    $storagePath . '/framework/cache/data',
    $storagePath . '/framework/sessions',
    $storagePath . '/logs',
];

foreach ($subDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Forward the request to Laravel's standard public front controller
require __DIR__ . '/../public/index.php';
