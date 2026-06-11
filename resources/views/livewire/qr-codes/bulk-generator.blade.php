<div class="mx-auto max-w-3xl">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Bulk Generate QR Codes</h1>
    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">Upload a CSV file with Name and URL columns to generate multiple QR codes at once.</p>

    <div class="mt-6 rounded-xl bg-white dark:bg-zinc-900 p-6 shadow-sm ring-1 ring-gray-900/5 dark:ring-zinc-800">
        @if(!$processing)
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Upload CSV File</label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500 mb-2">Format: name, url (one per line, max 500 rows)</p>
                    <input wire:model="csvFile" type="file" accept=".csv,.txt"
                           class="block w-full text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500 file:mr-4 file:rounded-lg file:border-0 file:bg-primary-50 dark:bg-primary-950/50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-primary-700 dark:text-primary-400 hover:file:bg-primary-100">
                    @error('csvFile') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                @if($csvFile)
                    <button wire:click="parseCsv" class="rounded-lg border border-primary-600 px-4 py-2 text-sm font-medium text-primary-600 hover:bg-primary-50 dark:bg-primary-950/50 transition">
                        Preview Data
                    </button>
                @endif

                @if(!empty($parsedItems))
                    <div class="mt-4">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Preview ({{ count($parsedItems) }} items)</h3>
                        <div class="mt-2 max-h-64 overflow-y-auto rounded-lg border">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800 text-sm">
                                <thead class="bg-gray-50 dark:bg-zinc-800/60 sticky top-0">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-500">#</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-500">Name</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-500">URL</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-zinc-800">
                                    @foreach(array_slice($parsedItems, 0, 20) as $i => $item)
                                        <tr>
                                            <td class="px-3 py-2 text-gray-400 dark:text-gray-500">{{ $i + 1 }}</td>
                                            <td class="px-3 py-2 text-gray-900 dark:text-gray-100">{{ $item['name'] }}</td>
                                            <td class="px-3 py-2 text-gray-600 dark:text-gray-400 dark:text-gray-500 truncate max-w-xs">{{ $item['url'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @if(count($parsedItems) > 20)
                                <p class="px-3 py-2 text-xs text-gray-400 dark:text-gray-500">... and {{ count($parsedItems) - 20 }} more</p>
                            @endif
                        </div>

                        <div class="mt-4 flex items-center justify-between">
                            <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">
                                Items to generate: <span class="font-semibold text-primary-600">{{ count($parsedItems) }}</span>
                            </p>
                            <button wire:click="generate" class="rounded-lg bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 transition">
                                Generate All
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="text-center py-8">
                <svg class="mx-auto h-12 w-12 animate-spin text-primary-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                <p class="mt-4 text-lg font-semibold text-gray-900 dark:text-gray-100">Generating QR codes...</p>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">This is running in the background. Check your QR codes list shortly.</p>
                <a href="{{ route('qr-codes.index') }}" class="mt-6 inline-block rounded-lg bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 transition">
                    Go to My QR Codes
                </a>
            </div>
        @endif
    </div>
</div>
