@props(['items', 'level' => 0])

@php
$user = auth()->user();

$hasActiveChild = function ($items) use (&$hasActiveChild) {
    foreach ($items as $item) {
        $routes = $item['active_routes'] ?? [];
        if (!empty($item['route'])) {
            $routes[] = $item['route'];
        }
        if (request()->routeIs($routes)) {
            return true;
        }
        if (!empty($item['children']) && $hasActiveChild($item['children'])) {
            return true;
        }
    }
    return false;
};

$filterVisible = function ($items) use (&$filterVisible, $user) {
    $result = [];
    foreach ($items as $item) {
        if (! $user) {
            continue;
        }
        if (!empty($item['exclude_roles']) && $user->hasAnyRole($item['exclude_roles'])) {
            continue;
        }
        if (!empty($item['roles']) && ! $user->hasAnyRole($item['roles'])) {
            continue;
        }
        if (!empty($item['permissions']) && ! collect($item['permissions'])->contains(fn ($permission) => $user->can($permission))) {
            continue;
        }
        if (!empty($item['permission']) && ! $user->can($item['permission'])) {
            continue;
        }
        if (!empty($item['children'])) {
            $children = $filterVisible($item['children']);
            if (empty($children)) {
                continue;
            }
            $item['children'] = $children;
        }
        $result[] = $item;
    }
    return $result;
};

$visibleItems = $filterVisible($items);
@endphp

@foreach ($visibleItems as $item)
    @if (!empty($item['children']))
        @php
            $isOpen = $hasActiveChild($item['children']);
            $showIcon = $level === 0 || !empty($item['icon']);
            $isHeading = !empty($item['heading']);
        @endphp

        @if ($isHeading)
            <div class="px-2 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                <span class="flex items-center gap-2">
                    @if ($showIcon && !empty($item['icon']))
                        <svg class="h-5 w-5 flex-shrink-0 text-gray-400 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" preserveAspectRatio="xMidYMid meet" style="overflow:visible">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}" />
                        </svg>
                    @endif
                    <span class="truncate">{{ __($item['label']) }}</span>
                </span>
            </div>

            <div class="grid">
                <div class="overflow-hidden {{ $level === 0 ? 'ml-4' : 'ml-3' }} space-y-1">
                    <x-sidebar.menu :items="$item['children']" :level="$level + 1" />
                </div>
            </div>
        @else
            @php
                // Comportement accordéon au niveau 0 seulement (École / Pédagogie / Finance /
                // Administration...) : ouvrir une section referme les autres via un événement
                // window plutôt qu'un store partagé, pour rester simple et local à ce composant
                // récursif. Sans ça, plusieurs sections à nombreux sous-menus (ex. Finance, 9
                // liens) pouvaient rester ouvertes en même temps et pousser toute la barre
                // latérale bien au-delà de la hauteur de l'écran.
                $accordionKey = addslashes($item['label']);
                $toggleClick = $level === 0
                    ? "open = !open; if (open) \$dispatch('sidebar-accordion', '{$accordionKey}')"
                    : 'open = !open';
            @endphp
            <div x-data="{ open: {{ $isOpen ? 'true' : 'false' }} }"
                 @if ($level === 0) x-on:sidebar-accordion.window="if ($event.detail !== '{{ $accordionKey }}') open = false" @endif>
                <div class="w-full flex items-center justify-between gap-2">
                    @if (!empty($item['route']))
                        <a href="{{ route($item['route']) }}" x-on:click="sidebarOpen = false" class="w-full flex items-center gap-2 px-2 py-2 text-sm font-semibold rounded-md {{ $isOpen ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-700' }}">
                            @if ($showIcon && !empty($item['icon']))
                                <svg class="mr-4 flex-shrink-0 h-7 w-7 overflow-visible {{ $isOpen ? 'text-indigo-500 dark:text-indigo-400' : 'text-gray-400 hover:text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" preserveAspectRatio="xMidYMid meet" style="overflow:visible">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}" />
                                </svg>
                            @endif
                            <span class="truncate">{{ __($item['label']) }}</span>
                        </a>
                    @else
                        <button
                            type="button"
                            @click="{{ $toggleClick }}"
                            class="w-full flex items-center gap-2 px-2 py-2 text-sm font-semibold text-left rounded-md {{ $isOpen ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-700' }}"
                        >
                            @if ($showIcon && !empty($item['icon']))
                                <svg class="mr-4 flex-shrink-0 h-7 w-7 overflow-visible {{ $isOpen ? 'text-indigo-500 dark:text-indigo-400' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" preserveAspectRatio="xMidYMid meet" style="overflow:visible">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}" />
                                </svg>
                            @endif
                            <span class="truncate">{{ __($item['label']) }}</span>
                        </button>
                    @endif

                    <button
                        type="button"
                        @click="{{ $toggleClick }}"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-md text-gray-400 hover:text-gray-600 dark:text-gray-400 dark:hover:text-gray-200"
                        aria-label="{{ $isOpen ? __('Réduire le menu') : __('Ouvrir le menu') }}"
                    >
                        <svg x-show="!open" class="h-4 w-4 overflow-visible" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="overflow:visible">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        <svg x-show="open" x-cloak class="h-4 w-4 overflow-visible" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="overflow:visible">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>

                <div
                    x-show="open"
                    x-transition:enter="transition-[grid-template-rows] ease-out duration-200"
                    x-transition:enter-start="grid-rows-[0fr]"
                    x-transition:enter-end="grid-rows-[1fr]"
                    x-transition:leave="transition-[grid-template-rows] ease-in duration-150"
                    x-transition:leave-start="grid-rows-[1fr]"
                    x-transition:leave-end="grid-rows-[0fr]"
                    class="grid"
                >
                    <div class="overflow-hidden {{ $level === 0 ? 'ml-4' : 'ml-3' }} space-y-1">
                        <x-sidebar.menu :items="$item['children']" :level="$level + 1" />
                    </div>
                </div>
            </div>
        @endif
    @else
        <x-sidebar.link
            :route="$item['route']"
            :label="$item['label']"
            :active-routes="$item['active_routes'] ?? null"
            :icon="($level === 0 || !empty($item['icon'])) ? ($item['icon'] ?? null) : null"
        />
    @endif
@endforeach
