<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Tableau de bord — Surveillant') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Accès rapide -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @can('voir-presences')
                    <a href="{{ route('surveillant.attendances.index') }}" class="rounded-lg bg-white dark:bg-slate-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition">
                        <div class="flex items-center gap-3">
                            <div class="rounded-full bg-indigo-100 dark:bg-indigo-900/40 p-2.5 text-indigo-600 dark:text-indigo-300">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6 4h2" /></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Présences élèves</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $todaysAttendances }}</p>
                            </div>
                        </div>
                    </a>
                @endcan

                @can('voir-pointage-enseignants')
                    <a href="{{ route('teacher-attendances.index') }}" class="rounded-lg bg-white dark:bg-slate-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition">
                        <div class="flex items-center gap-3">
                            <div class="rounded-full bg-emerald-100 dark:bg-emerald-900/40 p-2.5 text-emerald-600 dark:text-emerald-300">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pointage enseignants</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $todaysSessions }}</p>
                            </div>
                        </div>
                    </a>
                @endcan

                @can('voir-programmes')
                    <a href="{{ route('programs.index') }}" class="rounded-lg bg-white dark:bg-slate-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition">
                        <div class="flex items-center gap-3">
                            <div class="rounded-full bg-amber-100 dark:bg-amber-900/40 p-2.5 text-amber-600 dark:text-amber-300">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Programmes à valider</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $pendingPrograms }}</p>
                            </div>
                        </div>
                    </a>
                @endcan

                @can('voir-cahier-textes')
                    <a href="{{ route('cahier-textes.dashboard.index') }}" class="rounded-lg bg-white dark:bg-slate-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition">
                        <div class="flex items-center gap-3">
                            <div class="rounded-full bg-purple-100 dark:bg-purple-900/40 p-2.5 text-purple-600 dark:text-purple-300">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Cahier de textes</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">&rarr;</p>
                            </div>
                        </div>
                    </a>
                @endcan
            </div>

            <!-- Liste des classes -->
            <div class="rounded-lg bg-white dark:bg-slate-800 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-200 dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Classes</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Accès direct aux feuilles de présence par classe.</p>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse($classrooms as $classroom)
                        <div class="px-6 py-4 sm:flex sm:items-center sm:justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $classroom->name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $classroom->schoolYear?->year_string ?? 'Année non définie' }}
                                    &bull; {{ $classroom->registrations()->where('status', 'active')->count() }} élèves
                                </p>
                            </div>
                            <div class="mt-3 sm:mt-0 flex flex-wrap gap-2">
                                @can('voir-presences')
                                    <a href="{{ route('surveillant.attendances.class', ['classroom' => $classroom->id]) }}" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-700">
                                        Présences
                                    </a>
                                @endcan
                                <a href="{{ route('students.index', ['classroom_id' => $classroom->id]) }}" class="inline-flex items-center rounded-md bg-white px-3 py-1.5 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-slate-700 dark:text-gray-100 dark:ring-slate-600 dark:hover:bg-slate-600">
                                    Élèves
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            Aucune classe disponible.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
