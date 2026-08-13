{{--
    Panneau latéral coulissant réutilisable — pour les formulaires courts qui ne
    justifient pas une page dédiée (ex. création rapide depuis une liste).
    N'importe quel contenu peut y être placé, y compris un <form> classique :
    la soumission passe par le PJAX existant (resources/js/pjax.js) comme sur
    n'importe quelle page, aucun JS supplémentaire n'est nécessaire pour ça.

    Usage :
    <x-drawer title="Nouveau X" trigger-label="+ Nouveau X">
        <form ...>...</form>
    </x-drawer>

    Pour un déclencheur personnalisé (ex. une icône) plutôt que le bouton par
    défaut, utiliser le slot nommé "trigger" :
    <x-drawer title="...">
        <x-slot:trigger><button type="button">...</button></x-slot:trigger>
        ...
    </x-drawer>

    Le drawer s'ouvre automatiquement si $open est vrai au chargement (utile
    pour le rouvrir après un échec de validation, cf. fee-types/index.blade.php).
--}}
@props(['title' => null, 'triggerLabel' => 'Ouvrir', 'width' => 'max-w-md', 'open' => false])

<div x-data="{ open: @js($open) }" x-on:keydown.escape.window="open = false" {{ $attributes->only('class') }}>
    @isset($trigger)
        <span x-on:click="open = true">{{ $trigger }}</span>
    @else
        <button type="button" x-on:click="open = true" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
            {{ $triggerLabel }}
        </button>
    @endisset

    <div
        x-show="open"
        x-cloak
        class="fixed inset-0 z-[65]"
        role="dialog"
        aria-modal="true"
        @if($title) aria-label="{{ $title }}" @endif
    >
        <div
            class="absolute inset-0 bg-slate-950/60"
            x-show="open"
            x-transition:enter="transition-opacity ease-linear duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-on:click="open = false"
        ></div>

        <div
            class="fixed inset-y-0 right-0 flex {{ $width }} w-full"
            x-show="open"
            x-transition:enter="transform transition ease-in-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
        >
            <div class="flex h-full w-full flex-col bg-white shadow-2xl dark:bg-slate-800">
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-slate-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $title }}</h2>
                    <button type="button" x-on:click="open = false" class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-700 dark:hover:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500" aria-label="Fermer le panneau">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-6 py-6">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</div>
