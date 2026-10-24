<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'DecisionLedger')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=ibm-plex-mono:400,500|ibm-plex-sans:400,500,600" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3">
            <a href="{{ route('overview') }}" class="text-lg font-semibold tracking-tight text-slate-900">DecisionLedger</a>
            <nav class="flex items-center gap-4 text-sm text-slate-600">
                <a href="{{ route('overview') }}" class="hover:text-slate-900">Overview</a>
                <a href="{{ route('decisions.index') }}" class="hover:text-slate-900">Decisions</a>
                <a href="{{ route('checks.show') }}" class="rounded-md bg-slate-900 px-3 py-1.5 font-medium text-white hover:bg-slate-800">Check PHP</a>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8">
        @if (session('status'))
            <div class="mb-6 rounded-md border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700">
                {{ session('status') }}
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="border-t border-slate-200 py-6 text-center text-xs text-slate-500">
        Analyzes pasted text only · Offline deterministic rules · No repository scan claimed
    </footer>
</body>
</html>
