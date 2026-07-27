@props(['route', 'label', 'activeRoutes' => null, 'icon' => null])

@php
$active = request()->routeIs($activeRoutes ?? $route);
@endphp

<a href="{{ route($route) }}" class="{{ $active ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-300' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 hover:text-gray-900 dark:hover:text-gray-100' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
    @if ($icon)
        <svg class="mr-3 flex-shrink-0 h-6 w-6 {{ $active ? 'text-indigo-500 dark:text-indigo-400' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}" />
        </svg>
    @endif
    {{ __($label) }}
</a>
