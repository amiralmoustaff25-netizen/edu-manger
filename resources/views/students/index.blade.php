<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestion des élèves</h2>
                <p class="mt-1 text-sm text-gray-500">Recherche, statut, classe actuelle et accès aux fiches élèves.</p>
            </div>
            <a href="{{ route('registrations.create') }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                Nouvelle inscription
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <form method="GET" action="{{ route('students.index') }}" class="grid gap-4 md:grid-cols-4">
                    <div class="md:col-span-2">
                        <label for="search" class="block text-sm font-medium text-gray-700">Recherche</label>
                        <input id="search" name="search" value="{{ $filters['search'] ?? '' }}" type="text" placeholder="Matricule, nom, email ou matricule d’inscription" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="classroom_id" class="block text-sm font-medium text-gray-700">Classe</label>
                        <select id="classroom_id" name="classroom_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Toutes les classes</option>
                            @foreach($classrooms as $classroom)
                                <option value="{{ $classroom->id }}" @selected((string) ($filters['classroom_id'] ?? '') === (string) $classroom->id)>{{ $classroom->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Statut</label>
                        <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Tous</option>
                            <option value="active" @selected(($filters['status'] ?? '') === 'active')>Actifs</option>
                            <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>En attente</option>
                            <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Compte inactif</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2 md:col-span-4">
                        <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">Filtrer</button>
                        <a href="{{ route('students.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Réinitialiser</a>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Matricule</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Élève</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Classe actuelle</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Année</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Statut</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-600">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($students as $student)
                                @php($registration = $student->latestRegistration)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $student->matricule }}</td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-gray-900">{{ $student->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $student->email ?? 'Email non renseigné' }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">{{ $registration->classroom->name ?? 'Non inscrit' }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $registration->schoolYear->year_string ?? $registration->academic_year ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ ($registration?->status === 'active' && $student->is_active) ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                            {{ $student->is_active ? ($registration?->status === 'active' ? 'Actif' : 'En attente') : 'Inactif' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('students.show', $student) }}" class="rounded-md border border-indigo-200 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Voir la fiche</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">Aucun élève trouvé.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 px-4 py-3">
                    {{ $students->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
