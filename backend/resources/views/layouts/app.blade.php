<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="admin-shell lg:flex">
            @include('layouts.navigation')

            <div class="min-h-screen flex-1">
                <header class="admin-topbar">
                    <div class="flex items-center gap-4">
                        <button class="rounded-xl border border-slate-200 px-3 py-2 text-slate-500 lg:hidden">☰</button>
                        <span class="inline-flex rounded-full bg-indigo-500 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white">
                            Kho giai phap
                        </span>
                        <div class="hidden min-w-[280px] items-center rounded-full border border-slate-200 bg-slate-50 px-4 py-2 md:flex">
                            <input class="w-full border-0 bg-transparent p-0 text-sm text-slate-700 placeholder:text-slate-400 focus:ring-0" placeholder="Tim chuc nang..." />
                            <span class="text-rose-500">⌕</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="hidden rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 md:block">
                            So du: 10.000d
                        </div>
                        <div class="hidden text-sm font-medium text-slate-500 md:block">Tro giup</div>
                        <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-200 font-bold text-slate-600">
                                {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                            </div>
                            <div class="hidden text-left md:block">
                                <div class="text-sm font-semibold text-slate-900">{{ Auth::user()->name }}</div>
                                <div class="text-xs text-slate-500">{{ str(Auth::user()->platform_role?->value ?? 'workspace_owner')->replace('_', ' ')->title() }}</div>
                            </div>
                        </div>
                    </div>
                </header>

                @isset($header)
                    <div class="border-b border-slate-200 bg-white/70 px-6 py-6 backdrop-blur">
                        {{ $header }}
                    </div>
                @endisset

                <main>
                    @if (session('status'))
                        <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
                            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                                {{ session('status') }}
                            </div>
                        </div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
