<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ dark: localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches) }" x-init="document.documentElement.classList.toggle('dark', dark); $watch('dark', value => { document.documentElement.classList.toggle('dark', value); localStorage.setItem('theme', value ? 'dark' : 'light'); })" :class="{ 'dark': dark }">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ __(config('app.name', 'Edu-Manager')) }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-100 dark:bg-slate-900 transition-colors duration-300">
        <x-sidebar>
            @isset($header)
                <header class="bg-white dark:bg-slate-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            @if (session('success'))
                <div x-data="{ show: true }"
                    x-init="setTimeout(() => show = false, 3000)"
                    x-show="show"
                    x-transition:leave="transition ease-in duration-500"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="max-w-7xl mx-auto mt-4 px-4 sm:px-6 lg:px-8">
                    <div class="bg-green-100 dark:bg-green-900/50 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-200 px-4 py-3 rounded">
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            @if(isset($slot))
                {{ $slot }}
            @else
                @yield('content')
            @endif
        </x-sidebar>

        <div
            x-data="{ open: false, form: null, title: '', message: '', confirmLabel: 'Confirmer' }"
            x-on:open-confirmation.window="form = $event.detail.form; title = $event.detail.title; message = $event.detail.message; confirmLabel = $event.detail.confirmLabel || 'Confirmer'; open = true"
            x-show="open"
            x-cloak
            class="fixed inset-0 z-[60] flex items-center justify-center px-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="confirmation-title"
        >
            <div class="absolute inset-0 bg-slate-950/60" x-on:click="open = false"></div>
            <div class="relative w-full max-w-lg rounded-xl bg-white p-6 shadow-2xl dark:bg-slate-800" x-on:keydown.escape.window="open = false">
                <div class="flex items-start gap-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">!</div>
                    <div class="min-w-0">
                        <h2 id="confirmation-title" class="text-lg font-semibold text-slate-900 dark:text-white" x-text="title"></h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300" x-text="message"></p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" x-on:click="open = false" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Annuler</button>
                    <button type="button" x-on:click="open = false; form.submit()" class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700" x-text="confirmLabel"></button>
                </div>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>
