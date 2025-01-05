<x-filament-panels::page>
    <div class="flex gap-6 h-screen" dir="ltr">
        <!-- Sidebar (fixed width) -->
        <div class="w-96 flex-shrink-0 bg-white shadow rounded-lg p-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Log Files</h3>
            <ul class="space-y-2">
                @foreach ($logFiles as $logFile)
                    <li>
                        <button
                            class="w-full text-left p-2 bg-gray-100 rounded-md hover:bg-blue-100 focus:outline-none focus:ring focus:ring-blue-300 transition"
                            wire:click="loadLogs('{{ $logFile['date'] }}')"
                        >
                            <div class="flex justify-between items-center">
                                <span>{{ $logFile['date'] }}</span>
                                <span class="text-sm text-gray-500">{{ $logFile['size'] }}</span>
                            </div>
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
                    <input
                        type="text"
                        placeholder="Search logs..."
                        class="w-1/2 p-2 rounded-md border border-gray-300 focus:ring focus:ring-blue-300"
                        wire:model="search"
                    />
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
                                        <span class="text-sm text-gray-500">{{ $logLine['timestamp'] }}</span>
                                    </div>
                                    <div class="mt-2 text-sm text-gray-800 overflow-x-auto whitespace-pre-wrap break-words border-t pt-2">
                                        @if ($expandedLogIndex === $index)
                                            <pre class="bg-gray-100 p-2 rounded-lg text-sm font-mono text-gray-800 overflow-y-auto">{{ $logLine['full'] }}</pre>
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
