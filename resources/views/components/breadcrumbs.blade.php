{{--
    Fil d'Ariane réutilisable.
    Usage:
    <x-breadcrumbs :items="[
        ['label' => 'Élèves', 'route' => 'students.index'],
        ['label' => $student->name],
    ]" />
    Le dernier élément n'est jamais un lien (page courante).
--}}
@props(['items' => []])

@if(!empty($items))
    <nav aria-label="Fil d'Ariane" class="mb-4">
        <ol class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 flex-wrap">
            <li class="flex items-center">
                <a href="{{ route('dashboard') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">
                    Accueil
                </a>
            </li>
            @foreach($items as $index => $item)
                <li class="flex items-center gap-1.5">
                    <svg class="h-4 w-4 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    @if(!empty($item['route']) && $index !== count($items) - 1)
                        <a href="{{ route($item['route'], $item['params'] ?? []) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">
                            {{ $item['label'] }}
                        </a>
                    @else
                        <span class="font-medium text-gray-700 dark:text-gray-200">{{ $item['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
