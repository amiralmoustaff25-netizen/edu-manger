<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Mon Espace Élève') }}
        </h2>
    </x-slot>


    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Message de bienvenue -->
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg mb-6 @if($user->cycle === 'primaire') border-t-4 border-emerald-500 @elseif($user->cycle === 'college') border-t-4 border-orange-500 @else border-t-4 border-red-500 @endif">
                <div class="p-6 text-gray-900 dark:text-gray-100 border-b border-gray-200 dark:border-slate-700 flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-bold @if($user->cycle === 'primaire') text-emerald-600 dark:text-emerald-400 @elseif($user->cycle === 'college') text-orange-600 dark:text-orange-400 @else text-red-600 dark:text-red-400 @endif mb-2">
                            {{ __('Bonjour, :name !', ['name' => $user->name]) }}
                        </h3>
                        <p class="text-gray-600 dark:text-gray-300">
                            {{ __('Bienvenue sur ton portail EduManager. Voici le résumé de tes informations scolaires.') }}
                        </p>
                    </div>
                    <div class="hidden md:block">
                        @if($user->profile_photo_path)
                            <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}" class="h-16 w-16 rounded-full object-cover ring-2 ring-white dark:ring-slate-600">
                        @else
                            <div class="h-16 w-16 rounded-full @if($user->cycle === 'primaire') bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400 @elseif($user->cycle === 'college') bg-orange-100 dark:bg-orange-900/50 flex items-center justify-center text-orange-600 dark:text-orange-400 @else bg-red-100 dark:bg-red-900/50 flex items-center justify-center text-red-600 dark:text-red-400 @endif font-bold text-2xl">
                                {{ mb_substr($user->name, 0, 1) }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Grille des statistiques -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                
                <!-- Carte 1 : Classe -->
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm p-6 border border-gray-100 dark:border-slate-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                        </div>
                        <div class="ml-4">
                            <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('Ma Classe') }}</p>
                            <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                                {{ $registration->classroom->name ?? __('Classe non assignée') }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Carte 2 : Professeur Principal -->
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm p-6 border border-gray-100 dark:border-slate-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 dark:bg-green-900/50 text-green-600 dark:text-green-400">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </div>
                        <div class="ml-4">
                            <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('Professeur Principal') }}</p>
                            <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                                {{ $registration->classroom->teacher->name ?? __('Aucun professeur') }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Carte 3 : Moyenne générale -->
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm p-6 border border-gray-100 dark:border-slate-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900/50 text-purple-600 dark:text-purple-400">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        </div>
                        <div class="ml-4">
                            <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('Moyenne Générale') }}</p>
                            <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                                {{ number_format($moyenne, 2, ',', ' ') }}/20
                            </p>
                        </div>
                    </div>
                </div>
                
            </div>

            <!-- Section Notes & Paiements -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- Dernières notes -->
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-100 dark:border-slate-700">
                        <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">📚 {{ __('Mes Dernières Notes') }}</h4>
                        @if($notes->count() > 0)
                            <div class="space-y-3">
                                @foreach($notes as $note)
                                    <div class="flex items-center justify-between p-3 rounded-lg @if($note->valeur >= 10) bg-green-50 dark:bg-green-900/30 border-l-4 border-green-500 @else bg-red-50 dark:bg-red-900/30 border-l-4 border-red-500 @endif">
                                        <div>
                                            <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $note->matiere->nom ?? __('Matière inconnue') }}</p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $note->type_evaluation }} — {{ $note->periode }}</p>
                                        </div>
                                        <span class="text-lg font-bold @if($note->valeur >= 10) text-green-600 dark:text-green-400 @else text-red-600 dark:text-red-400 @endif">
                                            {{ $note->valeur }}/20
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500 dark:text-gray-400 italic">{{ __('Aucune note disponible pour le moment.') }}</p>
                        @endif
                    </div>
                </div>

                <!-- Paiements -->
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-100 dark:border-slate-700">
                        <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">💰 {{ __('Mes Paiements') }}</h4>
                        
                        <!-- Solde -->
                        <div class="mb-4 p-4 rounded-lg @if($remaining <= 0) bg-green-50 dark:bg-green-900/30 border border-green-200 @else bg-amber-50 dark:bg-amber-900/30 border border-amber-200 @endif">
                            <div class="flex justify-between items-center">
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('Solde restant') }}</span>
                                <span class="text-xl font-bold @if($remaining <= 0) text-green-600 dark:text-green-400 @else text-amber-600 dark:text-amber-400 @endif">
                                    {{ number_format($remaining, 0, ',', ' ') }} FCFA
                                </span>
                            </div>
                        </div>

                        @if($payments->count() > 0)
                            <div class="space-y-2">
                                @foreach($payments as $payment)
                                    <div class="flex items-center justify-between p-2 rounded bg-gray-50 dark:bg-slate-700/50">
                                        <div>
                                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $payment->month }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $payment->payment_date->format('d/m/Y') }}</p>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</span>
                                            <span class="block text-xs @if($payment->status === 'complet') text-green-600 @else text-amber-600 @endif">
                                                {{ $payment->status === 'complet' ? __('Complet') : __('Partiel') }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500 dark:text-gray-400 italic">{{ __('Aucun paiement enregistré.') }}</p>
                        @endif
                    </div>
                </div>

            </div>

            <!-- Parents -->
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg mt-6">
                <div class="p-6 border-b border-gray-100 dark:border-slate-700">
                    <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">👨‍👩‍👧 {{ __('Mes Parents / Responsables') }}</h4>
                    @if($user->parents->count() > 0)
                        <div class="space-y-3">
                            @foreach($user->parents as $parent)
                                <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-slate-700/50">
                                    <div>
                                        <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $parent->nom }} {{ $parent->prenom }}</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $parent->pivot->lien_parente ?? '-' }}</p>
                                    </div>
                                    <div class="text-right">
                                        @if($parent->pivot->est_responsable_financier)<span class="inline-flex px-2 py-1 text-xs rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">Resp. financier</span>@endif
                                        @if($parent->pivot->est_contact_urgence)<span class="inline-flex px-2 py-1 text-xs rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">Urgence</span>@endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400 italic">{{ __('Aucun parent enregistré.') }}</p>
                    @endif
                </div>
            </div>

            <!-- Bulletins -->
            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg mt-6">
                <div class="p-6 border-b border-gray-100 dark:border-slate-700">
                    <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">📄 {{ __('Mes Bulletins') }}</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        @foreach(['trimestre_1' => 'Trimestre 1', 'trimestre_2' => 'Trimestre 2', 'trimestre_3' => 'Trimestre 3'] as $period => $label)
                            <div class="rounded-md border border-gray-200 dark:border-slate-700 p-4">
                                <p class="font-medium text-gray-800 dark:text-gray-200 mb-3">{{ $label }}</p>
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('bulletins.show', [$user, $period]) }}" target="_blank" class="px-3 py-1.5 bg-indigo-600 text-white text-xs rounded-md hover:bg-indigo-700">Voir</a>
                                    <a href="{{ route('bulletins.pdf', [$user, $period]) }}" target="_blank" class="px-3 py-1.5 bg-gray-200 text-gray-700 text-xs rounded-md hover:bg-gray-300 dark:bg-slate-700 dark:text-gray-200 dark:hover:bg-slate-600">PDF</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Présences & Sanctions -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-100 dark:border-slate-700">
                        <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">✅ {{ __('Mes Dernières Présences') }}</h4>
                        @if($user->attendances->count() > 0)
                            <div class="space-y-2">
                                @foreach($user->attendances->sortByDesc('date')->take(10) as $attendance)
                                    <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-slate-700/50">
                                        <div>
                                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $attendance->date?->format('d/m/Y') }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $attendance->classroom->name ?? '-' }}</p>
                                        </div>
                                        <span class="inline-flex px-2 py-1 text-xs rounded-full
                                            {{ match($attendance->status) {
                                                'present' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200',
                                                'late' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
                                                'excused' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                                default => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                            } }}">{{ $attendance->status_label }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500 dark:text-gray-400 italic">{{ __('Aucune donnée de présence.') }}</p>
                        @endif
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-100 dark:border-slate-700">
                        <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">⚠️ {{ __('Mes Sanctions') }}</h4>
                        @if($user->sanctions->count() > 0)
                            <div class="space-y-2">
                                @foreach($user->sanctions->sortByDesc('date_incident')->take(10) as $sanction)
                                    <div class="flex flex-col p-3 rounded-lg bg-gray-50 dark:bg-slate-700/50">
                                        <div class="flex justify-between items-center">
                                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $sanction->type_label }}</span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $sanction->date_incident?->format('d/m/Y') }}</span>
                                        </div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $sanction->description ?? '-' }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500 dark:text-gray-400 italic">{{ __('Aucune sanction enregistrée.') }}</p>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>