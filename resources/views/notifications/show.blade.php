<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Notification') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                @php
                    $data = $notification->data;
                    $type = $data['type'] ?? 'information';
                    $priority = $data['priority'] ?? 'normal';
                    $category = $data['category'] ?? 'system';
                    $title = $data['title'] ?? 'Notification';
                    $content = $data['content'] ?? '';
                    $author = $data['author_name'] ?? null;
                    $attachment = $data['attachment'] ?? null;
                @endphp

                <div class="p-6 border-b border-gray-100 dark:border-slate-700">
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        <span class="px-2 py-1 text-xs font-semibold rounded-md
                            @if($type === 'urgent') bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300
                            @elseif($type === 'important') bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300
                            @else bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 @endif">
                            {{ ucfirst($type) }}
                        </span>
                        <span class="px-2 py-1 text-xs rounded-md bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-gray-400">
                            {{ $category === 'administrative' ? 'Administration' : 'Système' }}
                        </span>
                    </div>

                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">{{ $title }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                        {{ $notification->created_at?->format('d/m/Y H:i') }}
                        @if($author)
                            — {{ $author }}
                        @endif
                    </p>

                    <div class="prose dark:prose-invert max-w-none text-gray-800 dark:text-gray-200">
                        {!! nl2br(e($content)) !!}
                    </div>

                    @if($attachment)
                        <p class="mt-6 text-sm text-gray-600 dark:text-gray-400">
                            Pièce jointe : <a href="{{ Storage::url($attachment) }}" target="_blank" class="text-indigo-600 dark:text-indigo-400 underline">Voir le fichier</a>
                        </p>
                    @endif
                </div>
            </div>

            <div class="mt-6">
                <a href="{{ route('notifications.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 dark:bg-slate-700 dark:text-gray-200 dark:hover:bg-slate-600">
                    {{ __('Retour aux notifications') }}
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
