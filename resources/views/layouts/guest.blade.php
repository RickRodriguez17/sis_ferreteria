<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

        <title>{{ app(\App\Support\CompanySettings::class)->name() }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center bg-slate-100 px-4 py-10">
            <div class="mb-6 text-center">
                <a href="/" wire:navigate>
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-600 text-xl text-white shadow-lg"><i class="bi bi-shop"></i></span>
                    <span class="mt-3 block text-xl font-bold tracking-tight text-slate-800">{{ app(\App\Support\CompanySettings::class)->name() }}</span>
                </a>
            </div>
            <div class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white px-6 py-6 shadow-xl">
                {{ $slot }}
            </div>
        </div>
        @if (session('success'))
            <script>window.erpToast(@js(session('success')), 'success');</script>
        @endif
        @if (session('error'))
            <script>window.erpAlert({ icon: 'error', title: 'Error', text: @js(session('error')) });</script>
        @endif
        @if (session('status') && session('status') !== 'verification-link-sent')
            <script>window.erpToast(@js(session('status')), 'info');</script>
        @endif
    </body>
</html>
