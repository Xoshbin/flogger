<?php

namespace Xoshbin\Flogger\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\File;
use Filament\Notifications\Notification;

class LogViewer extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'flogger::pages.log-viewer';

    protected static ?string $navigationLabel = 'Log Viewer';

    protected static ?string $title = 'Log Viewer';

    public $logFiles = [];
    public $logLines = [];
    public $selectedDate = null;
    public $expandedLogIndex = null;
    public $confirmingDelete = false;
    public $fileToDelete = null;

    public function mount()
    {
        // Fetch log files from storage/logs
        $this->logFiles = collect(File::files(storage_path('logs')))
            ->map(function ($file) {
                return [
                    'date' => $file->getFilenameWithoutExtension(),
                    'path' => $file->getRealPath(),
                    'size' => $this->formatBytes(filesize($file->getRealPath())), // File size
                ];
            })->sortByDesc('date')->values()->toArray();
    }

    public function loadLogs($date)
    {
        $this->selectedDate = $date;
        $this->expandedLogIndex = null;

        // Find the file path for the selected date
        $filePath = collect($this->logFiles)->firstWhere('date', $date)['path'] ?? null;

        if ($filePath && File::exists($filePath)) {
            $fileContent = File::get($filePath);

            // Match log entries using regex to split them
            preg_match_all(
                '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s([a-zA-Z.]+):\s(.*?)((?=\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\])|$)/s',
                $fileContent,
                $matches,
                PREG_SET_ORDER
            );

            $this->logLines = collect($matches)
                ->map(function ($match, $index) {
                    return [
                        'timestamp' => $match[1], // Timestamp
                        'type' => strtolower(explode('.', $match[2])[1] ?? 'unknown'), // Log type (e.g., error, info)
                        'excerpt' => substr(trim($match[3]), 0, 100), // Excerpt
                        'full' => trim($match[3]), // Full log entry
                        'index' => $index,
                    ];
                })
                ->toArray();
        } else {
            $this->logLines = [];
        }
    }

    function getLogLineClass($type)
    {
        return match ($type) {
            'emergency' => 'border-emergency',
            'alert' => 'border-alert',
            'critical' => 'border-critical',
            'error' => 'border-error',
            'warning' => 'border-warning',
            'info' => 'border-info',
            'notice' => 'border-notice',
            'debug' => 'border-debug',
            default => 'border-debug',
        };
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public function toggleLogExpansion($index)
    {
        $this->expandedLogIndex = $this->expandedLogIndex === $index ? null : $index;
    }


    public function deleteLogFile($date)
    {
        // Find the file path for the selected date
        $filePath = collect($this->logFiles)->firstWhere('date', $date)['path'] ?? null;

        if ($filePath && File::exists($filePath)) {
            File::delete($filePath);
            $this->logFiles = collect(File::files(storage_path('logs')))
                ->map(function ($file) {
                    return [
                        'date' => $file->getFilenameWithoutExtension(),
                        'path' => $file->getRealPath(),
                        'size' => $this->formatBytes(filesize($file->getRealPath())),
                    ];
                })->sortByDesc('date')->values()->toArray();

            if ($this->selectedDate === $date) {
                $this->selectedDate = null;
                $this->logLines = [];
            }

            Notification::make()
                ->title('Log file deleted successfully!')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Log file not found!')
                ->danger()
                ->send();
        }
    }
}
