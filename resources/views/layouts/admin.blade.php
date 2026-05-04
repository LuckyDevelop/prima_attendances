<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>
        <style>
            [x-cloak] {
                display: none !important;
            }
        </style>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        @stack('styles')
    </head>

    <body class="bg-gray-50 font-sans antialiased">

        <div class="flex h-screen overflow-hidden" x-data="{
            sidebarOpen: JSON.parse(localStorage.getItem('prima_sb') ?? 'true'),
            mobileOpen: false,
            init() {
                this.$watch('sidebarOpen', val => localStorage.setItem('prima_sb', JSON.stringify(val)));
            }
        }" x-cloak>

            {{-- Mobile backdrop --}}
            <div x-show="mobileOpen" x-transition:enter="transition-opacity duration-200"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity duration-200" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0" @click="mobileOpen = false"
                class="fixed inset-0 z-20 bg-black/50 lg:hidden"></div>

            {{-- Sidebar --}}
            <x-admin.sidebar />

            {{-- Main content area --}}
            <div class="flex flex-1 flex-col overflow-hidden min-w-0">
                <x-admin.navbar />

                <main class="flex-1 overflow-y-auto">
                    <div class="px-4 py-6 sm:px-6 lg:px-8">
                        @isset($slot)
                            {{ $slot }}
                        @endisset
                        @yield('content')
                    </div>
                </main>
            </div>
        </div>

        {{-- Global toast notifications --}}
        <x-ui.toast />

        @livewireScripts
        @stack('scripts')
    </body>

</html>
