<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nouvelle inscription</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                @if ($errors->any())
                    <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form action="{{ route('registrations.store') }}" method="POST" class="space-y-5">
                    @csrf

                    <div>
                        <label for="user_id" class="block text-sm font-medium text-gray-700">Élève</label>
                        <select id="user_id" name="user_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($students as $student)
                                <option value="{{ $student->id }}" @selected(old('user_id') == $student->id)>
                                    {{ $student->matricule }} - {{ $student->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="classroom_id" class="block text-sm font-medium text-gray-700">Classe</label>
                        <select id="classroom_id" name="classroom_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($classrooms as $classroom)
                                <option value="{{ $classroom->id }}" @selected(old('classroom_id') == $classroom->id)>{{ $classroom->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="registration_fee_paid" class="block text-sm font-medium text-gray-700">Frais d'inscription payés</label>
                            <input id="registration_fee_paid" type="number" name="registration_fee_paid" value="{{ old('registration_fee_paid') }}" step="0.01" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label for="monthly_fee" class="block text-sm font-medium text-gray-700">Scolarité mensuelle</label>
                            <input id="monthly_fee" type="number" name="monthly_fee" value="{{ old('monthly_fee') }}" step="0.01" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div class="rounded-md bg-indigo-50 p-4 text-sm text-indigo-800">
                        Année scolaire active : <strong>{{ $activeYear->year_string }}</strong>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                            Enregistrer l'inscription
                        </button>
                        <a href="{{ route('students.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
