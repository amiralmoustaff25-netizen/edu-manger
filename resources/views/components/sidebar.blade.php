@props(['activeRoute' => null])

<div x-data="{ sidebarOpen: false }" class="flex h-screen bg-gray-100 dark:bg-slate-900">
    <!-- Mobile sidebar backdrop -->
    <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 lg:hidden" @click="sidebarOpen = false"></div>

    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-slate-800 shadow-lg lg:static lg:inset-0 lg:block hidden" :class="{ 'block': sidebarOpen, 'hidden': !sidebarOpen }" @click.away="sidebarOpen = false">
        
        <!-- Logo -->
        <div class="flex items-center justify-center h-16 bg-indigo-600 dark:bg-indigo-700">
            <a href="{{ route('dashboard') }}" class="flex items-center space-x-2">
                <x-application-logo class="block h-8 w-auto fill-current text-white" />
                <span class="text-white font-bold text-xl">EduManager</span>
            </a>
        </div>

        <!-- Navigation -->
        <nav class="mt-5 px-2 space-y-1">
            
            <!-- Accueil -->
            @role('eleve')
                <a href="{{ route('student.dashboard') }}" class="{{ request()->routeIs('student.dashboard') ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-300' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 hover:text-gray-900 dark:hover:text-gray-100' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                    <svg class="mr-3 flex-shrink-0 h-6 w-6 {{ request()->routeIs('student.dashboard') ? 'text-indigo-500 dark:text-indigo-400' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    {{ __('Accueil') }}
                </a>
            @else
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-300' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 hover:text-gray-900 dark:hover:text-gray-100' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                    <svg class="mr-3 flex-shrink-0 h-6 w-6 {{ request()->routeIs('dashboard') ? 'text-indigo-500 dark:text-indigo-400' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    {{ __('Accueil') }}
                </a>
            @endrole

            @role('eleve')
            <!-- Mon Dossier -->
            <div class="mt-6">
                <p class="px-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Mon Dossier') }}</p>
                <div class="mt-2 space-y-1">
                    <a href="{{ route('profile.show') }}" class="{{ request()->routeIs('profile.*') ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-300' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 hover:text-gray-900 dark:hover:text-gray-100' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <svg class="mr-3 flex-shrink-0 h-6 w-6 {{ request()->routeIs('profile.*') ? 'text-indigo-500 dark:text-indigo-400' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        {{ __('Mon Profil') }}
                    </a>
                </div>
            </div>

            <!-- Vie Scolaire -->
            <div class="mt-6">
                <p class="px-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Vie Scolaire') }}</p>
                <div class="mt-2 space-y-1">
                    <a href="#" class="text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 hover:text-gray-900 dark:hover:text-gray-100 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <svg class="mr-3 flex-shrink-0 h-6 w-6 text-gray-400 group-hover:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {{ __('Emploi du temps') }}
                    </a>
                    <a href="#" class="text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 hover:text-gray-900 dark:hover:text-gray-100 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <svg class="mr-3 flex-shrink-0 h-6 w-6 text-gray-400 group-hover:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        {{ __('Cahier de texte') }}
                    </a>
                </div>
            </div>

            <!-- Suivi Pédagogique -->
            <div class="mt-6">
                <p class="px-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Suivi Pédagogique') }}</p>
                <div class="mt-2 space-y-1">
                    <a href="#" class="text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 hover:text-gray-900 dark:hover:text-gray-100 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <svg class="mr-3 flex-shrink-0 h-6 w-6 text-gray-400 group-hover:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        {{ __('Mes Notes') }}
                    </a>
                    <a href="#" class="text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 hover:text-gray-900 dark:hover:text-gray-100 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <svg class="mr-3 flex-shrink-0 h-6 w-6 text-gray-400 group-hover:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        {{ __('Bulletins') }}
                    </a>
                </div>
            </div>
            @endrole

            @role('super-admin|admin')
            <!-- Gestion -->
            <div class="mt-6">
                <p class="px-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Gestion') }}</p>
                <div class="mt-2 space-y-1">
                    <a href="{{ route('classrooms.index') }}" class="{{ request()->routeIs('classrooms.*') ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-300' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 hover:text-gray-900 dark:hover:text-gray-100' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <svg class="mr-3 flex-shrink-0 h-6 w-6 {{ request()->routeIs('classrooms.*') ? 'text-indigo-500 dark:text-indigo-400' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        {{ __('Classes') }}
                    </a>
                    <a href="{{ route('students.index') }}" class="{{ request()->routeIs('students.*') ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-300' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 hover:text-gray-900 dark:hover:text-gray-100' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <svg class="mr-3 flex-shrink-0 h-6 w-6 {{ request()->routeIs('students.*') ? 'text-indigo-500 dark:text-indigo-400' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        {{ __('Élèves') }}
                    </a>
                    <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-300' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 hover:text-gray-900 dark:hover:text-gray-100' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <svg class="mr-3 flex-shrink-0 h-6 w-6 {{ request()->routeIs('users.*') ? 'text-indigo-500 dark:text-indigo-400' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        {{ __('Utilisateurs') }}
                    </a>
                    @can('voir-professeurs')
                        <a href="{{ route('teachers.index') }}" class="{{ request()->routeIs('teachers.*') ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-300' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 hover:text-gray-900 dark:hover:text-gray-100' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                            <svg class="mr-3 flex-shrink-0 h-6 w-6 {{ request()->routeIs('teachers.*') ? 'text-indigo-500 dark:text-indigo-400' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            {{ __('Professeurs') }}
                        </a>
                    @endcan
                    <a href="{{ route('login-logs.index') }}" class="{{ request()->routeIs('login-logs.*') ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-300' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 hover:text-gray-900 dark:hover:text-gray-100' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <svg class="mr-3 flex-shrink-0 h-6 w-6 {{ request()->routeIs('login-logs.*') ? 'text-indigo-500 dark:text-indigo-400' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        {{ __('Logs Connexion') }}
                    </a>
                </div>
            </div>
            @endrole

        </nav>
    </div>

    <!-- Main content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top bar -->
        <div class="bg-white dark:bg-slate-800 shadow">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <button @click="sidebarOpen = !sidebarOpen" class="px-4 border-r border-gray-200 dark:border-slate-700 text-gray-500 dark:text-gray-400 focus:outline-none focus:text-gray-700 dark:focus:text-gray-300 lg:hidden">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex items-center">
                        <!-- Dark Mode Toggle -->
                        <button @click="dark = !dark" class="p-2 rounded-full text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-slate-700 transition-colors duration-200 mr-4">
                            <svg x-show="!dark" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                            </svg>
                            <svg x-show="dark" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </button>

                        <!-- User dropdown -->
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white dark:bg-slate-700 dark:text-gray-300 hover:text-gray-700 dark:hover:text-gray-100 focus:outline-none transition ease-in-out duration-150">
                                    <div>{{ Auth::user()->name }}</div>
                                    <div class="ms-1">
                                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <x-dropdown-link :href="route('profile.show')">
                                    {{ __('Mon Profil') }}
                                </x-dropdown-link>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-dropdown-link :href="route('logout')"
                                            onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                        {{ __('Se Déconnecter') }}
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page content -->
        <main class="flex-1 overflow-auto">
            @isset($header)
                <header class="bg-white dark:bg-slate-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            @if (session('success'))
                <div x-data="{ show: true }"
                    x-init="setTimeout(() => show = false, 3000)"
                    x-show="show"
                    x-transition:leave="transition ease-in duration-500"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="max-w-7xl mx-auto mt-4 px-4 sm:px-6 lg:px-8">
                    <div class="bg-green-100 dark:bg-green-900/50 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-200 px-4 py-3 rounded">
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>
</div>