
@csrf

@php
    $studentNom = $student->name ? explode(' ', $student->name)[0] ?? '';
    $studentPrenom = $student->name ? implode(' ', array_slice(explode(' ', $student->name), 1)) ?? '';
@endphp

<div x-data='@json([
    "photoPreview" => $student->profile_photo_url,
    "showDelete" => (bool) $student->profile_photo_path,
])' class="grid gap-6 lg:grid-cols-2">
    @can('upload-photo-eleve')
    <!-- Photo Upload Section -->
    <div class="lg:col-span-2 flex flex-col items-center">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Photo de profil</label>
        <div class="flex flex-col items-center gap-4">
            <img :src="photoPreview" alt="Preview" class="rounded-full object-cover border-2 border-gray-300 dark:border-slate-600" style="width: 150px; height: 150px;">
            <div class="flex flex-col gap-2">
                <input @change="
                    const file = $event.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = (e) => { photoPreview = e.target.result; showDelete = false; };
                        reader.readAsDataURL(file);
                    }
                " type="file" id="photo" name="photo" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-slate-800 dark:file:text-indigo-400 dark:hover:file:bg-slate-700">
                <x-input-error :messages="$errors->get('photo')" class="mt-1" />
                @if($student->exists && $student->profile_photo_path)
                    <div x-show="showDelete" class="flex items-center gap-2">
                        <input type="checkbox" id="supprimer_photo" name="supprimer_photo" value="1" @checked(old('supprimer_photo')) class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500">
                        <label for="supprimer_photo" class="text-sm text-gray-600 dark:text-gray-400">Supprimer la photo</label>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @else
    @if($student->profile_photo_path)
    <div class="lg:col-span-2 flex flex-col items-center">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Photo de profil</label>
        <img :src="photoPreview" alt="Preview" class="rounded-full object-cover border-2 border-gray-300 dark:border-slate-600" style="width: 150px; height: 150px;">
    </div>
    @endif
    @endcan

    <div>
        <label for="nom" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nom</label>
        <input id="nom" name="nom" type="text" value="{{ old('nom', $studentNom) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <x-input-error :messages="$errors->get('nom')" class="mt-2" />
    </div>

    <div>
        <label for="prenom" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Prénom</label>
        <input id="prenom" name="prenom" type="text" value="{{ old('prenom', $studentPrenom) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <x-input-error :messages="$errors->get('prenom')" class="mt-2" />
    </div>

    <div class="lg:col-span-2">
        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', $student->email) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    <div>
        <label for="date_naissance" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date de naissance</label>
        <input id="date_naissance" name="date_naissance" type="date" value="{{ old('date_naissance', $student->date_naissance ? $student->date_naissance->format('Y-m-d') : '') }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <x-input-error :messages="$errors->get('date_naissance')" class="mt-2" />
    </div>

    <div>
        <label for="lieu_naissance" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Lieu de naissance</label>
        <input id="lieu_naissance" name="lieu_naissance" type="text" value="{{ old('lieu_naissance', $student->lieu_naissance) }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <x-input-error :messages="$errors->get('lieu_naissance')" class="mt-2" />
    </div>

    <div>
        <label for="sexe" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sexe</label>
        <select id="sexe" name="sexe" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Sélectionner</option>
            <option value="M" @selected(old('sexe', $student->sexe) === 'M')>Masculin</option>
            <option value="F" @selected(old('sexe', $student->sexe) === 'F')>Féminin</option>
        </select>
        <x-input-error :messages="$errors->get('sexe')" class="mt-2" />
    </div>

    <div>
        <label for="nationalite" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nationalité</label>
        <input id="nationalite" name="nationalite" type="text" value="{{ old('nationalite', $student->nationalite) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <x-input-error :messages="$errors->get('nationalite')" class="mt-2" />
    </div>

    <div>
        <label for="telephone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Téléphone</label>
        <input id="telephone" name="telephone" type="text" value="{{ old('telephone', $student->telephone) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <x-input-error :messages="$errors->get('telephone')" class="mt-2" />
    </div>

    <div>
        <label for="cycle" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cycle</label>
        <select id="cycle" name="cycle" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Sélectionner</option>
            <option value="primaire" @selected(old('cycle', $student->cycle) === 'primaire')>Primaire</option>
            <option value="college" @selected(old('cycle', $student->cycle) === 'college')>Collège</option>
            <option value="lycee" @selected(old('cycle', $student->cycle) === 'lycee')>Lycée</option>
        </select>
        <x-input-error :messages="$errors->get('cycle')" class="mt-2" />
    </div>

    <div>
        <label for="classroom_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Classe</label>
        <select id="classroom_id" name="classroom_id" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Sélectionner</option>
            @foreach($classrooms as $classroom)
                <option value="{{ $classroom->id }}" @selected(old('classroom_id', $student->latestRegistration?->classroom_id) === $classroom->id)>{{ $classroom->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('classroom_id')" class="mt-2" />
    </div>

    <div class="lg:col-span-2">
        <label for="adresse" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Adresse</label>
        <textarea id="adresse" name="adresse" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('adresse', $student->adresse) }}</textarea>
        <x-input-error :messages="$errors->get('adresse')" class="mt-2" />
    </div>

    @if($parents->count() > 0)
        <div class="lg:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Parents associés</label>
            <div class="mt-2 space-y-3">
                @for($i = 0; $i < 2; $i++)
                    @php
                        $parent = $student->parents->get($i);
                    @endphp
                    <div class="grid gap-3 md:grid-cols-4 border border-gray-200 dark:border-slate-700 rounded-md p-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Parent</label>
                            <select name="parents[{{ $i }}][parent_id]" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Aucun</option>
                                @foreach($parents as $p)
                                    <option value="{{ $p->id }}" @selected(old("parents.{$i}.parent_id", optional($parent)->id === $p->id)>{{ $p->nom }} {{ $p->prenom }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Lien de parenté</label>
                            <input name="parents[{{ $i }}][lien_parente]" type="text" value="{{ old("parents.{$i}.lien_parente", optional($parent->pivot)->lien_parente) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div class="flex items-end gap-2">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="parents[{{ $i }}][est_responsable_financier]" value="1" @checked(old("parents.{$i}.est_responsable_financier", optional($parent->pivot)->est_responsable_financier)) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">Resp. financier</span>
                            </label>
                        </div>
                        <div class="flex items-end gap-2">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="parents[{{ $i }}][est_contact_urgence]" value="1" @checked(old("parents.{$i}.est_contact_urgence", optional($parent->pivot)->est_contact_urgence)) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">Contact urgence</span>
                            </label>
                        </div>
                    </div>
                @endfor
            </div>
        </div>
    @endif
</div>

<div class="mt-6 flex items-center gap-3 border-t border-gray-200 dark:border-slate-700 pt-4">
    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
        {{ $student->exists ? 'Enregistrer les modifications' : 'Créer l\'élève' }}
    </button>
    <a href="{{ $student->exists ? route('students.show', $student) : route('students.index') }}" class="rounded-md border border-gray-300 dark:border-slate-600 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700">Annuler</a>
</div>
