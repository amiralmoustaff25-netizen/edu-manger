<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            Génération des Bulletins Scolaires
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm overflow-hidden">
                <div class="p-8">
                    
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            Sélectionner une classe pour générer les bulletins
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($classrooms as $classroom)
                            <div class="border border-gray-200 dark:border-slate-700 rounded-lg p-4 hover:shadow-md transition">
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">{{ $classroom->name }}</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                    {{ $classroom->schoolYear->year_string ?? 'N/A' }}
                                </p>
                                
                                <div class="flex gap-2">
                                    <form action="{{ route('bulletins.class-pdf', [$classroom, 'trimestre_1']) }}" method="GET" data-no-pjax class="flex-1">
                                        <button type="submit" class="w-full px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg transition">
                                            T1 PDF
                                        </button>
                                    </form>
                                    <form action="{{ route('bulletins.class-pdf', [$classroom, 'trimestre_2']) }}" method="GET" data-no-pjax class="flex-1">
                                        <button type="submit" class="w-full px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg transition">
                                            T2 PDF
                                        </button>
                                    </form>
                                    <form action="{{ route('bulletins.class-pdf', [$classroom, 'trimestre_3']) }}" method="GET" data-no-pjax class="flex-1">
                                        <button type="submit" class="w-full px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg transition">
                                            T3 PDF
                                        </button>
                                    </form>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <script>
        document.querySelectorAll('form[data-no-pjax]').forEach(form => {
            form.addEventListener('submit', function () {
                const button = form.querySelector('button[type="submit"]');
                if (button) {
                    button.disabled = true;
                    button.textContent = 'Génération...';
                }
            });
        });
    </script>
</x-app-layout>
