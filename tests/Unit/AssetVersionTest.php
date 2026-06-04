<?php

namespace Tests\Unit;

use Tests\TestCase;

class AssetVersionTest extends TestCase
{
    public function test_asset_version_appends_file_mtime_query(): void
    {
        $path = 'css/docs-reader.css';
        $fullPath = public_path($path);

        $this->assertFileExists($fullPath);

        $url = asset_version($path);

        $this->assertStringContainsString(asset($path), $url);
        $this->assertStringContainsString('?v='.(string) filemtime($fullPath), $url);
    }

    public function test_asset_version_uses_config_override(): void
    {
        config(['app.asset_version' => 'deploy-99']);

        $url = asset_version('css/docs-reader.css');

        $this->assertStringEndsWith('?v=deploy-99', $url);
    }
}
