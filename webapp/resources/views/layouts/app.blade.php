<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="portal-body antialiased">
        <div x-data="{ sidebarOpen: false, sidebarCollapsed: false, profileOpen: false }" class="portal-shell min-h-screen w-screen overflow-x-hidden">
            @include('layouts.navigation')

            <div class="pt-16">
                <div
                    x-show="sidebarOpen"
                    x-transition.opacity
                    @click="sidebarOpen = false"
                    class="fixed inset-0 z-30 bg-slate-950/40 lg:hidden"
                    x-cloak
                ></div>

                @include('layouts.sidebar')

                <div
                    class="portal-content ml-0 w-full min-w-0 overflow-x-hidden transition-all duration-300"
                    :class="sidebarCollapsed ? 'lg:ml-20 lg:w-[calc(100%-5rem)]' : 'lg:ml-72 lg:w-[calc(100%-18rem)]'"
                >
                    @isset($header)
                        <header class="portal-page-header">
                            <div class="w-full px-4 py-5 sm:px-6 lg:px-8">
                                {{ $header }}
                            </div>
                        </header>
                    @endisset

                    <main class="pb-8">
                        <div class="w-full min-w-0 px-4 pt-6 sm:px-6 lg:px-8">
                            {{ $slot }}
                        </div>
                    </main>
                </div>
            </div>
        </div>
    </body>
</html>
