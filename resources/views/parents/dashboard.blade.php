<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Espace Famille</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tableau de bord des parents et responsables.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <x-card padding="p-5">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Enfants associés</p>
                    <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $parent->students->count() }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Suivi de vos enfants dans l'école.</p>
                </x-card>
                <x-card padding="p-5">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Notifications</p>
                    <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ auth()->user()->unreadNotifications->count() }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Messages non lus.</p>
                </x-card>
                <x-card padding="p-5">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Derniers éléments</p>
                    <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $parent->students->sum('notes_count') }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Total des notes disponibles.</p>
                </x-card>
            </div>

            <div class="rounded-lg bg-white dark:bg-slate-800 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-200 dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Rappels de paiement</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Échéances et retards de paiement pour vos enfants.</p>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse($reminders as $reminder)
                        <div class="px-6 py-4 sm:flex sm:items-start sm:justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $reminder->registration->user->name ?? 'Élève' }}
                                </p>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $reminder->message }}</p>
                            </div>
                            <div class="mt-2 sm:mt-0 flex items-center gap-2 shrink-0">
                                <span @class([
                                    'inline-flex items-center rounded-full px-3 py-1 text-xs font-medium',
                                    'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' => $reminder->type === 'overdue',
                                    'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' => $reminder->type !== 'overdue',
                                ])>
                                    {{ $reminder->type === 'overdue' ? 'Retard' : 'À venir' }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $reminder->scheduled_at->format('d/m/Y') }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            Aucun rappel de paiement en attente.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-lg bg-white dark:bg-slate-800 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-200 dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Mes enfants</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Accédez rapidement à la fiche et aux informations de chaque enfant.</p>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse($parent->students as $student)
                        <div class="px-6 py-5 sm:flex sm:items-center sm:justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $student->name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Matricule : {{ $student->matricule }}</p>
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                    Classe : {{ optional(optional($student->latestRegistration)->classroom)->name ?? 'Non assignée' }}
                                    • Année : {{ optional(optional($student->latestRegistration)->schoolYear)->year_string ?? 'N/A' }}
                                </p>
                            </div>
                            <div class="mt-4 sm:mt-0 flex flex-wrap gap-2 items-center">
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-sm text-slate-700 dark:bg-slate-700 dark:text-slate-200">
                                    {{ $student->notes_count }} note{{ $student->notes_count > 1 ? 's' : '' }}
                                </span>
                                    <a href="{{ route('parents.children.profile', ['student' => $student->id]) }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                        Voir la fiche
                                    </a>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            Aucun enfant associé pour le moment.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
