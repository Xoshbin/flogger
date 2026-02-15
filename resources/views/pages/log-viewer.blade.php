<x-filament-panels::page>
    <div class="fl-flex fl-gap-6 fl-h-screen">
        <!-- Sidebar (fixed width) -->
        <div class="fl-w-96 fl-flex-shrink-0 fl-bg-white dark:fl-bg-gray-900 fl-shadow fl-rounded-lg fl-p-4 fl-ring-1 fl-ring-gray-950/5 dark:fl-ring-white/10">
            <h3 class="fl-text-lg fl-font-semibold fl-text-gray-800 dark:fl-text-gray-200 fl-mb-4">Log Files</h3>
            <ul class="fl-space-y-2">
                @foreach ($logFiles as $logFile)
                    <li wire:key="log-file-{{ $logFile['date'] }}" class="fl-flex fl-justify-between fl-items-center">
                        <button
                            class="fl-w-full fl-text-start fl-p-2 fl-bg-gray-100 dark:fl-bg-gray-800 fl-rounded-md hover:fl-bg-blue-100 dark:hover:fl-bg-blue-900 focus:fl-outline-none focus:fl-ring focus:fl-ring-blue-300 fl-transition fl-text-gray-900 dark:fl-text-gray-100"
                            wire:click="loadLogs('{{ $logFile['date'] }}')"
                        >
                            <div class="fl-flex fl-justify-between fl-items-center">
                                <span>{{ $logFile['date'] }}</span>
                                <span class="fl-text-sm fl-text-gray-500 dark:fl-text-gray-400">{{ $logFile['size'] }}</span>
                            </div>
                        </button>
                        <button
                            class="fl-p-2 fl-text-red-600 hover:fl-text-red-800"
                            wire:click="deleteLogFile('{{ $logFile['date'] }}')"
                            onclick="confirm('Are you sure you want to delete this log file?') || event.stopImmediatePropagation()"
                            title="Delete Log File"
                        >
                            ✖
                        </button>
                    </li>
                @endforeach
            </ul>

        </div>

        <!-- Logs Viewer (flexible width) -->
        <div class="fl-flex-1 fl-bg-white dark:fl-bg-gray-900 fl-shadow fl-rounded-lg fl-flex fl-flex-col fl-min-w-0 fl-ring-1 fl-ring-gray-950/5 dark:fl-ring-white/10">
            <div class="fl-p-4 fl-border-b fl-border-gray-200 dark:fl-border-gray-700 fl-flex-shrink-0">
                <div class="fl-flex fl-justify-between fl-items-center">
                    @if ($selectedDate)
                        <div class="fl-flex fl-items-center fl-gap-4">
                            <h2 class="fl-text-xl fl-font-bold fl-text-gray-800 dark:fl-text-gray-200">Logs for {{ $selectedDate }}</h2>
                            @if ($this->totalPages > 1)
                                <div class="fl-flex fl-items-center fl-gap-2 fl-bg-gray-50 dark:fl-bg-gray-800 fl-rounded-lg fl-px-3 fl-py-1 fl-border fl-border-gray-200 dark:fl-border-gray-700">
                                    <button
                                        wire:click="previousPage"
                                        wire:loading.attr="disabled"
                                        class="fl-px-2 fl-py-1 fl-text-sm fl-font-medium fl-rounded hover:fl-bg-white disabled:fl-opacity-50 disabled:fl-cursor-not-allowed fl-transition-colors {{ $this->page <= 1 ? 'fl-text-gray-400' : 'fl-text-blue-600 hover:fl-text-blue-700 hover:fl-shadow-sm' }}"
                                        @if($this->page <= 1) disabled @endif
                                    >
                                        &larr; Newer
                                    </button>
                                    <span class="fl-text-sm fl-text-gray-600 fl-font-medium fl-px-2 fl-border-l fl-border-r fl-border-gray-200">
                                        Page {{ $this->page }} of {{ $this->totalPages }}
                                    </span>
                                    <button
                                        wire:click="nextPage"
                                        wire:loading.attr="disabled"
                                        class="fl-px-2 fl-py-1 fl-text-sm fl-font-medium fl-rounded hover:fl-bg-white disabled:fl-opacity-50 disabled:fl-cursor-not-allowed fl-transition-colors {{ $this->page >= $this->totalPages ? 'fl-text-gray-400' : 'fl-text-blue-600 hover:fl-text-blue-700 hover:fl-shadow-sm' }}"
                                        @if($this->page >= $this->totalPages) disabled @endif
                                    >
                                        Older &rarr;
                                    </button>
                                </div>
                            @endif
                        </div>
                    @else
                        <h2 class="fl-text-xl fl-font-bold fl-text-gray-800 dark:fl-text-gray-200">Log Viewer</h2>
                    @endif
                </div>
            </div>

            <div class="fl-flex-1 fl-p-4 fl-overflow-y-auto">
                @if ($logLines)
                    <ul class="fl-space-y-4">
                        @foreach (collect($logLines)->reverse() as $index => $logLine)
                            <li wire:key="log-line-{{ $logLine['index'] }}">
                                <div
                                    class="fl-p-4 fl-rounded-lg fl-shadow-sm fl-border fl-cursor-pointer fl-bg-white dark:fl-bg-gray-800 hover:fl-bg-gray-100 dark:hover:fl-bg-gray-700 fl-transition-all {{ $this->getLogLineClass($logLine['type']) }}"
                                    wire:click="toggleLogExpansion({{ $index }})">
                                    <div class="fl-flex fl-justify-between fl-items-center fl-mb-2">
                                        <span class="fl-text-sm fl-font-semibold fl-text-gray-600 dark:fl-text-gray-400 fl-capitalize">{{ $logLine['type'] }}</span>
                                        <div class="fl-flex fl-items-center fl-gap-2" x-data="{ copied: false }">
                                            <span class="fl-text-sm fl-text-gray-500 dark:fl-text-gray-400">{{ $logLine['timestamp'] }}</span>
                                            <button
                                                x-on:click.stop="
                                                    const text = @js($logLine['full']);
                                                    if (window.navigator && window.navigator.clipboard) {
                                                        window.navigator.clipboard.writeText(text);
                                                        copied = true;
                                                    } else {
                                                        const textArea = document.createElement('textarea');
                                                        textArea.value = text;
                                                        document.body.appendChild(textArea);
                                                        textArea.focus();
                                                        textArea.select();
                                                        try {
                                                            document.execCommand('copy');
                                                            copied = true;
                                                        } catch (err) {
                                                            console.error('Fallback: Oops, unable to copy', err);
                                                        }
                                                        document.body.removeChild(textArea);
                                                    }
                                                    setTimeout(() => copied = false, 2000);
                                                "
                                                class="fl-flex fl-items-center fl-gap-1 fl-p-1 fl-px-2 fl-rounded-md fl-transition-colors focus:fl-outline-none fl-text-xs fl-font-medium fl-border"
                                                :class="copied ? 'flogger-copied-button' : 'fl-bg-white hover:fl-bg-gray-50 fl-text-gray-500 fl-border-gray-200'"
                                                title="Copy to clipboard"
                                            >
                                                <!-- Heroicon: clipboard -->
                                                <svg x-show="!copied" xmlns="http://www.w3.org/2000/svg" class="fl-h-3.5 fl-w-3.5 fl-text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                                </svg>
                                                <!-- Heroicon: check -->
                                                <svg x-show="copied" style="display: none;" xmlns="http://www.w3.org/2000/svg" class="fl-h-3.5 fl-w-3.5 flogger-copied-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <span x-show="copied" style="display: none;">Copied!</span>
                                                <span x-show="!copied">Copy</span>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="fl-mt-2 fl-text-sm fl-text-gray-800 dark:fl-text-gray-200 fl-overflow-x-auto fl-whitespace-pre-wrap fl-break-words fl-border-t dark:fl-border-gray-700 fl-pt-2 fl-text-left" dir="ltr">
                                        @if ($expandedLogIndex === $index)
                                            <pre class="fl-bg-gray-100 dark:fl-bg-gray-900 fl-p-2 fl-rounded-lg fl-text-sm fl-font-mono fl-text-gray-800 dark:fl-text-gray-200 fl-overflow-y-auto fl-whitespace-pre-wrap fl-text-left" dir="ltr">{{ $logLine['full'] }}</pre>
                                        @else
                                            <p class="fl-text-gray-700 dark:fl-text-gray-300">{{ $logLine['excerpt'] }}</p>
                                        @endif
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="fl-flex fl-items-center fl-justify-center fl-h-full fl-text-center fl-text-gray-500 dark:fl-text-gray-400">
                        @if ($selectedDate)
                            No logs available for this file.
                        @else
                            <div>
                                <p class="fl-text-lg fl-font-medium">No log file selected</p>
                                <p>Select a log file from the list to view its content.</p>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
