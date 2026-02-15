<?php

namespace Tests;

use Illuminate\Support\Facades\File;
use Xoshbin\Flogger\Pages\LogViewer;

class LogParsingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->useStoragePath(__DIR__.'/temp_storage');
        File::ensureDirectoryExists(storage_path('logs'));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path());
        parent::tearDown();
    }

    /** @test */
    public function it_can_parse_logs_with_various_channel_names()
    {
        $logContent = <<<'LOG'
[2026-02-15 10:00:00] local.INFO: Standard log entry
[2026-02-15 10:01:00] api.v1.ERROR: Log with numbers in channel
[2026-02-15 10:02:00] my_custom_app.DEBUG: Log with underscore in channel
[2026-02-15 10:03:00] testing.INFO: 📥 Controller received message {"chat_id": 123}
[2026-02-15 10:04:00] system-logs.CRITICAL: Log with hyphen in channel
LOG;

        $logPath = storage_path('logs/test.log');
        File::put($logPath, $logContent);

        $viewer = new LogViewer;

        // Mock logFiles since mount would try to read real storage
        $viewer->logFiles = [
            ['date' => 'test', 'path' => $logPath, 'size' => '1 KB'],
        ];

        $viewer->loadLogs('test');

        $this->assertCount(5, $viewer->logLines);

        $this->assertEquals('info', $viewer->logLines[0]['type']); // local.INFO
        $this->assertEquals('error', $viewer->logLines[1]['type']); // api.v1.ERROR
        $this->assertEquals('debug', $viewer->logLines[2]['type']); // my_custom_app.DEBUG
        $this->assertEquals('info', $viewer->logLines[3]['type']); // testing.INFO
        $this->assertEquals('critical', $viewer->logLines[4]['type']); // system-logs.CRITICAL
    }

    /** @test */
    public function it_handles_very_long_log_messages()
    {
        $longMessage = str_repeat('A', 10000);
        $logContent = "[2026-02-15 10:00:00] local.INFO: $longMessage\n";

        $logPath = storage_path('logs/long.log');
        File::put($logPath, $logContent);

        $viewer = new LogViewer;
        $viewer->logFiles = [['date' => 'long', 'path' => $logPath, 'size' => '10 KB']];
        $viewer->loadLogs('long');

        $this->assertCount(1, $viewer->logLines);
        $this->assertEquals('info', $viewer->logLines[0]['type']);
        $this->assertStringContainsString('AAAAA', $viewer->logLines[0]['full']);
    }
}
