@csrf

@php
    $teacherUserName = optional($teacher->user)->name;
@endphp

<div class="grid gap-6 lg:grid-cols-2">
    <div>
        <label for="nom" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nom</label>
        <input id="nom" name="nom" type="text" value="{{ old('nom', $teacherUserName ? explode(' ', $teacherUserName)[0] : '') }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <x-input-error :messages="$errors->get('nom')" class="mt-2" />
    </div>

    <div>
        <label for="prenom" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Prénom</label>
        <input id="prenom" name="prenom" type="text" value="{{ old('prenom', $teacherUserName ? implode(' ', array_slice(explode(' ', $teacherUserName), 1)) : '') }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <x-input-error :messages="$errors->get('prenom')" class="mt-2" />
    </div>

    <div class="lg:col-span-2">
        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', optional($teacher->user)->email ?? '') }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    <div>
        <label for="date_naissance" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date de naissance</label>
        <input id="date_naissance" name="date_naissance" type="date" value="{{ old('date_naissance', optional($teacher->date_naissance)->format('Y-m-d')) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <x-input-error :messages="$errors->get('date_naissance')" class="mt-2" />
    </div>

    <div>
        <label for="lieu_naissance" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Lieu de naissance</label>
        <input id="lieu_naissance" name="lieu_naissance" type="text" value="{{ old('lieu_naissance', $teacher->lieu_naissance) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <x-input-error :messages="$errors->get('lieu_naissance')" class="mt-2" />
    </div>

    <div>
        <label for="sexe" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sexe</label>
        <select id="sexe" name="sexe" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Sélectionner</option>
            <option value="masculin" @selected(old('sexe', $teacher->sexe) === 'masculin')>Masculin</option>
            <option value="feminin" @selected(old('sexe', $teacher->sexe) === 'feminin')>Féminin</option>
        </select>
        <x-input-error :messages="$errors->get('sexe')" class="mt-2" />
    </div>

    <div>
        <label for="nationalite" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nationalité</label>
        <input id="nationalite" name="nationalite" type="text" value="{{ old('nationalite', $teacher->nationalite) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <x-input-error :messages="$errors->get('nationalite')" class="mt-2" />
    </div>

    <div>
        <label for="telephone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Téléphone</label>
        <input id="telephone" name="telephone" type="text" value="{{ old('telephone', optional($teacher->user)->telephone ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <x-input-error :messages="$errors->get('telephone')" class="mt-2" />
    </div>

    <div>
        <label for="date_recrutement" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date de recrutement</label>
        <input id="date_recrutement" name="date_recrutement" type="date" value="{{ old('date_recrutement', optional($teacher->date_recrutement)->format('Y-m-d')) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <x-input-error :messages="$errors->get('date_recrutement')" class="mt-2" />
    </div>

    <div>
        <label for="statut" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Statut</label>
        <select id="statut" name="statut" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Sélectionner</option>
            <option value="fonctionnaire" @selected(old('statut', $teacher->statut) === 'fonctionnaire')>Fonctionnaire</option>
            <option value="contractuel" @selected(old('statut', $teacher->statut) === 'contractuel')>Contractuel</option>
            <option value="vacataire" @selected(old('statut', $teacher->statut) === 'vacataire')>Vacataire</option>
        </select>
        <x-input-error :messages="$errors->get('statut')" class="mt-2" />
    </div>

    <div class="lg:col-span-2">
        <label for="diplomes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Diplômes</label>
        <textarea id="diplomes" name="diplomes" rows="3" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('diplomes', $teacher->diplomes) }}</textarea>
        <x-input-error :messages="$errors->get('diplomes')" class="mt-2" />
    </div>

    <div class="lg:col-span-2">
        <label for="etablissements_formation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Établissement(s) de formation</label>
        <textarea id="etablissements_formation" name="etablissements_formation" rows="3" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('etablissements_formation', $teacher->etablissements_formation) }}</textarea>
        <x-input-error :messages="$errors->get('etablissements_formation')" class="mt-2" />
    </div>

    <div class="lg:col-span-2">
        <label for="specialites" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Spécialités (séparées par des virgules)</label>
        <input id="specialites" name="specialites" type="text" value="{{ is_array(old('specialites')) ? implode(', ', old('specialites')) : old('specialites', $teacher->specialites ? implode(', ', $teacher->specialites) : '') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <x-input-error :messages="$errors->get('specialites')" class="mt-2" />
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Entrez plusieurs spécialités séparées par des virgules.</p>
    </div>

    <div class="lg:col-span-2">
        <label for="filiation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Filiation</label>
        <textarea id="filiation" name="filiation" rows="3" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('filiation', $teacher->filiation) }}</textarea>
        <x-input-error :messages="$errors->get('filiation')" class="mt-2" />
    </div>

    <div class="lg:col-span-1">
        <label for="contact_urgence_nom" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contact urgence</label>
        <input id="contact_urgence_nom" name="contact_urgence_nom" type="text" value="{{ old('contact_urgence_nom', $teacher->contact_urgence_nom) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <x-input-error :messages="$errors->get('contact_urgence_nom')" class="mt-2" />
    </div>

    <div class="lg:col-span-1">
        <label for="contact_urgence_tel" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Téléphone urgence</label>
        <input id="contact_urgence_tel" name="contact_urgence_tel" type="text" value="{{ old('contact_urgence_tel', $teacher->contact_urgence_tel) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <x-input-error :messages="$errors->get('contact_urgence_tel')" class="mt-2" />
    </div>

    <div>
        <label for="rib" class="block text-sm font-medium text-gray-700 dark:text-gray-300">RIB</label>
        <input id="rib" name="rib" type="text" value="" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <x-input-error :messages="$errors->get('rib')" class="mt-2" />
        @if($teacher->exists && $canViewRib)
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Le RIB est masqué pour des raisons de sécurité.</p>
        @endif
    </div>

    <div>
        <label for="nombre_heures_semaine" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre d'heures/semaine</label>
        <input id="nombre_heures_semaine" name="nombre_heures_semaine" type="number" min="0" value="{{ old('nombre_heures_semaine', $teacher->nombre_heures_semaine ?? 0) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <x-input-error :messages="$errors->get('nombre_heures_semaine')" class="mt-2" />
    </div>

    <div class="lg:col-span-2">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Classes et volume horaire</label>
        <div class="space-y-4">
            @for($i = 0; $i < 2; $i++)
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Classe</label>
                        <select name="classrooms[{{ $i }}][classroom_id]" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Aucune</option>
                            @foreach($classrooms as $classroom)
                                <option value="{{ $classroom->id }}" @selected(old("classrooms.{$i}.classroom_id") == $classroom->id)>{{ $classroom->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Matière</label>
                        <select name="classrooms[{{ $i }}][matiere_id]" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Aucune</option>
                            @foreach($matieres as $matiere)
                                <option value="{{ $matiere->id }}" @selected(old("classrooms.{$i}.matiere_id") == $matiere->id)>{{ $matiere->name ?? 'Matière '.$matiere->id }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Volume horaire</label>
                        <input name="classrooms[{{ $i }}][volume_horaire_hebdo]" type="number" min="0" value="{{ old("classrooms.{$i}.volume_horaire_hebdo", 0) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
            @endfor
        </div>
    </div>
</div>

<div class="mt-6 flex items-center gap-3 border-t border-gray-200 dark:border-slate-700 pt-4">
    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
        {{ $teacher->exists ? 'Enregistrer les modifications' : 'Créer le professeur' }}
    </button>
    <a href="{{ route('teachers.index') }}" class="rounded-md border border-gray-300 dark:border-slate-600 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-700">Annuler</a>
</div>
