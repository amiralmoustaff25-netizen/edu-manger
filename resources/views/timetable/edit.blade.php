<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ __('Emploi du temps') }} — {{ $classroom->name }}
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $classroom->schoolYear?->year_string }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('timetable.print', $classroom) }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700">
                    {{ __('Imprimer / PDF') }}
                </a>
                <a href="{{ route('timetable.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700">
                    {{ __('Retour à la liste') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="rounded-md border border-green-200 dark:border-green-900 bg-green-50 dark:bg-green-900/20 p-4 text-sm text-green-800 dark:text-green-200">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-md border border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-900/20 p-4 text-sm text-red-800 dark:text-red-200">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="rounded-lg bg-white dark:bg-slate-800 p-6 shadow-sm">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-3">{{ __('Import / export Excel') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Téléchargez le modèle (déjà rempli avec les cases saisies), complétez-le hors-ligne en respectant la mise en page, puis réimportez-le.') }}</p>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('timetable.template', $classroom) }}" class="inline-flex items-center justify-center rounded-md bg-white dark:bg-slate-900 border border-gray-300 dark:border-slate-600 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700">
                        {{ __('Télécharger le modèle Excel') }}
                    </a>
                    <form method="POST" action="{{ route('timetable.import', $classroom) }}" enctype="multipart/form-data" class="flex items-center gap-2">
                        @csrf
                        <input type="file" name="file" accept=".xlsx,.xls" required class="text-sm text-gray-600 dark:text-gray-300">
                        <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">{{ __('Importer') }}</button>
                    </form>
                </div>
            </div>

            <form method="POST" action="{{ route('timetable.update', $classroom) }}">
                @csrf
                @method('PUT')
                <div class="overflow-x-auto rounded-lg bg-white dark:bg-slate-800 shadow-sm">
                    <table class="min-w-full border-collapse text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-slate-700">
                                <th class="border border-gray-200 dark:border-slate-600 px-3 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">{{ __('Horaire') }}</th>
                                @foreach ($days as $day)
                                    <th class="border border-gray-200 dark:border-slate-600 px-3 py-3 text-center font-semibold text-gray-600 dark:text-gray-300">{{ $day }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($slots as $slot)
                                <tr>
                                    <th class="border border-gray-200 dark:border-slate-600 px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200 whitespace-nowrap bg-gray-50 dark:bg-slate-700">{{ $slot }}</th>
                                    @foreach ($days as $day)
                                        <td class="border border-gray-200 dark:border-slate-600 p-1">
                                            <input type="text" name="content[{{ $day }}][{{ $slot }}]" value="{{ old('content.'.$day.'.'.$slot, $entries[$day][$slot] ?? '') }}"
                                                class="w-full min-w-[110px] rounded-md border-0 bg-transparent px-2 py-2 text-sm text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500"
                                                placeholder="—">
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex items-center gap-3">
                    <button class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">{{ __('Enregistrer') }}</button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
