<div @class([
    'mx-auto',
    'max-w-6xl' => $step === 2 && config('qrcode.generator_engine') === 'v2',
    'max-w-4xl' => ! ($step === 2 && config('qrcode.generator_engine') === 'v2'),
])>
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $editing ? __('qr.edit') : __('qr.create') }}</h1>

        @if($step === 1)
            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="min-w-0 flex-1 sm:max-w-xs">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('qr.category') }}</label>
                    <select wire:model.live="categoryId"
                            class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        <option value="">{{ __('qr.no_category') }}</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button wire:click="toggleNewCategoryForm" type="button"
                        class="shrink-0 rounded-lg border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-zinc-800 transition">
                    {{ $showNewCategoryForm ? __('common.cancel') : __('qr.create_new_category') }}
                </button>
            </div>

            @if($showNewCategoryForm)
                <form wire:submit="createAndSelectCategory" class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-start">
                    <div class="min-w-0 flex-1 sm:max-w-md">
                        <input wire:model="newCategoryName" type="text" placeholder="{{ __('qr.category_name_placeholder') }}"
                               class="block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        @error('newCategoryName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="shrink-0 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 transition">
                        {{ __('common.create') }}
                    </button>
                </form>
            @endif
        @endif
    </div>

    {{-- Step indicator --}}
    <div class="mb-8 flex items-center justify-center gap-4">
        @foreach([1 => __('qr.step_content'), 2 => __('qr.step_design'), 3 => __('qr.step_preview')] as $num => $label)
            <div class="flex items-center gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold {{ $step >= $num ? 'bg-primary-600 text-white' : 'bg-gray-200 dark:bg-zinc-700 text-gray-500 dark:text-gray-400 dark:text-gray-500' }}">
                    {{ $num }}
                </div>
                <span class="hidden text-sm font-medium {{ $step >= $num ? 'text-primary-700 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400 dark:text-gray-500' }} sm:block">{{ $label }}</span>
            </div>
            @if($num < 3)
                <div class="h-px w-12 {{ $step > $num ? 'bg-primary-600' : 'bg-gray-200 dark:bg-zinc-700' }}"></div>
            @endif
        @endforeach
    </div>

    <div class="rounded-xl bg-white dark:bg-zinc-900 p-6 shadow-sm ring-1 ring-gray-900/5 dark:ring-zinc-800 sm:p-8">
        {{-- Step 1: Content --}}
        @if($step === 1)
        <div class="space-y-6">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-[13rem_1fr]">
                {{-- Type selector (left) --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('qr.type') }}</label>
                    <div class="grid grid-cols-2 gap-1.5 sm:grid-cols-3 md:grid-cols-1 md:max-h-[65vh] md:overflow-y-auto md:pr-1">
                        @foreach($this->availableTypes as $qrType)
                            @php
                                $isSelected = $type === $qrType->value;
                                $c = $qrType->color();
                                $colorMap = [
                                    'blue'   => ['bg' => 'bg-blue-100',   'text' => 'text-blue-600',   'border' => 'border-blue-500',   'selectedBg' => 'bg-blue-50'],
                                    'slate'  => ['bg' => 'bg-slate-100',  'text' => 'text-slate-600',  'border' => 'border-slate-400',  'selectedBg' => 'bg-slate-50'],
                                    'violet' => ['bg' => 'bg-violet-100', 'text' => 'text-violet-600', 'border' => 'border-violet-500', 'selectedBg' => 'bg-violet-50'],
                                    'cyan'   => ['bg' => 'bg-cyan-100',   'text' => 'text-cyan-600',   'border' => 'border-cyan-500',   'selectedBg' => 'bg-cyan-50'],
                                    'amber'  => ['bg' => 'bg-amber-100',  'text' => 'text-amber-600',  'border' => 'border-amber-500',  'selectedBg' => 'bg-amber-50'],
                                    'green'  => ['bg' => 'bg-green-100',  'text' => 'text-green-600',  'border' => 'border-green-500',  'selectedBg' => 'bg-green-50 dark:bg-green-950/50'],
                                    'lime'   => ['bg' => 'bg-lime-100',   'text' => 'text-lime-600',   'border' => 'border-lime-500',   'selectedBg' => 'bg-lime-50'],
                                    'red'    => ['bg' => 'bg-red-100',    'text' => 'text-red-600',    'border' => 'border-red-500',    'selectedBg' => 'bg-red-50 dark:bg-red-950/50'],
                                    'orange' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-600', 'border' => 'border-orange-500', 'selectedBg' => 'bg-orange-50'],
                                    'yellow' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-600', 'border' => 'border-yellow-500', 'selectedBg' => 'bg-yellow-50'],
                                    'indigo' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-600', 'border' => 'border-indigo-500', 'selectedBg' => 'bg-indigo-50'],
                                    'pink'   => ['bg' => 'bg-pink-100',   'text' => 'text-pink-600',   'border' => 'border-pink-500',   'selectedBg' => 'bg-pink-50'],
                                    'rose'   => ['bg' => 'bg-rose-100',   'text' => 'text-rose-600',   'border' => 'border-rose-500',   'selectedBg' => 'bg-rose-50'],
                                    'teal'   => ['bg' => 'bg-teal-100',   'text' => 'text-teal-600',   'border' => 'border-teal-500',   'selectedBg' => 'bg-teal-50'],
                                ];
                                $colors = $colorMap[$c] ?? $colorMap['blue'];
                            @endphp
                            <button wire:click="selectType('{{ $qrType->value }}')" type="button"
                                    class="group flex items-center gap-2 rounded-lg border-2 px-2 py-1.5 text-left transition-all duration-150
                                    {{ $isSelected
                                        ? $colors['selectedBg'] . ' ' . $colors['border'] . ' shadow-sm'
                                        : 'border-transparent bg-white dark:bg-zinc-900 hover:bg-gray-50 dark:hover:bg-zinc-800' }}">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $colors['bg'] }} {{ $colors['text'] }}">
                                    <i class="{{ $qrType->icon() }} text-xs"></i>
                                </span>
                                <span class="text-[11px] font-medium leading-tight {{ $isSelected ? $colors['text'] : 'text-gray-700 dark:text-gray-300' }} truncate">{{ $qrType->label() }}</span>
                                @if($qrType->isDynamic())
                                    <span class="ml-auto shrink-0 rounded bg-emerald-100 px-1 py-px text-[7px] font-bold uppercase tracking-wider text-emerald-700">D</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                    @error('type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Type-specific form (right) --}}
                <div class="min-w-0 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('qr.name') }}</label>
                        <input wire:model.live.debounce.500ms="name" type="text" placeholder="{{ __('qr.name_placeholder') }}"
                               class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ \App\Enums\QrCodeType::from($type)->label() }} Details</label>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">{{ __('qr.marketing_info.' . $type) }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 dark:bg-zinc-800/60 p-4">
                @if($type === 'url')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('qr.url') }}</label>
                        <input wire:model.live.debounce.500ms="url" type="url" placeholder="{{ __('qr.url_placeholder') }}"
                               class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        @error('url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                @elseif($type === 'text')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('qr.text') }}</label>
                        <textarea wire:model.live.debounce.500ms="text" rows="4" placeholder="{{ __('qr.text_placeholder') }}"
                                  class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"></textarea>
                        @error('text') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                @elseif($type === 'vcard')
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">First Name</label>
                            <input wire:model.live.debounce.500ms="firstName" type="text" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            @error('firstName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Last Name</label>
                            <input wire:model.live.debounce.500ms="lastName" type="text" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            @error('lastName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Organization</label>
                            <input wire:model.live.debounce.500ms="org" type="text" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                            <input wire:model.live.debounce.500ms="title" type="text" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
                            <input wire:model.live.debounce.500ms="vcardPhone" type="tel" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                            <input wire:model.live.debounce.500ms="vcardEmail" type="email" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Website</label>
                            <input wire:model.live.debounce.500ms="vcardUrl" type="url" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Address</label>
                            <input wire:model.live.debounce.500ms="vcardAddress" type="text" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        </div>
                    </div>
                @elseif($type === 'wifi')
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Network Name (SSID)</label>
                            <input wire:model.live.debounce.500ms="ssid" type="text" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            @error('ssid') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                            <input wire:model.live.debounce.500ms="wifiPassword" type="text" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Encryption</label>
                            <select wire:model.live="encryption" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                <option value="WPA">WPA/WPA2</option>
                                <option value="WEP">WEP</option>
                                <option value="nopass">None</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-2">
                                <input wire:model.live="hidden" type="checkbox" class="rounded border-gray-300 dark:border-zinc-700 text-primary-600">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Hidden network</span>
                            </label>
                        </div>
                    </div>
                @elseif($type === 'email')
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email Address</label>
                            <input wire:model.live.debounce.500ms="emailAddress" type="email" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            @error('emailAddress') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Subject (optional)</label>
                            <input wire:model.live.debounce.500ms="emailSubject" type="text" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Message (optional)</label>
                            <textarea wire:model.live.debounce.500ms="emailBody" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"></textarea>
                        </div>
                    </div>
                @elseif($type === 'phone')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone Number</label>
                        <input wire:model.live.debounce.500ms="phone" type="tel" placeholder="+1 234 567 890" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                @elseif($type === 'sms')
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone Number</label>
                            <input wire:model.live.debounce.500ms="smsPhone" type="tel" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            @error('smsPhone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Message</label>
                            <textarea wire:model.live.debounce.500ms="smsMessage" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"></textarea>
                        </div>
                    </div>
                @elseif($type === 'geo')
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Latitude</label>
                            <input wire:model.live.debounce.500ms="latitude" type="text" placeholder="40.7128" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            @error('latitude') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Longitude</label>
                            <input wire:model.live.debounce.500ms="longitude" type="text" placeholder="-74.0060" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            @error('longitude') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @elseif($type === 'event')
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Event Title</label>
                            <input wire:model.live.debounce.500ms="eventTitle" type="text" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            @error('eventTitle') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Start Date/Time</label>
                                <input wire:model.live="eventStart" type="datetime-local" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                @error('eventStart') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">End Date/Time</label>
                                <input wire:model.live="eventEnd" type="datetime-local" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Location</label>
                            <input wire:model.live.debounce.500ms="eventLocation" type="text" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                            <textarea wire:model.live.debounce.500ms="eventDescription" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"></textarea>
                        </div>
                    </div>
                @elseif($type === 'crypto')
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cryptocurrency</label>
                            <select wire:model.live="cryptoCurrency" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                <option value="bitcoin">Bitcoin</option>
                                <option value="ethereum">Ethereum</option>
                                <option value="litecoin">Litecoin</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Wallet Address</label>
                            <input wire:model.live.debounce.500ms="cryptoAddress" type="text" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm font-mono text-xs">
                            @error('cryptoAddress') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Amount (optional)</label>
                            <input wire:model.live.debounce.500ms="cryptoAmount" type="text" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        </div>
                    </div>
                @elseif($type === 'social')
                    @include('livewire.qr-codes._social-platform-picker')
                @elseif(in_array($type, ['app_store', 'menu']))
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">URL</label>
                        <input wire:model.live.debounce.500ms="socialUrl" type="url" placeholder="https://" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        @error('socialUrl') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                @elseif($type === 'pdf')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Upload PDF / File</label>
                        <input wire:model="pdfFile" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg"
                               class="mt-2 block w-full text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500 file:mr-4 file:rounded-lg file:border-0 file:bg-primary-50 dark:bg-primary-950/50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-primary-700 dark:text-primary-400 hover:file:bg-primary-100">
                        @error('pdfFile') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        @if($existingFileUrl)
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">Current file attached</p>
                        @endif
                    </div>
                @endif
                    </div>

                    {{-- Dynamic QR section --}}
                    @php
                        $isDynamicType = \App\Enums\QrCodeType::from($type)->isDynamic();
                        $pdfUsesShortLink = $type === 'pdf';
                    @endphp
                    @if($isDynamicType)
                    <div class="rounded-lg border border-gray-200 dark:border-zinc-700 p-4 space-y-3">
                        @if($pdfUsesShortLink)
                        <div class="flex items-start gap-3">
                            <i class="fa-solid fa-link mt-0.5 text-sm text-primary-600 dark:text-primary-400 shrink-0"></i>
                            <div>
                                <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('qr.dynamic') }}</span>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ __('qr.pdf_short_link_info') }}
                                </p>
                            </div>
                        </div>
                        @else
                        {{-- Toggle --}}
                        <label @class(['flex items-start gap-3', 'cursor-not-allowed opacity-75' => $editing && $qrCode?->shortLink, 'cursor-pointer' => !($editing && $qrCode?->shortLink)])>
                            <input wire:model.live="isDynamic" type="checkbox"
                                   @if($editing && $qrCode?->shortLink) disabled @endif
                                   class="mt-0.5 h-4 w-4 rounded border-gray-300 dark:border-zinc-600 text-primary-600 focus:ring-primary-500 shrink-0">
                            <div>
                                <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">Make this a Dynamic QR Code</span>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    Includes scan tracking, analytics, password protection, and the ability to change its destination anytime.
                                </p>
                            </div>
                        </label>
                        @endif

                        @if($isDynamic || $pdfUsesShortLink)
                        <div class="space-y-3 border-t border-gray-100 dark:border-zinc-800 pt-3">
                            {{-- Plan limit warning when creating and over limit --}}
                            @if(!$editing && !auth()->user()->canCreateQrCode(isDynamic: true))
                            <div class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/40 p-3">
                                <i class="fa-solid fa-triangle-exclamation text-amber-500 mt-0.5 text-sm shrink-0"></i>
                                <p class="text-sm text-amber-800 dark:text-amber-200">
                                    You've reached your plan's dynamic QR code limit.
                                    Creating additional dynamic QR codes costs <span class="font-bold">€1</span> each.
                                </p>
                            </div>
                            @endif

                            {{-- Enterprise: unlimited edits --}}
                            @if(auth()->user()->hasFreeDynamicEdits())
                            <p class="flex items-center gap-1.5 text-xs font-medium text-emerald-700 dark:text-emerald-400">
                                <i class="fa-solid fa-infinity"></i>
                                Unlimited edits included with your Enterprise plan.
                            </p>
                            @elseif($editing && $qrCode?->shortLink)
                            {{-- Existing dynamic QR — subsequent edits cost €1 --}}
                            <div class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/40 p-3">
                                <i class="fa-solid fa-euro-sign text-amber-500 mt-0.5 text-sm shrink-0"></i>
                                <p class="text-sm text-amber-800 dark:text-amber-200">
                                    Changing the destination URL, password, expiration, or scan limits costs
                                    <span class="font-bold">€1</span> per save. You will be redirected to Stripe to complete payment.
                                </p>
                            </div>
                            @else
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                First activation is included in your plan. Subsequent edits cost €1 each.
                            </p>
                            @endif

                            {{-- Link options --}}
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Password Protection <span class="font-normal text-gray-400">(optional)</span></label>
                                    <input wire:model.live.debounce.500ms="linkPassword" type="text" placeholder="Leave empty for no password"
                                           class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Expiration Date <span class="font-normal text-gray-400">(optional)</span></label>
                                    <input wire:model.live="expiresAt" type="datetime-local"
                                           class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Max Scans <span class="font-normal text-gray-400">(optional)</span></label>
                                    <input wire:model.live="maxScans" type="number" min="1" placeholder="Unlimited"
                                           class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>

            <div class="mt-6 flex justify-between">
                @if($step > 1)
                    <button wire:click="previousStep" class="rounded-lg border border-gray-300 dark:border-zinc-700 px-6 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-800 dark:bg-zinc-800/60 transition">
                        {{ __('common.back') }}
                    </button>
                @else
                    <div></div>
                @endif
                @if($step < 3)
                    <button wire:click="nextStep" class="rounded-lg bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 transition">
                        {{ __('common.next') }}
                    </button>
                @endif
            </div>
        </div>
        @endif

        {{-- Step 2: Design --}}
        @if($step === 2)
            @include('livewire.qr-codes._design-step-' . config('qrcode.generator_engine', 'v1'))
        @endif

        {{-- Step 3: Preview & Save --}}
        @if($step === 3)
        <div class="flex flex-col items-center gap-8">
            @if($preview)
                <div class="rounded-xl border border-gray-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 shadow-sm">
                    <img src="{{ $preview }}" alt="QR Code Preview" class="h-72 w-72">
                </div>
            @else
                <div class="flex h-72 w-72 items-center justify-center rounded-xl border-2 border-dashed border-gray-300 dark:border-zinc-700">
                    <span class="text-sm text-gray-400 dark:text-gray-500" wire:loading.remove>Generating preview...</span>
                    <span class="text-sm text-gray-400 dark:text-gray-500" wire:loading>{{ __('common.loading') }}...</span>
                </div>
            @endif

            <div class="w-full max-w-sm space-y-3">
                <div class="rounded-lg bg-gray-50 dark:bg-zinc-800/60 p-4 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400 dark:text-gray-500">Name:</span><span class="font-medium">{{ $name }}</span></div>
                    <div class="mt-1 flex justify-between"><span class="text-gray-500 dark:text-gray-400 dark:text-gray-500">Type:</span><span class="font-medium">{{ \App\Enums\QrCodeType::from($type)->label() }}</span></div>
                    <div class="mt-1 flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400 dark:text-gray-500">Mode:</span>
                        <span class="font-medium">
                            @if(\App\Enums\QrCodeType::from($type)->isDynamic() && ($isDynamic || $type === 'pdf'))
                                {{ __('qr.dynamic') }}
                            @else
                                {{ __('qr.static') }}
                                @if(\App\Enums\QrCodeType::from($type)->isDynamic())
                                    <span class="text-xs text-gray-400 dark:text-gray-500">(can be made dynamic)</span>
                                @endif
                            @endif
                        </span>
                    </div>
                    @if($isDynamic && \App\Enums\QrCodeType::from($type)->isDynamic() && $qrCode?->shortLink && !auth()->user()->hasFreeDynamicEdits())
                    <div class="mt-1 flex justify-between"><span class="text-gray-500 dark:text-gray-400">Edit cost:</span><span class="font-medium text-amber-600">€1 per save</span></div>
                    @endif
                </div>

                <button wire:click="save" class="flex w-full justify-center rounded-lg bg-primary-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 transition">
                    <span wire:loading.remove wire:target="save">{{ __('qr.save') }}</span>
                    <span wire:loading wire:target="save">{{ __('common.loading') }}...</span>
                </button>
            </div>

            <div class="mt-8 flex justify-between w-full max-w-sm">
                <button wire:click="previousStep" class="rounded-lg border border-gray-300 dark:border-zinc-700 px-6 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-800 dark:bg-zinc-800/60 transition">
                    {{ __('common.back') }}
                </button>
                <div></div>
            </div>
        </div>
        @endif
    </div>
</div>
