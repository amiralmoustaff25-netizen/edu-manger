<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Nouvelle inscription</h2>
            <a href="{{ route('registrations.reinscription') }}" class="inline-flex items-center justify-center rounded-md bg-gray-200 dark:bg-slate-700 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-300 dark:hover:bg-slate-600">
                Réinscription
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                @if ($errors->any())
                    <div class="mb-5 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900 p-4 text-sm text-red-700 dark:text-red-200">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form action="{{ route('registrations.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                    @include('students._form', ['enrollment' => true, 'feeLibrary' => $feeLibrary])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
