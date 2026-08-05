{{--
    Composant Card standardisé — remplace le pattern répété
    "rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700"
    présent dans des dizaines de vues.

    Usage:
    <x-card title="Titre" subtitle="Sous-titre optionnel">
        Contenu...
        <x-slot:actions><a href="#">Action</a></x-slot:actions>
    </x-card>
--}}
@props(['title' => null, 'subtitle' => null, 'padding' => 'p-6'])

<div {{ $attributes->merge(['class' => "rounded-lg bg-white dark:bg-slate-800 $padding shadow-sm ring-1 ring-gray-200 dark:ring-slate-700"]) }}>
    @if($title || $subtitle || isset($actions))
        <div class="flex items-start justify-between gap-4 {{ $slot->isNotEmpty() ? 'mb-5' : '' }}">
            <div>
                @if($title)
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</h3>
                @endif
                @if($subtitle)
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex-shrink-0">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    {{ $slot }}
</div>
