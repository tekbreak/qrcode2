<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $hub_title ?: __('qr.social_hub_page_title') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100">
    <div class="mx-auto flex min-h-screen max-w-md flex-col px-4 py-8">
        <header class="mb-8 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-primary-500 to-pink-500 text-white shadow-lg"
                 style="background: linear-gradient(135deg, #6366f1 0%, #ec4899 100%);">
                <i class="fa-solid fa-share-nodes text-2xl"></i>
            </div>
            @if($hub_title)
                <h1 class="text-2xl font-bold text-gray-900">{{ $hub_title }}</h1>
            @endif
        </header>

        <main class="flex-1 space-y-3">
            @foreach($networks as $network)
                @php
                    $meta = $platforms[$network['platform']] ?? $platforms['custom'];
                    $label = $meta['label'] ?? 'Link';
                @endphp
                <a href="{{ $network['url'] }}"
                   class="group flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md active:scale-[0.99]"
                   rel="noopener noreferrer">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-lg {{ $meta['icon_color'] ?? 'text-white' }}"
                          style="{{ $meta['style'] ?? 'background-color:#6B7280' }}">
                        <i class="{{ $meta['icon'] ?? 'fa-solid fa-link' }}"></i>
                    </span>
                    <span class="min-w-0 flex-1 text-left">
                        <span class="block text-base font-semibold text-gray-900">{{ $label }}</span>
                        <span class="block truncate text-sm text-gray-500">{{ $network['identifier'] ?? '' }}</span>
                    </span>
                    <i class="fa-solid fa-chevron-right text-sm text-gray-300 group-hover:text-gray-500"></i>
                </a>
            @endforeach
        </main>
    </div>
</body>
</html>
