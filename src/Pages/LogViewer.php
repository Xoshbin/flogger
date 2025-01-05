<?php

namespace Xoshbin\Flogger\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\File;

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
    public $search = '';

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
                ->filter(function ($log) {
                    // Filter logs based on search query
                    return str_contains(strtolower($log['full']), strtolower($this->search));
                })
                ->toArray();
        } else {
            $this->logLines = [];
        }
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

    public function updatedSearch()
    {
        if ($this->selectedDate) {
            $this->loadLogs($this->selectedDate);
        }
    }
}
