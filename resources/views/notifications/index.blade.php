<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Centre de notifications') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200">Mes notifications</h3>
                <form action="{{ route('notifications.mark-all-read') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm rounded-md hover:bg-gray-300 dark:bg-slate-700 dark:text-gray-200 dark:hover:bg-slate-600">
                        {{ __('Tout marquer comme lu') }}
                    </button>
                </form>
            </div>

            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="divide-y divide-gray-100 dark:divide-slate-700">
                    @forelse($notifications as $notification)
                        @php
                            $isRead = $notification->read_at !== null;
                            $data = $notification->data;
                            $type = $data['type'] ?? 'information';
                            $priority = $data['priority'] ?? 'normal';
                            $category = $data['category'] ?? 'system';
                            $title = $data['title'] ?? 'Notification';
                        @endphp
                        <div class="p-4 flex flex-col sm:flex-row sm:items-start gap-4 {{ $isRead ? 'bg-white dark:bg-slate-800' : 'bg-indigo-50/50 dark:bg-indigo-900/20' }}">
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-md
                                        @if($type === 'urgent') bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300
                                        @elseif($type === 'important') bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300
                                        @else bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 @endif">
                                        {{ ucfirst($type) }}
                                    </span>
                                    @if($category === 'administrative')
                                        <span class="px-2 py-0.5 text-xs rounded-md bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-gray-400">Administration</span>
                                    @else
                                        <span class="px-2 py-0.5 text-xs rounded-md bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-gray-400">Système</span>
                                    @endif
                                    @if(! $isRead)
                                        <span class="relative flex h-3 w-3">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                                        </span>
                                    @endif
                                </div>
                                <h4 class="text-base font-semibold text-gray-900 dark:text-white truncate">{{ $title }}</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $notification->created_at?->diffForHumans() }}</p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <a href="{{ route('notifications.show', $notification) }}" class="px-3 py-1.5 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Voir</a>
                                @if(! $isRead)
                                    <form action="{{ route('notifications.mark-as-read', $notification) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 text-sm bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 dark:bg-slate-700 dark:text-gray-200 dark:hover:bg-slate-600">Marquer comme lu</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                            {{ __('Aucune notification pour le moment.') }}
                        </div>
                    @endforelse
                </div>
                <div class="p-4 border-t border-gray-100 dark:border-slate-700">
                    {{ $notifications->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
