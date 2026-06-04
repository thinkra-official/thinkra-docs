<?php

if (! function_exists('asset_version')) {
    /**
     * Public asset URL with a cache-busting query (?v=) from file mtime or APP_ASSET_VERSION.
     */
    function asset_version(string $path): string
    {
        $path = ltrim($path, '/');
        $url = asset($path);

        $override = config('app.asset_version');
        if ($override !== null && $override !== '') {
            return $url.'?v='.rawurlencode((string) $override);
        }

        $fullPath = public_path($path);
        if (is_file($fullPath)) {
            return $url.'?v='.filemtime($fullPath);
        }

        return $url;
    }
}
