<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Heim') }} | Sign In</title>
        <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
        <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            [x-cloak] { display: none !important; }
            body { font-family: 'Manrope', sans-serif; }
        </style>
    </head>
    <body class="min-h-full font-sans text-gray-900 antialiased bg-slate-50 relative selection:bg-heim-600 selection:text-white">
        <div class="relative min-h-screen flex flex-col items-center justify-center px-4 py-8 sm:px-6 overflow-hidden">
            {{-- Ambient background decorations --}}
            <div class="pointer-events-none absolute -top-36 -right-36 h-96 w-96 rounded-full bg-heim-200/45 blur-3xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-36 -left-36 h-96 w-96 rounded-full bg-amber-100/50 blur-3xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 h-[28rem] w-[28rem] rounded-full bg-heim-100/30 blur-3xl" aria-hidden="true"></div>

            {{-- Background Logo Watermark --}}
            <div class="pointer-events-none absolute inset-0 flex items-center justify-center overflow-hidden z-0 select-none" aria-hidden="true">
                <img src="{{ asset('images/logo.png') }}" alt="" class="w-[50rem] max-w-[95vw] opacity-[0.055] select-none filter blur-[0.2px] transform -rotate-6 scale-105 pointer-events-none">
            </div>

            <div class="relative z-10 w-full max-w-md sm:max-w-lg">
                {{-- Brand Header / Logo --}}
                <div class="flex flex-col items-center text-center mb-6">
                    <a href="{{ url('/') }}" class="group flex flex-col items-center focus:outline-none focus:ring-2 focus:ring-heim-500 rounded-2xl p-1 transition duration-200 hover:-translate-y-0.5">
                        <div class="flex h-20 w-48 items-center justify-center rounded-2xl bg-white/95 px-5 py-3 shadow-md shadow-heim-900/5 ring-1 ring-gray-200/70 transition duration-200 group-hover:shadow-lg group-hover:ring-heim-300">
                            <img src="{{ asset('images/logo.png') }}" alt="Heim" class="h-full w-full object-contain">
                        </div>
                        <div class="mt-3 flex items-center gap-1.5 px-3 py-1 rounded-full bg-heim-100/80 border border-heim-200/80 text-[10px] font-extrabold uppercase tracking-[0.2em] text-heim-800 shadow-2xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-heim-600 animate-pulse"></span>
                            <span>Coffee Shop Operations</span>
                        </div>
                    </a>
                </div>

                {{-- Main Card Container --}}
                <div class="overflow-hidden rounded-3xl bg-white/95 p-6 sm:p-8 shadow-2xl shadow-heim-950/10 ring-1 ring-gray-200/80 backdrop-blur-md">
                    {{ $slot }}
                </div>

                {{-- Footer --}}
                <div class="mt-8 text-center text-xs text-gray-500">
                    <div class="flex items-center justify-center gap-2 font-medium">
                        <span>Heim POS</span>
                        <span class="text-gray-300">&bull;</span>
                        <span class="inline-flex items-center gap-1.5 text-emerald-700 font-semibold">
                            <span class="h-2 w-2 rounded-full bg-emerald-500 ring-2 ring-emerald-200 animate-pulse"></span>
                            Terminal Active
                        </span>
                    </div>
                    <p class="mt-1.5 text-gray-400">&copy; {{ date('Y') }} Heim Coffee Shop System. All rights reserved.</p>
                </div>
            </div>
        </div>
    </body>
</html>
