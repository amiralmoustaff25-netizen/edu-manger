{{--
    Composant Badge standardisé pour les statuts (paiement, inscription, etc.)
    Usage: <x-badge color="green">Actif</x-badge>
    Couleurs disponibles: gray, green, amber, red, indigo, blue
--}}
@props(['color' => 'gray'])

@php
$colors = [
    'gray' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    'green' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200',
    'amber' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
    'red' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    'indigo' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
    'blue' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
];
$classes = $colors[$color] ?? $colors['gray'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium $classes"]) }}>
    {{ $slot }}
</span>
