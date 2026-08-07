@props(['activeRoute' => null])

<div x-data="{ sidebarOpen: false }" class="flex h-screen bg-gray-100 dark:bg-slate-900">
    <!-- Mobile sidebar backdrop -->
    <div x-show="sidebarOpen"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 lg:hidden"
         @click="sidebarOpen = false"></div>

    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-slate-800 shadow-lg lg:static lg:inset-0 lg:block hidden flex flex-col"
         :class="{ 'block': sidebarOpen, 'hidden': !sidebarOpen }"
         @click.away="sidebarOpen = false">

        <!-- Fixed top: logo -->
        <div class="flex items-center justify-center h-16 bg-indigo-600 dark:bg-indigo-700 flex-shrink-0">
            <a href="{{ route('dashboard') }}" class="flex items-center space-x-2">
                <x-application-logo class="block h-8 w-auto fill-current text-white" />
                <span class="text-white font-bold text-xl">EduManager</span>
            </a>
        </div>

        <!-- Scrollable navigation -->
        <nav id="sidebar-nav" class="mt-2 px-2 pb-4 space-y-1 flex-1 overflow-y-auto overflow-x-hidden">
            @auth
                <x-sidebar.menu :items="config('sidebar.items')" />
            @endauth
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
                        @can('voir-notifications')
                        <div x-data="notificationBell()" x-init="init()" class="relative mr-2" @click.away="open = false">
                            <button @click="open = !open" class="relative p-2 rounded-full text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-slate-700 transition-colors duration-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                <span x-show="count > 0" x-text="count" x-cloak class="absolute top-0 right-0 inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold leading-none text-white transform translate-x-1/4 -translate-y-1/4 bg-red-600 rounded-full min-w-[1.25rem]"></span>
                            </button>

                            <div x-show="open" x-cloak class="absolute right-0 mt-2 w-80 bg-white dark:bg-slate-800 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50" x-transition>
                                <div class="p-3 border-b border-gray-100 dark:border-slate-700 font-semibold text-sm text-gray-800 dark:text-gray-200">Notifications</div>
                                <div class="max-h-64 overflow-y-auto">
                                    <template x-for="n in notifications" :key="n.id">
                                        <a :href="n.url" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700 border-b border-gray-100 dark:border-slate-700">
                                            <div class="flex items-center gap-2">
                                                <span class="w-2 h-2 rounded-full"
                                                    :class="{ 'bg-red-500': n.type === 'urgent', 'bg-amber-500': n.type === 'important', 'bg-blue-500': true }"></span>
                                                <span class="text-sm font-medium text-gray-900 dark:text-white truncate" x-text="n.title"></span>
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1" x-text="n.created_at"></div>
                                        </a>
                                    </template>
                                    <div x-show="notifications.length === 0" class="px-4 py-6 text-sm text-gray-500 dark:text-gray-400 text-center">Aucune notification non lue.</div>
                                </div>
                                <a href="{{ route('notifications.index') }}" class="block px-4 py-2 text-center text-sm text-indigo-600 dark:text-indigo-400 bg-gray-50 dark:bg-slate-700/50 rounded-b-md hover:underline">Voir tout</a>
                            </div>

                            <script>
                                function notificationBell() {
                                    return {
                                        open: false,
                                        count: 0,
                                        notifications: [],
                                        init() {
                                            this.fetch();
                                            setInterval(() => this.fetch(), 60000);
                                        },
                                        fetch() {
                                            fetch('{{ route('notifications.unread') }}')
                                                .then(r => r.ok ? r.json() : { count: 0, notifications: [] })
                                                .then(data => { this.count = data.count || 0; this.notifications = data.notifications || []; })
                                                .catch(() => { this.count = 0; this.notifications = []; });
                                        }
                                    }
                                }
                            </script>
                        </div>
                        @endcan

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
