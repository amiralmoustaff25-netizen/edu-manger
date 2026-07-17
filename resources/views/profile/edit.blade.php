<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center text-sm text-gray-500 dark:text-gray-400 mb-2">
            <a href="{{ route('dashboard') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">Accueil</a>
            <span class="mx-2">></span>
            <a href="{{ route('profile.show') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">Mon Profil</a>
            <span class="mx-2">></span>
            <span class="text-gray-700 dark:text-gray-200">Modifier</span>
        </div>
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100">
            {{ __('Modifier mon profil') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Bouton retour -->
            <div class="flex justify-start mb-6">
                <a href="{{ route('profile.show') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    {{ __('Retour au profil') }}
                </a>
            </div>

            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                @csrf
                @method('patch')

                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm overflow-hidden">
                    <div class="p-8">
                        
                        <!-- En-tête avec photo -->
                        <div class="flex flex-col md:flex-row items-start md:items-center gap-6 mb-8">
                            <div class="relative">
                                <img src="{{ $user->profile_photo_url }}" 
                                     alt="{{ $user->name }}" 
                                     class="w-32 h-32 rounded-full object-cover shadow-lg">
                            </div>
                            
                            <div class="flex-1">
                                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $user->name }}</h3>
                                <p class="text-indigo-600 dark:text-indigo-400 font-mono text-lg mt-1">{{ $user->matricule }}</p>
                                <span class="inline-block mt-2 px-3 py-1 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 rounded-full text-sm font-medium">
                                    {{ ucfirst($user->role) }}
                                </span>
                                
                                <!-- Champ photo -->
                                <div class="mt-4">
                                    <label for="profile_photo" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        {{ __('Photo de profil') }}
                                    </label>
                                    <input type="file" id="profile_photo" name="profile_photo" accept="image/*"
                                           class="w-full text-sm text-gray-500 dark:text-gray-400
                                                  file:mr-4 file:py-2 file:px-4
                                                  file:rounded-full file:border-0
                                                  file:text-sm file:font-semibold
                                                  file:bg-indigo-50 dark:file:bg-slate-700
                                                  file:text-indigo-700 dark:file:text-indigo-300
                                                  hover:file:bg-indigo-100 dark:hover:file:bg-slate-600">
                                    @error('profile_photo')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Formulaire -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            
                            <!-- Nom -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ __('Nom complet') }} <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                                       class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ __('Adresse e-mail') }}
                                </label>
                                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}"
                                       class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                                @error('email')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Matricule (lecture seule) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ __('Matricule') }}
                                </label>
                                <input type="text" value="{{ $user->matricule }}" disabled
                                       class="w-full rounded-lg border-gray-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-gray-500 dark:text-gray-400 cursor-not-allowed">
                            </div>

                            <!-- Classe (lecture seule) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ __('Classe') }}
                                </label>
                                <input type="text" value="{{ $classroom->name ?? __('Non assignée') }}" disabled
                                       class="w-full rounded-lg border-gray-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-gray-500 dark:text-gray-400 cursor-not-allowed">
                            </div>

                        </div>

                        <!-- Informations complémentaires (lecture seule) -->
                        @if($registration)
                        <div class="border-t border-gray-200 dark:border-slate-700 pt-6 mb-8">
                            <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                                {{ __('Informations complémentaires') }}
                            </h4>
                            <div class="bg-gray-800 dark:bg-slate-700 rounded-lg p-4 flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <span class="text-white">
                                        {{ __('Inscrit en') }} {{ $classroom->name ?? 'N/A' }} 
                                        / {{ $schoolYear->year_string ?? 'N/A' }} 
                                        / {{ $registration->status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Boutons -->
                        <div class="flex justify-end gap-3 pt-6 border-t border-gray-200 dark:border-slate-700">
                            <a href="{{ route('profile.show') }}"
                               class="px-6 py-2 border border-gray-300 dark:border-slate-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition">
                                {{ __('Annuler') }}
                            </a>
                            <button type="submit"
                                    class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition">
                                {{ __('Enregistrer les modifications') }}
                            </button>
                        </div>

                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>