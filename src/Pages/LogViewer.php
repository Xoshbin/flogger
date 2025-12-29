<?php

namespace Xoshbin\Flogger\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LogViewer extends Page
{
    protected static ?string $navigationGroup = 'Settings';

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
        $excludedFiles = config('flogger.exclude_files', []);

        $this->logFiles = collect(File::files(storage_path('logs')))
            ->filter(function ($file) use ($excludedFiles) {
                foreach ($excludedFiles as $pattern) {
                    if (Str::is($pattern, $file->getFilename())) {
                        return false;
                    }
                }

                return true;
            })
            ->map(function ($file) {
                try {
                    $size = $this->formatBytes(filesize($file->getRealPath()));
                } catch (\Exception $e) {
                    $size = 'Unknown';
                }

                return [
                    'date' => $file->getFilenameWithoutExtension(),
                    'path' => $file->getRealPath(),
                    'size' => $size,
                ];
            })->sortByDesc('date')->values()->toArray();
    }

    public $page = 1;

    public $totalPages = 1;

    // Define the maximum size to read (2MB)
    const MAX_LOG_SIZE = 2 * 1024 * 1024; // 2MB

    public function loadLogs($date)
    {
        $this->selectedDate = $date;
        $this->expandedLogIndex = null;
        $this->page = 1;

        // Find the file path for the selected date
        $filePath = collect($this->logFiles)->firstWhere('date', $date)['path'] ?? null;

        if ($filePath && File::exists($filePath)) {
            $fileSize = filesize($filePath);
            $this->totalPages = ceil($fileSize / self::MAX_LOG_SIZE);
            // Ensure at least 1 page
            if ($this->totalPages < 1) {
                $this->totalPages = 1;
            }

            $this->loadChunk($filePath);
        } else {
            $this->logLines = [];
            $this->totalPages = 1;
        }
    }

    public function nextPage()
    {
        if ($this->page < $this->totalPages) {
            $this->page++;
            $this->refreshLogs();
        }
    }

    public function previousPage()
    {
        if ($this->page > 1) {
            $this->page--;
            $this->refreshLogs();
        }
    }

    public function refreshLogs()
    {
        $filePath = collect($this->logFiles)->firstWhere('date', $this->selectedDate)['path'] ?? null;
        if ($filePath) {
            $this->loadChunk($filePath);
        }
    }

    protected function loadChunk($filePath)
    {
        $fileSize = filesize($filePath);
        $offset = max(0, $fileSize - ($this->page * self::MAX_LOG_SIZE));
        $length = self::MAX_LOG_SIZE;

        // Adjust length if we are at the beginning of the file and the chunk is smaller than MAX_LOG_SIZE
        if ($offset == 0) {
            $length = $fileSize - (($this->totalPages - 1) * self::MAX_LOG_SIZE);
        }

        try {
            $handle = fopen($filePath, 'rb');
            fseek($handle, $offset);
            $fileContent = fread($handle, $length);
            fclose($handle);

            // Cleanup partial lines if we are not at the very beginning of the file
            if ($offset > 0) {
                $firstNewline = strpos($fileContent, "\n");
                if ($firstNewline !== false) {
                    $fileContent = substr($fileContent, $firstNewline + 1);
                }

                // Extra safety: ensure we start with a timestamp
                if (preg_match('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $fileContent, $match, PREG_OFFSET_CAPTURE)) {
                    $fileContent = substr($fileContent, $match[0][1]);
                }
            }
        } catch (\Exception $e) {
            $fileContent = '';
        }

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
    }

    public function getLogLineClass($type)
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

        return round($bytes, $precision).' '.$units[$pow];
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
