@props(['title', 'icon', 'open' => false])

<div x-data="{ open: {{ $open ? 'true' : 'false' }} }">
    <button @click="open = !open" class="w-full flex items-center justify-between gap-2 px-2 py-2 text-sm font-semibold text-left text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-700 rounded-md group">
        <span class="flex items-center min-w-0 flex-1">
            <svg class="mr-3 flex-shrink-0 h-6 w-6 text-gray-400 group-hover:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}" />
            </svg>
            <span class="truncate">{{ __($title) }}</span>
        </span>
        <span class="flex-shrink-0">
            <svg x-show="!open" class="h-4 w-4 text-gray-400 group-hover:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <svg x-show="open" x-cloak class="h-4 w-4 text-gray-400 group-hover:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </span>
    </button>
    <div x-show="open" x-collapse class="ml-4 space-y-1">
        {{ $slot }}
    </div>
</div>
