<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ dark: localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches) }" x-init="document.documentElement.classList.toggle('dark', dark); $watch('dark', value => { document.documentElement.classList.toggle('dark', value); localStorage.setItem('theme', value ? 'dark' : 'light'); })" :class="{ 'dark': dark }">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ __(config('app.name', 'Edu-Manager')) }}</title>

        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

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

        {{-- 
            Notifications toast (remplace les pages/bandeaux de confirmation).
             Déclenché via un événement 'push-toast' plutôt qu'un x-init : ce conteneur est
             en dehors de <main>, donc son x-init ne se rejouerait jamais après une navigation
             PJAX. Le <script> juste en dessous, lui, est réexécuté à chaque navigation PJAX
             (voir resources/js/pjax.js:executePageScripts, qui parcourt tout le document
             récupéré, pas seulement <main>) — les messages flash restent donc visibles même
             quand la navigation aboutit ailleurs que prévu (ex. redirection forcée).
         --}}
        <div
            x-data="{ toasts: [] }"
            x-on:push-toast.window="toasts.push($event.detail); if (! $event.detail.persistent) { setTimeout(() => toasts = toasts.filter(t => t.id !== $event.detail.id), 4000) }"
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
                    :class="{
                        'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-900/80 dark:text-emerald-100 dark:ring-emerald-700': toast.type === 'success',
                        'bg-red-50 text-red-800 ring-1 ring-red-200 dark:bg-red-900/80 dark:text-red-100 dark:ring-red-700': toast.type === 'error',
                        'bg-amber-50 text-amber-800 ring-1 ring-amber-200 dark:bg-amber-900/80 dark:text-amber-100 dark:ring-amber-700': toast.type === 'warning',
                        'bg-sky-50 text-sky-800 ring-1 ring-sky-200 dark:bg-sky-900/80 dark:text-sky-100 dark:ring-sky-700': toast.type === 'info',
                    }"
                >
                    <div class="flex-1 whitespace-pre-line" x-text="toast.message"></div>
                    <button type="button" @click="toasts = toasts.filter(x => x.id !== toast.id)" class="opacity-60 hover:opacity-100">&times;</button>
                </div>
            </template>
        </div>
        <script>
            // app.js démarre Alpine sur DOMContentLoaded (voir resources/js/app.js). Comme ce
            // script est aussi enregistré en tant qu'écouteur DOMContentLoaded mais AVANT
            // celui d'Alpine (il est parsé plus tôt dans le document), il s'exécuterait avant
            // qu'Alpine n'ait attaché x-on:push-toast.window sur le conteneur de toasts, et
            // l'événement partirait dans le vide. Le setTimeout(0) reporte l'exécution après
            // la fin de tous les écouteurs DOMContentLoaded du tick courant, Alpine.start()
            // inclus. Au rechargement PJAX (readyState déjà 'complete'), Alpine tourne déjà
            // depuis longtemps — le setTimeout(0) est alors une simple précaution sans effet.
            (function (fn) {
                if (document.readyState !== 'loading') {
                    setTimeout(fn, 0);
                } else {
                    document.addEventListener('DOMContentLoaded', () => setTimeout(fn, 0));
                }
            })(function () {
                @if(session('success'))
                    window.dispatchEvent(new CustomEvent('push-toast', { detail: { id: Date.now(), type: 'success', message: @json(session('success')) } }));
                @endif
                @if(session('error'))
                    window.dispatchEvent(new CustomEvent('push-toast', { detail: { id: Date.now() + 1, type: 'error', message: @json(session('error')) } }));
                @endif
                @if(session('warning'))
                    window.dispatchEvent(new CustomEvent('push-toast', { detail: { id: Date.now() + 2, type: 'warning', message: @json(session('warning')) } }));
                @endif
                @if(session('info'))
                    window.dispatchEvent(new CustomEvent('push-toast', { detail: { id: Date.now() + 3, type: 'info', message: @json(session('info')) } }));
                @endif
                {{--
                    temp_password/temp_credentials : un mot de passe temporaire doit rester
                    visible jusqu'à fermeture manuelle (persistent: true, pas d'auto-disparition
                    à 4s comme les autres toasts) — l'utilisateur doit avoir le temps de le noter.
                    Passait auparavant par une bannière HTML statique (@if(session(...))) rendue
                    en dehors de <main> : invisible après une navigation PJAX, qui ne re-rend que
                    <main>, contrairement à ce <script>, réexécuté à chaque navigation PJAX (voir
                    resources/js/pjax.js:executePageScripts) — d'où le mot de passe jamais
                    affiché en pratique malgré le message "notez-le avant de quitter la page".
                --}}
                @if(session('temp_password'))
                    window.dispatchEvent(new CustomEvent('push-toast', { detail: { id: Date.now() + 4, type: 'warning', persistent: true, message: @json('Mot de passe temporaire : '.session('temp_password')."\nNotez-le avant de quitter cette page.") } }));
                @endif
                @if(session('temp_credentials'))
                    window.dispatchEvent(new CustomEvent('push-toast', { detail: { id: Date.now() + 5, type: 'warning', persistent: true, message: @json(
                        'Mot de passe temporaire élève : '.session('temp_credentials')['student_password']
                        .(session('temp_credentials')['parent_password'] ? "\nMot de passe temporaire parent (".session('temp_credentials')['parent_matricule'].') : '.session('temp_credentials')['parent_password'] : '')
                        ."\nNotez-les avant de quitter cette page."
                    ) } }));
                @endif
            });
        </script>

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
                    <x-secondary-button type="button" x-on:click="open = false">Annuler</x-secondary-button>
                    <x-danger-button type="button" x-on:click="open = false; submitForm(form)" x-text="confirmLabel"></x-danger-button>
                </div>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>
