<div class="mx-auto max-w-4xl">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">{{ $editing ? __('qr.edit') : __('qr.create') }}</h1>
    </div>

    {{-- Step indicator --}}
    <div class="mb-8 flex items-center justify-center gap-4">
        @foreach([1 => __('qr.step_content'), 2 => __('qr.step_design'), 3 => __('qr.step_preview')] as $num => $label)
            <div class="flex items-center gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold {{ $step >= $num ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-500' }}">
                    {{ $num }}
                </div>
                <span class="hidden text-sm font-medium {{ $step >= $num ? 'text-primary-700' : 'text-gray-500' }} sm:block">{{ $label }}</span>
            </div>
            @if($num < 3)
                <div class="h-px w-12 {{ $step > $num ? 'bg-primary-600' : 'bg-gray-200' }}"></div>
            @endif
        @endforeach
    </div>

    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5 sm:p-8">
        {{-- Step 1: Content --}}
        @if($step === 1)
        <div class="space-y-6">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-[13rem_1fr]">
                {{-- Type selector (left) --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('qr.type') }}</label>
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
                                    'green'  => ['bg' => 'bg-green-100',  'text' => 'text-green-600',  'border' => 'border-green-500',  'selectedBg' => 'bg-green-50'],
                                    'lime'   => ['bg' => 'bg-lime-100',   'text' => 'text-lime-600',   'border' => 'border-lime-500',   'selectedBg' => 'bg-lime-50'],
                                    'red'    => ['bg' => 'bg-red-100',    'text' => 'text-red-600',    'border' => 'border-red-500',    'selectedBg' => 'bg-red-50'],
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
                                        : 'border-transparent bg-white hover:bg-gray-50' }}">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $colors['bg'] }} {{ $colors['text'] }}">
                                    <i class="{{ $qrType->icon() }} text-xs"></i>
                                </span>
                                <span class="text-[11px] font-medium leading-tight {{ $isSelected ? $colors['text'] : 'text-gray-700' }} truncate">{{ $qrType->label() }}</span>
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
                        <label class="block text-sm font-medium text-gray-700">{{ __('qr.name') }}</label>
                        <input wire:model.live.debounce.500ms="name" type="text" placeholder="{{ __('qr.name_placeholder') }}"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ \App\Enums\QrCodeType::from($type)->label() }} Details</label>
                        <p class="mt-1 text-sm text-gray-500">{{ __('qr.marketing_info.' . $type) }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-4">
                @if($type === 'url')
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('qr.url') }}</label>
                        <input wire:model.live.debounce.500ms="url" type="url" placeholder="{{ __('qr.url_placeholder') }}"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        @error('url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                @elseif($type === 'text')
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('qr.text') }}</label>
                        <textarea wire:model.live.debounce.500ms="text" rows="4" placeholder="{{ __('qr.text_placeholder') }}"
                                  class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"></textarea>
                        @error('text') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                @elseif($type === 'vcard')
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">First Name</label>
                            <input wire:model.live.debounce.500ms="firstName" type="text" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            @error('firstName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Last Name</label>
                            <input wire:model.live.debounce.500ms="lastName" type="text" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            @error('lastName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Organization</label>
                            <input wire:model.live.debounce.500ms="org" type="text" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Title</label>
                            <input wire:model.live.debounce.500ms="title" type="text" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Phone</label>
                            <input wire:model.live.debounce.500ms="vcardPhone" type="tel" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input wire:model.live.debounce.500ms="vcardEmail" type="email" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Website</label>
                            <input wire:model.live.debounce.500ms="vcardUrl" type="url" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Address</label>
                            <input wire:model.live.debounce.500ms="vcardAddress" type="text" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        </div>
                    </div>
                @elseif($type === 'wifi')
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Network Name (SSID)</label>
                            <input wire:model.live.debounce.500ms="ssid" type="text" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            @error('ssid') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Password</label>
                            <input wire:model.live.debounce.500ms="wifiPassword" type="text" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Encryption</label>
                            <select wire:model.live="encryption" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                <option value="WPA">WPA/WPA2</option>
                                <option value="WEP">WEP</option>
                                <option value="nopass">None</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-2">
                                <input wire:model.live="hidden" type="checkbox" class="rounded border-gray-300 text-primary-600">
                                <span class="text-sm text-gray-700">Hidden network</span>
                            </label>
                        </div>
                    </div>
                @elseif($type === 'email')
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email Address</label>
                            <input wire:model.live.debounce.500ms="emailAddress" type="email" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            @error('emailAddress') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Subject (optional)</label>
                            <input wire:model.live.debounce.500ms="emailSubject" type="text" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Message (optional)</label>
                            <textarea wire:model.live.debounce.500ms="emailBody" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"></textarea>
                        </div>
                    </div>
                @elseif($type === 'phone')
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                        <input wire:model.live.debounce.500ms="phone" type="tel" placeholder="+1 234 567 890" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                @elseif($type === 'sms')
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                            <input wire:model.live.debounce.500ms="smsPhone" type="tel" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            @error('smsPhone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Message</label>
                            <textarea wire:model.live.debounce.500ms="smsMessage" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"></textarea>
                        </div>
                    </div>
                @elseif($type === 'geo')
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Latitude</label>
                            <input wire:model.live.debounce.500ms="latitude" type="text" placeholder="40.7128" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            @error('latitude') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Longitude</label>
                            <input wire:model.live.debounce.500ms="longitude" type="text" placeholder="-74.0060" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            @error('longitude') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @elseif($type === 'event')
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Event Title</label>
                            <input wire:model.live.debounce.500ms="eventTitle" type="text" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            @error('eventTitle') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Start Date/Time</label>
                                <input wire:model.live="eventStart" type="datetime-local" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                @error('eventStart') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">End Date/Time</label>
                                <input wire:model.live="eventEnd" type="datetime-local" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Location</label>
                            <input wire:model.live.debounce.500ms="eventLocation" type="text" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea wire:model.live.debounce.500ms="eventDescription" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"></textarea>
                        </div>
                    </div>
                @elseif($type === 'crypto')
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Cryptocurrency</label>
                            <select wire:model.live="cryptoCurrency" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                <option value="bitcoin">Bitcoin</option>
                                <option value="ethereum">Ethereum</option>
                                <option value="litecoin">Litecoin</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Wallet Address</label>
                            <input wire:model.live.debounce.500ms="cryptoAddress" type="text" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm font-mono text-xs">
                            @error('cryptoAddress') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Amount (optional)</label>
                            <input wire:model.live.debounce.500ms="cryptoAmount" type="text" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        </div>
                    </div>
                @elseif(in_array($type, ['app_store', 'social', 'menu']))
                    <div>
                        <label class="block text-sm font-medium text-gray-700">URL</label>
                        <input wire:model.live.debounce.500ms="socialUrl" type="url" placeholder="https://" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        @error('socialUrl') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                @elseif($type === 'pdf')
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Upload PDF / File</label>
                        <input wire:model="pdfFile" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg"
                               class="mt-2 block w-full text-sm text-gray-500 file:mr-4 file:rounded-lg file:border-0 file:bg-primary-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-primary-700 hover:file:bg-primary-100">
                        @error('pdfFile') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        @if($existingFileUrl)
                            <p class="mt-2 text-xs text-gray-500">Current file attached</p>
                        @endif
                    </div>
                @endif
                    </div>

                    {{-- Dynamic type info --}}
                    @if(\App\Enums\QrCodeType::from($type)->isDynamic())
                    <div class="flex items-start gap-3 rounded-lg border border-blue-200 bg-blue-50 p-4">
                        <i class="fa-solid fa-circle-info text-blue-500 mt-0.5"></i>
                        <div class="text-sm text-blue-800">
                            <span class="font-semibold">This is a dynamic QR code.</span>
                            It includes scan tracking, analytics, password protection, and the ability to change its destination anytime.
                            @if(!auth()->user()->hasUnlimitedCredits())
                            <span class="block mt-1">
                                <i class="fa-solid fa-coins text-blue-600 mr-0.5"></i>
                                <span class="font-semibold">{{ \App\Enums\CreditAction::MaintainDynamicQr->cost() }} credits/month</span> to keep it active &middot;
                                <span class="font-semibold">{{ \App\Enums\CreditAction::EditDynamicQr->cost() }} credits</span> per edit.
                            </span>
                            @else
                            <span class="block mt-1 text-blue-600 font-medium">
                                <i class="fa-solid fa-infinity mr-0.5"></i> Included with your Enterprise plan — no credit costs.
                            </span>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- Link settings (editing a dynamic-capable type only) --}}
                    @if($editing && \App\Enums\QrCodeType::from($type)->isDynamic())
                    <div class="rounded-lg border border-gray-200 p-4 space-y-4">
                        <h4 class="text-sm font-semibold text-gray-700">
                            <i class="fa-solid fa-link text-gray-400 mr-1"></i>
                            Link Settings
                        </h4>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-600">Custom Slug (optional)</label>
                                <div class="mt-1 flex items-center gap-1">
                                    <span class="text-xs text-gray-400">{{ config('app.proxy_domain') }}/</span>
                                    <input wire:model.live.debounce.500ms="customSlug" type="text" placeholder="my-link" maxlength="50"
                                           class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                                </div>
                                @error('customSlug') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600">Password Protection (optional)</label>
                                <input wire:model.live.debounce.500ms="linkPassword" type="text" placeholder="Leave empty for no password"
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600">Expiration Date (optional)</label>
                                <input wire:model.live="expiresAt" type="datetime-local"
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600">Max Scans (optional)</label>
                                <input wire:model.live="maxScans" type="number" min="1" placeholder="Unlimited"
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                            </div>
                        </div>
                    </div>

                    {{-- Credit confirmation --}}
                    @if(auth()->user()->hasUnlimitedCredits())
                    <div class="flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                        <i class="fa-solid fa-infinity text-emerald-500 mt-0.5"></i>
                        <div class="flex-1">
                            <div class="text-sm text-emerald-800">
                                Your <span class="font-bold">Enterprise</span> plan includes unlimited edits and dynamic QR codes — no credits will be charged.
                            </div>
                            <label class="mt-3 flex items-center gap-2">
                                <input wire:model="confirmCreditCharge" type="checkbox" class="h-4 w-4 rounded border-emerald-400 text-emerald-600 focus:ring-emerald-500">
                                <span class="text-xs font-medium text-emerald-900">I confirm saving these changes</span>
                            </label>
                            @error('confirmCreditCharge') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    @else
                    <div class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4">
                        <i class="fa-solid fa-coins text-amber-500 mt-0.5"></i>
                        <div class="flex-1">
                            <div class="text-sm text-amber-800">
                                Saving changes costs
                                <span class="font-bold">{{ \App\Enums\CreditAction::EditDynamicQr->cost() }} credits</span>.
                                This QR code also has a
                                <span class="font-bold">{{ \App\Enums\CreditAction::MaintainDynamicQr->cost() }} credits/month</span> maintenance fee.
                                <span class="block mt-1 text-amber-600">Your balance: <strong>{{ number_format(auth()->user()?->creditBalance?->balance ?? 0) }} credits</strong></span>
                            </div>
                            <label class="mt-3 flex items-center gap-2">
                                <input wire:model="confirmCreditCharge" type="checkbox" class="h-4 w-4 rounded border-amber-400 text-amber-600 focus:ring-amber-500">
                                <span class="text-xs font-medium text-amber-900">I understand the credit costs above</span>
                            </label>
                            @error('confirmCreditCharge') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    @endif
                    @endif
                </div>
            </div>

            <div class="mt-6 flex justify-between">
                @if($step > 1)
                    <button wire:click="previousStep" class="rounded-lg border border-gray-300 px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
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
        <div class="flex flex-col gap-8">
            <div class="flex flex-col gap-8 lg:flex-row">
                {{-- Design controls (left) --}}
                <div class="flex-1 space-y-6">
                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('qr.fg_color') }}</label>
                        <div class="mt-1 flex items-center gap-2">
                            <input wire:model.live.debounce.300ms="fgColor" type="color" class="h-10 w-14 cursor-pointer rounded border-gray-300">
                            <input wire:model.live.debounce.500ms="fgColor" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" maxlength="7">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('qr.bg_color') }}</label>
                        <div class="mt-1 flex items-center gap-2">
                            <input wire:model.live.debounce.300ms="bgColor" type="color" class="h-10 w-14 cursor-pointer rounded border-gray-300">
                            <input wire:model.live.debounce.500ms="bgColor" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" maxlength="7">
                        </div>
                    </div>
                </div>

                {{-- Gradient --}}
                <div class="rounded-lg border border-gray-200 p-4">
                    <label class="flex items-center gap-3">
                        <input wire:model.live="useGradient" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-primary-600">
                        <span class="text-sm font-medium text-gray-700">Use Gradient</span>
                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">Premium</span>
                    </label>
                    @if($useGradient)
                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Color 1</label>
                            <input wire:model.live.debounce.300ms="gradientColor1" type="color" class="mt-1 h-8 w-full cursor-pointer rounded">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Color 2</label>
                            <input wire:model.live.debounce.300ms="gradientColor2" type="color" class="mt-1 h-8 w-full cursor-pointer rounded">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Direction</label>
                            <select wire:change="setDesign('gradientType', $event.target.value)" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                                <option value="linear" @selected($gradientType === 'linear')>Linear</option>
                                <option value="radial" @selected($gradientType === 'radial')>Radial</option>
                            </select>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Shape customization (Body, Eye Frame, Eye Ball) --}}
                <div class="rounded-xl border border-gray-200 bg-slate-50/80 p-3 sm:p-4">
                    <div class="space-y-4">
                        {{-- Body Shape (data modules) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Body Shape</label>
                            <p class="text-xs text-gray-500 mt-0.5">Shape of the data modules</p>
                            <div class="mt-2 flex flex-wrap gap-1">
                                @foreach(config('qr_shapes.body') as $style => $config)
                                <button wire:click="setDesign('dotStyle', '{{ $style }}')" type="button"
                                        title="{{ $config['label'] }}"
                                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded border bg-white shadow-sm transition-colors hover:border-gray-300 {{ $dotStyle === $style ? 'border-primary-500 bg-primary-50 ring-1 ring-primary-200' : 'border-gray-200' }}">
                                    <x-qr-shape-icon shape="{{ $config['svg'] }}" :size="14" />
                                </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Eye Frame Shape (outer corner squares) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Eye Frame Shape</label>
                            <p class="text-xs text-gray-500 mt-0.5">Outer shape of the corner finder patterns</p>
                            <div class="mt-2 flex flex-wrap gap-1">
                                @foreach(config('qr_shapes.eye_frame') as $style => $config)
                                <button wire:click="setDesign('eyeFrameStyle', '{{ $style }}')" type="button"
                                        title="{{ $config['label'] }}"
                                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded border bg-white shadow-sm transition-colors hover:border-gray-300 {{ $eyeFrameStyle === $style ? 'border-primary-500 bg-primary-50 ring-1 ring-primary-200' : 'border-gray-200' }}">
                                    <x-qr-shape-icon shape="{{ $config['svg'] }}" :size="14" />
                                </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Eye Ball Shape (inner dot of corners) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Eye Ball Shape</label>
                            <p class="text-xs text-gray-500 mt-0.5">Inner shape of the corner finder patterns</p>
                            <div class="mt-2 flex flex-wrap gap-1">
                                @foreach(config('qr_shapes.eye_ball') as $style => $config)
                                <button wire:click="setDesign('eyeBallStyle', '{{ $style }}')" type="button"
                                        title="{{ $config['label'] }}"
                                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded border bg-white shadow-sm transition-colors hover:border-gray-300 {{ $eyeBallStyle === $style ? 'border-primary-500 bg-primary-50 ring-1 ring-primary-200' : 'border-gray-200' }}">
                                    <x-qr-shape-icon shape="{{ $config['svg'] }}" :size="14" />
                                </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Frame --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Frame</label>
                    <div class="mt-2 flex flex-wrap gap-2">
                    @foreach(['' => 'None', 'simple' => 'Simple Border', 'rounded' => 'Rounded', 'banner' => 'Banner'] as $style => $label)
                    <button wire:click="setDesign('frameStyle', '{{ $style }}')" type="button"
                            class="rounded-lg border-2 px-3 py-1.5 text-xs {{ $frameStyle === $style ? 'border-primary-600 bg-primary-50' : 'border-gray-200' }}">
                        {{ $label }}
                    </button>
                    @endforeach
                    </div>
                    @if($frameStyle)
                    <div class="mt-3">
                        <label class="block text-xs font-medium text-gray-600">Call-to-action text</label>
                        <input wire:model.live.debounce.300ms="frameText" type="text" placeholder="Scan me!" maxlength="30"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    </div>
                    @endif
                </div>

                <div x-data="{ iconModalOpen: false }">
                    <label class="block text-sm font-medium text-gray-700">{{ __('qr.logo') }}</label>
                    <p class="text-xs text-gray-500 mb-2">{{ __('qr.logo_help') }}</p>
                    <input wire:model="logo" type="file" accept="image/png,image/jpeg,image/svg+xml"
                           class="block w-full text-sm text-gray-500 file:mr-4 file:rounded-lg file:border-0 file:bg-primary-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-primary-700 hover:file:bg-primary-100">
                    @error('logo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    @if($existingLogo && !str_starts_with($existingLogo ?? '', 'icons/'))
                        <p class="mt-2 text-xs text-gray-500">Current: {{ basename($existingLogo) }}</p>
                    @endif

                    <div class="mt-2 flex items-center gap-2">
                        <button type="button"
                                class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                @click="iconModalOpen = true">
                            <i class="fa-solid fa-icons mr-1.5"></i>Use an icon
                        </button>
                        @if($selectedIcon)
                            <span class="text-xs text-primary-600">{{ $selectedIcon }}</span>
                            <button wire:click="selectIcon(null)" type="button" class="text-xs text-gray-500 hover:text-gray-700">Clear</button>
                        @endif
                    </div>

                    {{-- Icon picker modal --}}
                    <div x-show="iconModalOpen" x-cloak
                         x-effect="document.body.style.overflow = iconModalOpen ? 'hidden' : ''"
                         class="fixed inset-0 z-50 flex items-center justify-center p-4"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0">
                        <div class="absolute inset-0 bg-black/50" @click="iconModalOpen = false"></div>
                        <div class="relative w-full max-w-2xl rounded-xl bg-white shadow-xl flex flex-col overflow-hidden"
                             @click.stop>
                            <div class="flex shrink-0 items-center justify-between border-b border-gray-200 px-4 py-3">
                                <h3 class="text-lg font-semibold text-gray-900">Choose an icon</h3>
                                <button type="button" @click="iconModalOpen = false"
                                        class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                                    <i class="fa-solid fa-xmark text-xl"></i>
                                </button>
                            </div>
                            <div class="min-h-0 max-h-[400px] overflow-y-auto overflow-x-hidden p-4">
                                <div class="flex flex-wrap gap-2">
                                    @foreach($this->availableIcons as $icon)
                                        <button type="button"
                                                wire:click="selectIcon('{{ $icon }}')"
                                                @click="iconModalOpen = false"
                                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg transition-colors {{ $selectedIcon === $icon ? 'bg-primary-50' : 'hover:bg-gray-100' }}">
                                            <img src="{{ asset('icons/qr-center-icons/' . $icon . '.svg') }}" alt="{{ $icon }}" class="h-4 w-4" loading="lazy">
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

                {{-- Live preview (right) --}}
                <div class="lg:w-72 lg:shrink-0">
                    <div class="lg:sticky lg:top-24">
                    <p class="mb-3 text-sm font-semibold text-gray-700 text-center">Preview</p>
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <div class="relative" style="min-height: 200px">
                            @if($preview)
                                <img src="{{ $preview }}" alt="QR Code Preview"
                                     class="w-full rounded-lg transition-opacity duration-200"
                                     wire:loading.class="opacity-20"
                                     wire:target="setDesign,fgColor,bgColor,useGradient,gradientColor1,gradientColor2,gradientType,logo,selectIcon,eyeFrameStyle,eyeBallStyle">
                            @else
                                <div class="flex items-center justify-center rounded-lg border-2 border-dashed border-gray-300" style="height: 200px">
                                    <i class="fa-solid fa-spinner fa-spin text-2xl text-gray-300"></i>
                                </div>
                            @endif
                            <div wire:loading.flex wire:target="setDesign,fgColor,bgColor,useGradient,gradientColor1,gradientColor2,gradientType,logo,selectIcon,eyeFrameStyle,eyeBallStyle"
                                 class="absolute top-0 left-0 right-0 bottom-0 items-center justify-center rounded-lg bg-white/50" style="display:none">
                                <div class="flex flex-col items-center gap-3">
                                    <i class="fa-solid fa-spinner fa-spin text-4xl text-primary-600"></i>
                                    <span class="text-sm font-semibold text-primary-600">Updating...</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center justify-center gap-1.5">
                            <div class="h-3 w-3 rounded-full border" style="background-color: {{ $fgColor }}"></div>
                            <span class="text-xs text-gray-400">on</span>
                            <div class="h-3 w-3 rounded-full border" style="background-color: {{ $bgColor }}"></div>
                            <span class="mx-1 text-gray-300">|</span>
                            <span class="text-xs text-gray-400 capitalize">{{ $dotStyle }}</span>
                        </div>
                    </div>
                </div>
                </div>
            </div>

            {{-- Navigation buttons (below both columns) --}}
            <div class="flex w-full justify-between border-t border-gray-200 pt-6">
                <button wire:click="previousStep" class="rounded-lg border border-gray-300 px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                    {{ __('common.back') }}
                </button>
                <button wire:click="nextStep" class="rounded-lg bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 transition">
                    {{ __('common.next') }}
                </button>
            </div>
        </div>
        @endif

        {{-- Step 3: Preview & Save --}}
        @if($step === 3)
        <div class="flex flex-col items-center gap-8">
            @if($preview)
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <img src="{{ $preview }}" alt="QR Code Preview" class="h-72 w-72">
                </div>
            @else
                <div class="flex h-72 w-72 items-center justify-center rounded-xl border-2 border-dashed border-gray-300">
                    <span class="text-sm text-gray-400" wire:loading.remove>Generating preview...</span>
                    <span class="text-sm text-gray-400" wire:loading>{{ __('common.loading') }}...</span>
                </div>
            @endif

            <div class="w-full max-w-sm space-y-3">
                <div class="rounded-lg bg-gray-50 p-4 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Name:</span><span class="font-medium">{{ $name }}</span></div>
                    <div class="mt-1 flex justify-between"><span class="text-gray-500">Type:</span><span class="font-medium">{{ \App\Enums\QrCodeType::from($type)->label() }}</span></div>
                    <div class="mt-1 flex justify-between">
                        <span class="text-gray-500">Mode:</span>
                        <span class="font-medium">
                            @if(\App\Enums\QrCodeType::from($type)->isDynamic())
                                {{ $editing ? __('qr.dynamic') : __('qr.static') }}
                                @if(!$editing)
                                    <span class="text-xs text-gray-400">(upgradeable)</span>
                                @endif
                            @else
                                {{ __('qr.static') }}
                            @endif
                        </span>
                    </div>
                    @if($editing && \App\Enums\QrCodeType::from($type)->isDynamic())
                    <div class="mt-1 flex justify-between"><span class="text-gray-500">Edit cost:</span><span class="font-medium text-amber-600">-{{ \App\Enums\CreditAction::EditDynamicQr->cost() }} credits</span></div>
                    <div class="mt-1 flex justify-between"><span class="text-gray-500">Maintenance:</span><span class="font-medium text-amber-600">{{ \App\Enums\CreditAction::MaintainDynamicQr->cost() }} credits/mo</span></div>
                    @endif
                </div>

                <button wire:click="save" class="flex w-full justify-center rounded-lg bg-primary-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 transition">
                    <span wire:loading.remove wire:target="save">{{ __('qr.save') }}</span>
                    <span wire:loading wire:target="save">{{ __('common.loading') }}...</span>
                </button>
            </div>

            <div class="mt-8 flex justify-between w-full max-w-sm">
                <button wire:click="previousStep" class="rounded-lg border border-gray-300 px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                    {{ __('common.back') }}
                </button>
                <div></div>
            </div>
        </div>
        @endif
    </div>
</div>
