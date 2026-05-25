<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Password Required</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="w-full max-w-sm rounded-xl bg-white p-8 shadow-lg">
        <div class="text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
            <h2 class="mt-4 text-lg font-semibold text-gray-900">Password Required</h2>
            <p class="mt-2 text-sm text-gray-500">This link is password protected.</p>
        </div>

        @if($error ?? false)
            <div class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $error }}</div>
        @endif

        <form method="GET" action="" class="mt-6">
            <input name="password" type="password" placeholder="Enter password" required autofocus
                   class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <button type="submit" class="mt-4 w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                Continue
            </button>
        </form>
    </div>
</body>
</html>
