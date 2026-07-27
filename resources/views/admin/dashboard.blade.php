<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Administration</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Accès centralisé aux fonctionnalités selon vos droits.</p>
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-300">
                Connecté en tant que <span class="font-semibold">{{ auth()->user()->name }}</span>
                @if ($isSuperAdmin)
                    <span class="ml-2 rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-800 dark:bg-purple-900/40 dark:text-purple-200">Super Admin</span>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($modules as $module)
                    <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">
                        <div class="mb-4 flex items-center gap-3">
                            <div class="rounded-lg bg-indigo-50 dark:bg-indigo-900/30 p-2 text-indigo-600 dark:text-indigo-300">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $module['icon'] }}" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $module['title'] }}</h3>
                        </div>
                        <ul class="space-y-2">
                            @foreach ($module['items'] as $item)
                                <li>
                                    <a href="{{ route($item['route']) }}" class="group flex items-center justify-between rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-indigo-50 dark:text-gray-300 dark:hover:bg-slate-700/50">
                                        <span>{{ $item['label'] }}</span>
                                        <svg class="h-4 w-4 text-gray-400 group-hover:text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>

            @if ($modules->isEmpty())
                <div class="rounded-lg border border-gray-200 bg-white dark:bg-slate-800 dark:border-slate-700 p-8 text-center text-gray-500 dark:text-gray-400">
                    Aucun module administratif accessible avec vos droits actuels.
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
