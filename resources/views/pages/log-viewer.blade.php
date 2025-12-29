<x-filament-panels::page>
    <div class="flex gap-6 h-screen" dir="ltr">
        <!-- Sidebar (fixed width) -->
        <div class="w-96 flex-shrink-0 bg-white shadow rounded-lg p-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Log Files</h3>
            <ul class="space-y-2">
                @foreach ($logFiles as $logFile)
                    <li class="flex justify-between items-center">
                        <button
                            class="w-full text-left p-2 bg-gray-100 rounded-md hover:bg-blue-100 focus:outline-none focus:ring focus:ring-blue-300 transition"
                            wire:click="loadLogs('{{ $logFile['date'] }}')"
                        >
                            <div class="flex justify-between items-center">
                                <span>{{ $logFile['date'] }}</span>
                                <span class="text-sm text-gray-500">{{ $logFile['size'] }}</span>
                            </div>
                        </button>
                        <button
                            class="p-2 text-red-600 hover:text-red-800"
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
        <div class="flex-1 bg-white shadow rounded-lg flex flex-col min-w-0">
            <div class="p-4 border-b border-gray-200 flex-shrink-0">
                <div class="flex justify-between items-center">
                    @if ($selectedDate)
                        <h2 class="text-xl font-bold text-gray-800">Logs for {{ $selectedDate }}</h2>
                    @else
                        <h2 class="text-xl font-bold text-gray-800">Log Viewer</h2>
                    @endif
                </div>
            </div>

            <div class="flex-1 p-4 overflow-y-auto">
                @if ($logLines)
                    <ul class="space-y-4">
                        @foreach (collect($logLines)->reverse() as $index => $logLine)
                            <li>
                                <div
                                    class="p-4 rounded-lg shadow-sm border cursor-pointer hover:bg-gray-100 transition-all {{ $this->getLogLineClass($logLine['type']) }}"
                                    wire:click="toggleLogExpansion({{ $index }})">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm font-semibold text-gray-600 capitalize">{{ $logLine['type'] }}</span>
                                        <div class="flex items-center gap-2" x-data="{ copied: false }">
                                            <span class="text-sm text-gray-500">{{ $logLine['timestamp'] }}</span>
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
                                                class="flex items-center gap-1 p-1 px-2 rounded-md transition-colors focus:outline-none text-xs font-medium border"
                                                :class="copied ? 'flogger-copied-button' : 'bg-white hover:bg-gray-50 text-gray-500 border-gray-200'"
                                                title="Copy to clipboard"
                                            >
                                                <!-- Heroicon: clipboard -->
                                                <svg x-show="!copied" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                                </svg>
                                                <!-- Heroicon: check -->
                                                <svg x-show="copied" style="display: none;" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 flogger-copied-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <span x-show="copied" style="display: none;">Copied!</span>
                                                <span x-show="!copied">Copy</span>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-2 text-sm text-gray-800 overflow-x-auto whitespace-pre-wrap break-words border-t pt-2">
                                        @if ($expandedLogIndex === $index)
                                            <pre class="bg-gray-100 p-2 rounded-lg text-sm font-mono text-gray-800 overflow-y-auto whitespace-pre-wrap">{{ $logLine['full'] }}</pre>
                                        @else
                                            <p class="text-gray-700">{{ $logLine['excerpt'] }}</p>
                                        @endif
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="flex items-center justify-center h-full text-center text-gray-500">
                        @if ($selectedDate)
                            No logs available for this file.
                        @else
                            <div>
                                <p class="text-lg font-medium">No log file selected</p>
                                <p>Select a log file from the list to view its content.</p>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
