<?php

/**
 * Run on servers without Composer after pulling a release that removed app/helpers.php:
 * php scripts/remove-helpers-autoload.php
 */

$root = dirname(__DIR__);
$targets = [
    $root.'/vendor/composer/autoload_files.php',
    $root.'/vendor/composer/autoload_static.php',
];

foreach ($targets as $file) {
    if (! is_file($file)) {
        fwrite(STDERR, "Skip (missing): {$file}\n");
        continue;
    }

    $contents = file_get_contents($file);
    $updated = preg_replace(
        "/\\s*'[a-f0-9]+' => [^\n]*\\/app\\/helpers\\.php',?\n/",
        "\n",
        $contents
    );

    if ($updated === null || $updated === $contents) {
        fwrite(STDOUT, "No app/helpers.php entry in: {$file}\n");
        continue;
    }

    file_put_contents($file, $updated);
    fwrite(STDOUT, "Updated: {$file}\n");
}

fwrite(STDOUT, "Done. Run: php artisan optimize:clear && php artisan view:clear\n");
