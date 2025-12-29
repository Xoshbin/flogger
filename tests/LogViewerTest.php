<?php

namespace Tests;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Xoshbin\Flogger\Pages\LogViewer;

class LogViewerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Setup a temporary logs directory
        $this->app->useStoragePath(__DIR__.'/temp_storage');
        File::ensureDirectoryExists(storage_path('logs'));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path());
        parent::tearDown();
    }

    /** @test */
    public function it_excludes_files_defined_in_config()
    {
        // verify config is loaded/set
        Config::set('flogger.exclude_files', ['schedule-*']);

        // Create dummy log files
        $logsPath = storage_path('logs');
        File::put($logsPath.'/laravel.log', 'test log content');
        File::put($logsPath.'/schedule-2023-01-01.log', 'schedule log content'); // Should be excluded
        File::put($logsPath.'/other.log', 'other content');

        // Instantiate LogViewer and run mount
        $viewer = new LogViewer;
        $viewer->mount();

        $files = collect($viewer->logFiles)->pluck('date')->toArray();

        $this->assertContains('laravel', $files);
        $this->assertContains('other', $files);
        $this->assertNotContains('schedule-2023-01-01', $files);
    }

    /** @test */
    public function it_handles_missing_files_gracefully()
    {
        // This test simulates the file_get_contents error if we try to read a non-existent file
        // logically, LogViewer checks file existence before reading, so let's verify that.

        $viewer = new LogViewer;
        $viewer->logFiles = [
            ['date' => 'missing-file', 'path' => storage_path('logs/missing.log'), 'size' => 0],
        ];

        // Try to load a missing file
        $viewer->loadLogs('missing-file');

        // Should have empty logLines and no errors thrown
        $this->assertEmpty($viewer->logLines);
    }
}
