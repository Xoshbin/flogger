<?php

namespace Xoshbin\Flogger\Pages;

use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use UnitEnum;

class LogViewer extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'flogger::pages.log-viewer';

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

    protected int $chunkSize;

    public function __construct()
    {
        $this->chunkSize = config('flogger.chunk_size', 50 * 1024);
    }

    public function loadLogs($date)
    {
        $this->selectedDate = $date;
        $this->expandedLogIndex = null;
        $this->page = 1;

        // Find the file path for the selected date
        $filePath = collect($this->logFiles)->firstWhere('date', $date)['path'] ?? null;

        if ($filePath && File::exists($filePath)) {
            $fileSize = filesize($filePath);
            $this->totalPages = ceil($fileSize / $this->chunkSize);
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
        $offset = max(0, $fileSize - ($this->page * $this->chunkSize));
        $length = $this->chunkSize;

        // Adjust length if we are at the beginning of the file and the chunk is smaller than MAX_LOG_SIZE
        if ($offset == 0) {
            $length = $fileSize - (($this->totalPages - 1) * $this->chunkSize);
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
            '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s([^:]+):\s(.*?)((?=\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\])|$)/s',
            $fileContent,
            $matches,
            PREG_SET_ORDER
        );

        $this->logLines = collect($matches)
            ->map(function ($match, $index) {
                $parts = explode('.', $match[2]);
                $content = mb_convert_encoding($match[3], 'UTF-8', 'UTF-8');

                return [
                    'timestamp' => $match[1], // Timestamp
                    'type' => strtolower(end($parts) ?: 'unknown'), // Log type (e.g., error, info)
                    'excerpt' => mb_substr(trim($content), 0, 100), // Excerpt
                    'full' => trim($content), // Full log entry
                    'index' => $index,
                ];
            })
            ->toArray();
    }

    public function getLogLineClass($type)
    {
        return match ($type) {
            'emergency' => 'fl-border-l-4 fl-border-red-700 dark:fl-border-red-600 fl-bg-red-50 dark:fl-bg-red-900/10',
            'alert' => 'fl-border-l-4 fl-border-red-600 dark:fl-border-red-500 fl-bg-red-50 dark:fl-bg-red-900/10',
            'critical' => 'fl-border-l-4 fl-border-red-500 dark:fl-border-red-400 fl-bg-red-50 dark:fl-bg-red-900/10',
            'error' => 'fl-border-l-4 fl-border-red-500 dark:fl-border-red-400 fl-bg-red-50 dark:fl-bg-red-900/10',
            'warning' => 'fl-border-l-4 fl-border-yellow-500 dark:fl-border-yellow-400 fl-bg-yellow-50 dark:fl-bg-yellow-900/10',
            'info' => 'fl-border-l-4 fl-border-blue-500 dark:fl-border-blue-400 fl-bg-blue-50 dark:fl-bg-blue-900/10',
            'notice' => 'fl-border-l-4 fl-border-sky-400 dark:fl-border-sky-300 fl-bg-sky-50 dark:fl-bg-sky-900/10',
            'debug' => 'fl-border-l-4 fl-border-gray-400 dark:fl-border-gray-500 fl-bg-gray-50 dark:fl-bg-gray-800/50',
            default => 'fl-border-l-4 fl-border-gray-300 dark:fl-border-gray-600',
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
