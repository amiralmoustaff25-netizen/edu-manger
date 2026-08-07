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
    <body x-data="pjax()" class="font-sans antialiased bg-gray-100 dark:bg-slate-900 transition-colors duration-300">
        <div x-show="loading" x-cloak class="fixed inset-x-0 top-0 h-1 bg-indigo-600 z-50 animate-pulse"></div>
        <x-sidebar>
            @isset($header)
                <header class="bg-white dark:bg-slate-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        @isset($breadcrumbs)
                            {{ $breadcrumbs }}
                        @endisset
                        {{ $header }}
                    </div>
                </header>
            @endisset

            @if(isset($slot))
                {{ $slot }}
            @else
                @yield('content')
            @endif
        </x-sidebar>

        {{-- Notifications toast (remplace les pages/bandeaux de confirmation) --}}
        <div
            x-data="{ toasts: [] }"
            x-init="
                @if(session('success')) toasts.push({ id: Date.now(), type: 'success', message: @js(session('success')) }); @endif
                @if(session('error')) toasts.push({ id: Date.now() + 1, type: 'error', message: @js(session('error')) }); @endif
                toasts.forEach(t => setTimeout(() => toasts = toasts.filter(x => x.id !== t.id), 4000));
            "
            class="fixed top-4 right-4 z-[70] flex flex-col gap-3 w-full max-w-sm px-4 sm:px-0"
        >
            <template x-for="toast in toasts" :key="toast.id">
                <div
                    x-show="true"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-x-4"
                    x-transition:enter-end="opacity-100 translate-x-0"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="rounded-lg shadow-lg px-4 py-3 text-sm font-medium flex items-start gap-3"
                    :class="toast.type === 'success' ? 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-900/80 dark:text-emerald-100 dark:ring-emerald-700' : 'bg-red-50 text-red-800 ring-1 ring-red-200 dark:bg-red-900/80 dark:text-red-100 dark:ring-red-700'"
                >
                    <span x-text="toast.message" class="flex-1"></span>
                    <button type="button" @click="toasts = toasts.filter(x => x.id !== toast.id)" class="opacity-60 hover:opacity-100">&times;</button>
                </div>
            </template>
        </div>

        <x-cancel-payment-modal />

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
