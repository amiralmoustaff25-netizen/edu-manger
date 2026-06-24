<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h2 class="text-2xl font-bold mb-4">Profil de {{ auth()->user()->name }}</h2>
                
                <div class="space-y-4">
                    <p><strong>Nom :</strong> {{ auth()->user()->name }}</p>
                    <p><strong>Email :</strong> {{ auth()->user()->email }}</p>
                    <p><strong>Matricule :</strong> {{ auth()->user()->matricule }}</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>