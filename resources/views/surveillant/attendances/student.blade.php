<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Historique de présences') }} — {{ $student->name }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Résumé -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-lg bg-white dark:bg-slate-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 text-center">
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['present'] }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Présences</p>
                </div>
                <div class="rounded-lg bg-white dark:bg-slate-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 text-center">
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $stats['absent'] }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Absences</p>
                </div>
                <div class="rounded-lg bg-white dark:bg-slate-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 text-center">
                    <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $stats['late'] }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Retards</p>
                </div>
                <div class="rounded-lg bg-white dark:bg-slate-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 text-center">
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['excused'] }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Excusés</p>
                </div>
            </div>

            <!-- Liste -->
            <div class="rounded-lg bg-white dark:bg-slate-800 shadow-sm ring-1 ring-gray-200 dark:ring-slate-700 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-200 dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Détail</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-gray-50 dark:bg-slate-700/50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Date</th>
                                <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Classe</th>
                                <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Statut</th>
                                <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Observation</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                            @forelse($attendances as $attendance)
                                <tr class="text-sm text-gray-700 dark:text-gray-200">
                                    <td class="px-5 py-4 whitespace-nowrap">{{ $attendance->date->format('d/m/Y') }}</td>
                                    <td class="px-5 py-4">{{ $attendance->classroom?->name ?? '—' }}</td>
                                    <td class="px-5 py-4"><x-badge :color="$attendance->status === 'present' ? 'green' : ($attendance->status === 'absent' ? 'red' : 'amber')">{{ $attendance->status_label }}</x-badge></td>
                                    <td class="px-5 py-4">{{ $attendance->notes ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">Aucune présence enregistrée pour cet élève.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($attendances->hasPages())
                    <div class="border-t border-gray-200 dark:border-slate-700 px-5 py-4">{{ $attendances->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
