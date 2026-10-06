<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Heim</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        [x-cloak] { display: none !important; }
        body { font-family: 'Manrope', sans-serif; color: #17342d; }
        .sidebar-shell {
            background: linear-gradient(180deg, rgba(255,255,255,0.98) 0%, rgba(248,250,252,0.97) 100%);
            border-right: 1px solid rgba(148, 163, 184, 0.18);
            box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.02), 12px 0 30px -22px rgba(15, 23, 42, 0.35);
        }
        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.7rem 0.8rem;
            border-radius: 0.9rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #475569;
            text-decoration: none;
            transition: all 0.18s ease-out;
            position: relative;
            border: 1px solid transparent;
        }
        .nav-link:hover {
            background: rgba(21, 93, 73, 0.04);
            color: #0f172a;
            border-color: rgba(21, 93, 73, 0.08);
        }
        .nav-link.active {
            background: linear-gradient(135deg, #155d49 0%, #1d7a5b 100%);
            color: #ffffff;
            box-shadow: 0 8px 18px -12px rgba(21, 93, 73, 0.75);
            border-color: rgba(255,255,255,0.08);
        }
        .nav-link svg {
            width: 1rem;
            height: 1rem;
            stroke-width: 1.8;
            flex-shrink: 0;
            color: currentColor;
        }
        .nav-link.active svg {
            color: #ffffff;
        }
        .nav-link .notif-badge {
            margin-left: auto;
            display: inline-flex;
            min-width: 1.15rem;
            height: 1.15rem;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            background-color: #ef4444;
            padding: 0 0.35rem;
            font-size: 10px;
            font-weight: 700;
            color: #ffffff;
            line-height: 1;
        }
        .sidebar-section-label {
            padding: 0.8rem 0.75rem 0.45rem;
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #7c8a96;
        }
        .sidebar-scroll {
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, 0.5) transparent;
        }
        .sidebar-scroll::-webkit-scrollbar {
            width: 5px;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.45);
            border-radius: 9999px;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(100, 116, 139, 0.7);
        }
        .pos-main {
            display: flex;
            flex-direction: column;
            overflow: hidden;
            padding: 1rem;
        }
        .pos-main > .pos-terminal {
            flex: 1 1 0%;
            min-height: 0;
        }
        @media (min-width: 1280px) and (max-height: 950px) {
            .pos-order-panel {
                overflow-y: auto !important;
                overscroll-behavior: contain;
            }
            .pos-order-panel #cart-items {
                flex: 0 0 15rem;
                max-height: 32vh;
                min-height: 15rem;
            }
        }
        @media (max-width: 1279px) {
            .pos-shell {
                zoom: 100% !important;
            }
            .pos-main {
                overflow-y: auto;
                overscroll-behavior-y: contain;
            }
            .pos-main > .pos-terminal {
                flex: 0 0 auto;
                height: auto;
                min-height: 0;
                overflow: visible;
            }
            .pos-terminal > .grid {
                display: flex;
                flex: 0 0 auto;
                flex-direction: column;
                min-height: 0;
            }
            .pos-terminal > .grid > :first-child {
                flex: 0 0 auto;
                height: min(55vh, 34rem);
                min-height: 20rem;
            }
            .pos-terminal > .grid > .pos-order-panel {
                flex: 0 0 auto;
                height: auto;
                max-height: none;
                min-height: 0;
                overflow: visible;
            }
            .pos-order-panel > .sticky.bottom-0 {
                position: static;
            }
            .pos-order-panel #cart-items {
                flex: 0 0 15rem;
                max-height: 50vh;
                min-height: 12rem;
            }
        }
        .pos-main [class~="text-xs"],
        .pos-main [class~="text-[10px]"],
        .pos-main [class~="text-[11px]"] {
            font-size: 0.8125rem;
            line-height: 1.2rem;
        }
        .pos-main input:not([type="checkbox"]):not([type="radio"]),
        .pos-main select,
        .pos-main textarea {
            min-height: 2.5rem;
            font-size: 0.875rem;
        }
        .pos-main input.text-lg {
            font-size: 1.125rem;
        }
        .pos-main button:not(.cat-btn) {
            min-height: 2.5rem;
        }
        @media print {
            html, body {
                height: auto !important;
                overflow: visible !important;
                background: #ffffff !important;
                color: #0f172a !important;
            }
            .sidebar-shell,
            header,
            nav,
            aside,
            .print\:hidden,
            #user-menu-button,
            .notif-badge {
                display: none !important;
            }
            main {
                overflow: visible !important;
                padding: 0 !important;
                margin: 0 !important;
                height: auto !important;
            }
            .brand-card, .bg-white {
                border: 1px solid #e2e8f0 !important;
                box-shadow: none !important;
            }
            table {
                page-break-inside: auto;
                width: 100% !important;
            }
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            thead {
                display: table-header-group;
            }
            tfoot {
                display: table-footer-group;
            }
        }
    </style>
</head>
<body class="h-full overflow-hidden bg-gray-50 antialiased font-sans">

@php 
    $u = auth()->user(); 
    $nb = $u ? \App\Models\Notification::where('is_resolved', false)->whereNull('read_at')->count() : 0;
@endphp
<div x-data="{ sidebarOpen: false, userMenuOpen: false }" class="app-shell flex h-full w-full overflow-hidden bg-gray-50 {{ request()->routeIs('pos.index') ? 'pos-shell' : '' }}" style="zoom: 90%">

    {{-- ── Mobile Sidebar Backdrop ─────────────────────────────────────────────── --}}
    <div 
        x-show="sidebarOpen" 
        x-cloak 
        x-transition:enter="transition-opacity ease-linear duration-200" 
        x-transition:enter-start="opacity-0" 
        x-transition:enter-end="opacity-100" 
        x-transition:leave="transition-opacity ease-linear duration-200" 
        x-transition:leave-start="opacity-100" 
        x-transition:leave-end="opacity-0" 
        @click="sidebarOpen = false" 
        class="fixed inset-0 z-40 bg-gray-900/60 backdrop-blur-sm lg:hidden"
        aria-hidden="true"
    ></div>

    {{-- ── Sidebar ───────────────────────────────────────────────────────────── --}}
    <aside 
        :class="sidebarOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full lg:translate-x-0'"
        class="sidebar-shell fixed inset-y-0 left-0 z-50 w-64 flex-shrink-0 text-slate-800 flex flex-col transition-transform duration-200 ease-in-out lg:static lg:translate-x-0 lg:z-20"
    >

        {{-- Logo & Mobile Close Button --}}
        <div @click="sidebarOpen = false" class="flex items-center justify-between border-b border-slate-200/80 px-4 py-3.5">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group min-w-0">
                <div class="flex h-11 px-2.5 items-center justify-center overflow-hidden rounded-xl border border-heim-100 bg-white shadow-xs ring-1 ring-slate-100 group-hover:border-heim-300 transition-colors">
                    <img src="{{ asset('images/logo.png') }}" alt="Heim" class="h-7 w-auto max-w-[76px] object-contain">
                </div>
                <div class="min-w-0">
                    <div class="text-sm font-extrabold tracking-tight text-slate-900 leading-tight">Heim</div>
                    <div class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Operations</div>
                </div>
            </a>
            <button class="lg:hidden p-1.5 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors focus:outline-none" aria-label="Close sidebar">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Navigation --}}
        <nav class="sidebar-scroll flex-1 overflow-y-auto py-3 px-3 pb-12 space-y-0.5">
            <p class="sidebar-section-label">Main</p>
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9M5 10v10h14V10"/></svg><span>Dashboard</span></a>
            <a href="{{ route('pos.index') }}" class="nav-link {{ request()->routeIs('pos.*') ? 'active' : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l2 12h12l2-8H6m2 12a1 1 0 100 2 1 1 0 000-2zm10 0a1 1 0 100 2 1 1 0 000-2z"/></svg><span>Point of Sale</span></a>
            <a href="{{ route('orders.index') }}" class="nav-link {{ request()->routeIs('orders.*', 'refunds.*', 'voids.*') ? 'active' : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4h8l3 3v13H5V4h3zm2 6h4m-4 4h6"/></svg><span>Orders</span></a>

            @if($u && $u->canManageInventory())
            <p class="sidebar-section-label pt-6">Menu</p>
            <a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.*', 'categories.*', 'recipes.*') ? 'active' : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4v10l8 4 8-4V7zm-8 4l8-4m-8 4v10M4 7l8 4"/></svg><span>Menu</span></a>
            @endif

            @if($u && $u->canManageInventory())
            <p class="sidebar-section-label pt-6">Inventory</p>
            <a href="{{ route('inventory.index') }}" class="nav-link {{ request()->routeIs('inventory.*', 'ingredients.*', 'adjustments.*', 'stock-in.*', 'waste.*', 'consumption.*') ? 'active' : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 3.6 4 8 4s8-2 8-4V7m-16 0c0 2 3.6 4 8 4s8-2 8-4m-16 0c0-2 3.6-4 8-4s8 2 8 4"/></svg><span>Stock</span></a>
            @endif

            @if($u && $u->canManageInventory())
            <p class="sidebar-section-label pt-6">Reports</p>
            <a href="{{ route('reports.sales') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 19V5m0 14h16M8 15v-4m4 4V7m4 8V9m4 6V4"/></svg><span>Reports</span></a>
            @endif

            <p class="sidebar-section-label pt-6">Administration</p>
            @if($u && $u->isOwner())
            <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2m6-10a4 4 0 100-8 4 4 0 000 8zm8-5v6m3-3h-6"/></svg><span>Users &amp; Accounts</span></a>
            @endif
            @if($u)
            <a href="{{ route('profile.edit') }}" class="nav-link {{ request()->routeIs('profile.*', 'audit-logs.*', 'settings.tax.*') ? 'active' : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8a4 4 0 100 8 4 4 0 000-8zm8 4a8 8 0 01-.2 1.8l2 1.5-2 3.5-2.4-1a8 8 0 01-3.1 1.8L14 22h-4l-.4-2.4a8 8 0 01-3.1-1.8l-2.4 1-2-3.5 2-1.5a8 8 0 010-3.6l-2-1.5 2-3.5 2.4 1a8 8 0 013.1-1.8L10 2h4l.4 2.4a8 8 0 013.1 1.8l2.4-1 2 3.5-2 1.5A8 8 0 0120 12z"/></svg><span>Settings</span></a>
            @endif
        </nav>
        {{-- Sidebar User Footer --}}
        @if($u)
        <div class="border-t border-slate-200/80 bg-slate-50/70 p-3">
            <div class="flex items-center justify-between gap-2 rounded-2xl border border-slate-200 bg-white/90 px-2.5 py-2 shadow-sm">
                <div class="flex min-w-0 flex-1 items-center gap-2.5">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-heim-600 to-heim-800 text-xs font-bold text-white shadow-sm">
                        {{ strtoupper(substr($u->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs font-semibold text-slate-900">{{ $u->name }}</p>
                        <p class="truncate text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">{{ $u->role }}</p>
                    </div>
                </div>
            </div>
        </div>
        @endif

    </aside>

    {{-- ── Main Area ─────────────────────────────────────────────────────────── --}}
    <div class="flex-1 flex flex-col overflow-hidden min-w-0 relative">

        {{-- Background Logo Watermark --}}
        <div class="pointer-events-none absolute inset-0 flex items-center justify-center overflow-hidden z-0 select-none" aria-hidden="true">
            <img src="{{ asset('images/logo.png') }}" alt="" class="w-[44rem] max-w-[80vw] opacity-[0.038] filter blur-[0.2px] transform -rotate-6 select-none pointer-events-none">
        </div>

        {{-- Topbar Header --}}
        <header class="bg-white/95 backdrop-blur-md border-b border-gray-200/80 px-3 sm:px-6 py-3 flex flex-wrap items-center justify-between flex-shrink-0 z-30 shadow-[0_1px_3px_rgba(0,0,0,0.03)] xl:flex-nowrap">
            
            {{-- Left: Mobile hamburger & Header Title --}}
            <div class="flex w-full min-w-0 items-center gap-3 sm:gap-4 xl:w-auto">
                <button 
                    @click="sidebarOpen = !sidebarOpen" 
                    type="button" 
                    class="lg:hidden p-2 -ml-1 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-xl transition-colors focus:outline-none focus:ring-2 focus:ring-heim-500"
                    aria-label="Toggle navigation"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                <div class="min-w-0">
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-lg sm:text-xl font-bold text-gray-800 tracking-tight leading-tight truncate">
                            @yield('header', 'Dashboard')
                        </h1>
                        @hasSection('header-badge')
                            @yield('header-badge')
                        @endif
                    </div>
                    @hasSection('subheader')
                        <p class="text-xs text-gray-500 mt-0.5 truncate">@yield('subheader')</p>
                    @endif
                </div>
            </div>

            {{-- Right: Header actions, Date, Notifications, Profile --}}
            <div class="ml-auto flex w-full items-center justify-end gap-2 sm:gap-3.5 xl:w-auto">
                
                {{-- Optional page-level actions --}}
                @hasSection('header-actions')
                    <div class="mr-1 flex flex-wrap items-center justify-end gap-2">
                        @yield('header-actions')
                    </div>
                @endif

                {{-- Date pill --}}
                <div class="{{ request()->routeIs('pos.index') ? 'hidden' : 'hidden md:flex' }} items-center gap-1.5 px-3 py-1.5 rounded-full bg-gray-100/90 text-gray-600 text-xs font-medium border border-gray-200/60 shadow-xs">
                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>{{ now(config('app.business_timezone', 'Asia/Manila'))->format('D, M j, Y') }}</span>
                </div>

                {{-- Notifications bell --}}
                @if($u && $u->canManageInventory())
                <a href="{{ route('notifications.index') }}" 
                   title="Notifications"
                   class="relative p-2 rounded-xl text-gray-500 hover:text-heim-700 hover:bg-heim-50/70 border border-transparent hover:border-heim-200/70 transition-all focus:outline-none focus:ring-2 focus:ring-heim-500"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    @if($nb > 0)
                        <span class="absolute top-1 right-1 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white shadow-sm ring-2 ring-white animate-pulse">
                            {{ $nb > 9 ? '9+' : $nb }}
                        </span>
                    @endif
                </a>
                @endif

                {{-- User menu dropdown (Alpine) --}}
                <div class="relative" @keydown.escape.stop="userMenuOpen = false">
                    <button 
                        @click="userMenuOpen = !userMenuOpen" 
                        type="button" 
                        class="flex items-center gap-2.5 rounded-xl border border-gray-200/90 bg-white pl-2 pr-2.5 py-1.5 shadow-xs transition hover:border-heim-300 hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-heim-500 focus:ring-offset-2"
                        :class="userMenuOpen ? 'ring-2 ring-heim-500 border-transparent bg-gray-50/80' : ''"
                        id="user-menu-button"
                        aria-expanded="userMenuOpen"
                        aria-haspopup="true"
                    >
                        <div class="relative flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-heim-600 to-heim-800 text-sm font-bold text-white shadow-xs">
                            {{ strtoupper(substr($u->name ?? 'U', 0, 1)) }}
                            <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-emerald-500 border-2 border-white rounded-full"></span>
                        </div>
                        <div class="hidden text-left sm:block">
                            <div class="text-xs font-semibold text-gray-800 leading-tight truncate max-w-[120px]">{{ $u->name ?? 'User' }}</div>
                            <div class="text-[10px] font-medium text-heim-700 uppercase tracking-wider mt-0.5">{{ ucfirst($u->role ?? '') }}</div>
                        </div>
                        <svg class="h-3.5 w-3.5 text-gray-400 transition-transform duration-150" :class="userMenuOpen ? 'rotate-180 text-gray-600' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div 
                        x-show="userMenuOpen" 
                        x-cloak
                        @click.away="userMenuOpen = false" 
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                        x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                        class="absolute right-0 z-40 mt-2 w-56 origin-top-right overflow-hidden rounded-2xl border border-gray-200/90 bg-white shadow-xl ring-1 ring-black/5 divide-y divide-gray-100"
                        role="menu"
                    >
                        <div class="px-4 py-3 bg-gray-50/70">
                            <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wider">Signed in as</p>
                            <p class="text-sm font-semibold text-gray-900 truncate mt-0.5">{{ $u->name ?? 'User' }}</p>
                            <span class="inline-flex items-center mt-1.5 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-heim-100 text-heim-800 capitalize">
                                {{ $u->role ?? 'User' }}
                            </span>
                        </div>
                        
                        <div class="py-1">
                            <a href="{{ route('profile.edit') }}" 
                               class="group flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-heim-50 hover:text-heim-800 transition-colors"
                               role="menuitem"
                            >
                                <svg class="w-4 h-4 text-gray-400 group-hover:text-heim-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span>Account Info</span>
                            </a>
                        </div>

                        <div class="py-1">
                            <form id="header-logout-form" method="POST" action="{{ route('logout') }}" onsubmit="return confirmLogout(event, this);">
                                @csrf
                                <button type="button" 
                                        onclick="confirmLogout(event, this.closest('form'))"
                                        class="group flex w-full items-center gap-2.5 px-4 py-2.5 text-left text-sm text-rose-600 hover:bg-rose-50 transition-colors"
                                        role="menuitem"
                                >
                                    <svg class="w-4 h-4 text-rose-400 group-hover:text-rose-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    <span>Log Out</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        @php
            $currentRoute = request()->route()?->getName() ?? '';
            $navigationTabs = [];
            $isOwnerOrManager = $u && ($u->isOwner() || $u->isManager());

            if (str_starts_with($currentRoute, 'orders.') || in_array($currentRoute, ['refunds.index', 'voids.index'], true)) {
                $navigationTabs[] = ['label' => 'All Orders', 'route' => 'orders.index', 'active' => ['orders.*']];
                if ($isOwnerOrManager) {
                    $navigationTabs[] = ['label' => 'Refunds', 'route' => 'refunds.index', 'active' => ['refunds.*']];
                }
                if ($u && $u->canManageInventory()) {
                    $navigationTabs[] = ['label' => 'Voids', 'route' => 'voids.index', 'active' => ['voids.*']];
                }
            } elseif (str_starts_with($currentRoute, 'products.') || str_starts_with($currentRoute, 'categories.') || str_starts_with($currentRoute, 'recipes.')) {
                $navigationTabs = [
                    ['label' => 'Products', 'route' => 'products.index', 'active' => ['products.*']],
                    ['label' => 'Categories', 'route' => 'categories.index', 'active' => ['categories.*']],
                    ['label' => 'Recipes', 'route' => 'recipes.index', 'active' => ['recipes.*']],
                ];
            } elseif (str_starts_with($currentRoute, 'inventory.') || str_starts_with($currentRoute, 'ingredients.') || str_starts_with($currentRoute, 'adjustments.') || str_starts_with($currentRoute, 'stock-in.') || str_starts_with($currentRoute, 'waste.') || str_starts_with($currentRoute, 'consumption.')) {
                $navigationTabs[] = ['label' => 'Overview', 'route' => 'inventory.index', 'active' => ['inventory.index']];
                if ($u && $u->canManageInventory()) {
                    $navigationTabs[] = ['label' => 'Ingredients', 'route' => 'ingredients.index', 'active' => ['ingredients.*']];
                }
                $navigationTabs[] = ['label' => 'Stock Movements', 'route' => 'adjustments.index', 'active' => ['adjustments.*', 'inventory.transactions', 'stock-in.*', 'waste.*', 'consumption.*']];
            } elseif (str_starts_with($currentRoute, 'reports.')) {
                $navigationTabs = [
                    ['label' => 'Sales', 'route' => 'reports.sales', 'active' => ['reports.sales']],
                    ['label' => 'Grab', 'route' => 'reports.grab', 'active' => ['reports.grab']],
                    ['label' => 'Inventory', 'route' => 'reports.inventory', 'active' => ['reports.inventory']],
                    ['label' => 'Shifts', 'route' => 'reports.shifts', 'active' => ['reports.shifts']],
                ];
            } elseif (str_starts_with($currentRoute, 'profile.') || str_starts_with($currentRoute, 'settings.tax.') || str_starts_with($currentRoute, 'audit-logs.')) {
                $navigationTabs[] = ['label' => 'Security', 'route' => 'profile.edit', 'active' => ['profile.*']];
                if ($isOwnerOrManager) {
                    $navigationTabs[] = ['label' => 'Tax', 'route' => 'settings.tax.edit', 'active' => ['settings.tax.*']];
                    $navigationTabs[] = ['label' => 'Audit Logs', 'route' => 'audit-logs.index', 'active' => ['audit-logs.*']];
                }
            }
        @endphp

        {{-- Page Content --}}
        <main id="main-content" class="flex-1 min-h-0 overflow-y-auto p-4 sm:p-6 lg:p-7 relative {{ request()->routeIs('pos.index') ? 'pos-main' : '' }}">
            @if($navigationTabs)
                @include('components.navigation-tabs', ['tabs' => $navigationTabs])
            @endif

            {{-- Flash Messages --}}
            @if(session('success'))
            <div id="flash-success" class="flex items-center gap-3 bg-heim-600 text-white text-sm font-medium px-4 py-3 rounded-xl shadow-md mb-5 animate-fade-in">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
                <button onclick="document.getElementById('flash-success').remove()" class="ml-auto text-heim-200 hover:text-white">✕</button>
            </div>
            @endif
            @if(session('error'))
            <div id="flash-error" class="flex items-center gap-3 bg-rose-600 text-white text-sm font-medium px-4 py-3 rounded-xl shadow-md mb-5">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('error') }}</span>
                <button onclick="document.getElementById('flash-error').remove()" class="ml-auto text-rose-200 hover:text-white">✕</button>
            </div>
            @endif
            @if(session('warning'))
            <div id="flash-warning" class="flex items-center gap-3 bg-amber-500 text-white text-sm font-medium px-4 py-3 rounded-xl shadow-md mb-5">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                <span>{{ session('warning') }}</span>
                <button onclick="document.getElementById('flash-warning').remove()" class="ml-auto text-amber-100 hover:text-white">✕</button>
            </div>
            @endif
            @if(isset($errors) && $errors->any() && !request()->routeIs('pos.index'))
            <div id="flash-validation" class="bg-rose-50 border border-rose-200 text-rose-700 text-sm px-4 py-3 rounded-xl shadow-sm mb-5">
                <p class="font-semibold mb-1">Please fix the following errors:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
            @endif

            @yield('content')
            {{ $slot ?? '' }}
        </main>
    </div>
</div>

{{-- ── Global Authorization Modal ───────────────────────────────────────────── --}}
<div id="auth-modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4" style="display:none">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md transform transition-all">
        <div class="flex items-center justify-between p-6 border-b border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Authorization Required</h3>
                    <p class="text-xs text-gray-500">Manager or Owner only</p>
                </div>
            </div>
            <button type="button" onclick="closeAuthModal()" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition-colors focus:outline-none" aria-label="Close modal">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Authorizer Email</label>
                <input id="auth-email" type="email" class="w-full border border-gray-300 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-heim-500 focus:border-transparent" placeholder="manager@heim.com">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                <input id="auth-password" type="password" class="w-full border border-gray-300 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-heim-500 focus:border-transparent" placeholder="••••••••">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Reason <span class="text-red-500">*</span></label>
                <textarea id="auth-reason" rows="2" class="w-full border border-gray-300 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-heim-500 focus:border-transparent resize-none" placeholder="Reason for this action..."></textarea>
            </div>
            <p id="auth-error" class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2 hidden"></p>
        </div>
        <div class="flex gap-3 px-6 pb-6">
            <button type="button" onclick="closeAuthModal()" class="brand-btn-cancel flex-1">Cancel</button>
            <button type="button" id="auth-submit" onclick="submitAuth()" class="brand-button flex-1 disabled:opacity-60">Authorize</button>
        </div>
    </div>
</div>

{{-- ── Shared confirmation dialog ──────────────────────────────────────────── --}}
<div id="confirm-modal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-gray-950/55 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
    <div class="w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-black/5">
        <div class="flex items-start justify-between border-b border-gray-100 p-6">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                </div>
                <div class="min-w-0">
                    <h2 id="confirm-title" class="text-base font-bold text-gray-900">Please confirm</h2>
                    <p id="confirm-message" class="mt-1 text-sm leading-relaxed text-gray-500"></p>
                </div>
            </div>
            <button type="button" onclick="closeConfirmModal()" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition-colors focus:outline-none -mr-1 -mt-1" aria-label="Close modal">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="flex flex-col-reverse gap-2 p-5 sm:flex-row sm:justify-end">
            <button type="button" id="confirm-cancel" class="brand-btn-cancel">Cancel</button>
            <button type="button" id="confirm-accept" class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-rose-700 transition focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2">Confirm action</button>
        </div>
    </div>
</div>

{{-- ── Fallback Global Logout Form ─────────────────────────────────────────── --}}
<form id="global-logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
    @csrf
</form>

{{-- ── Dedicated Logout Confirmation Modal ────────────────────────────────────── --}}
<div id="logout-modal" 
     class="fixed inset-0 z-[70] hidden items-center justify-center bg-gray-950/60 p-4 backdrop-blur-sm transition-opacity duration-200" 
     role="dialog" 
     aria-modal="true" 
     aria-labelledby="logout-modal-title">
    <div class="w-full max-w-md transform overflow-hidden rounded-3xl bg-white p-6 shadow-2xl ring-1 ring-black/5 transition-all">
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-600 shadow-xs">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h2 id="logout-modal-title" class="text-base sm:text-lg font-bold text-gray-900">Confirm Log Out</h2>
                    <p class="mt-1 text-sm text-gray-500 leading-relaxed">
                        Are you sure you want to end your current session?
                    </p>
                    
                    @if($u)
                    <div class="mt-3.5 flex items-center gap-2.5 rounded-2xl bg-gray-50 p-3 border border-gray-100 text-xs">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-heim-700 text-white font-bold text-xs shadow-xs">
                            {{ strtoupper(substr($u->name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-gray-900 truncate">{{ $u->name }}</p>
                            <p class="text-gray-500 truncate capitalize">{{ $u->role }} &bull; {{ $u->email }}</p>
                        </div>
                    </div>
                    @endif

                    {{-- Contextual warning if active POS cart has items --}}
                    <div id="logout-pos-warning" class="mt-3 hidden rounded-2xl border border-amber-200 bg-amber-50/90 p-3 text-xs text-amber-900 leading-relaxed">
                        <div class="flex items-center gap-2 font-bold text-amber-800">
                            <svg class="h-4 w-4 shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span>Active POS Ticket Detected</span>
                        </div>
                        <p class="mt-1 text-amber-700">You have items in your current POS cart. Logging out now will discard this unsaved ticket.</p>
                    </div>
                </div>
            </div>
            <button type="button" onclick="closeLogoutModal()" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition-colors focus:outline-none -mr-1 -mt-1" aria-label="Close modal">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button type="button" 
                    id="logout-cancel-btn"
                    onclick="closeLogoutModal()" 
                    class="brand-btn-cancel">
                Cancel
            </button>
            <button type="button" 
                    id="logout-confirm-btn"
                    onclick="proceedLogout()" 
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-rose-600 px-5 text-sm font-bold text-white shadow-sm hover:bg-rose-700 transition focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <span>Yes, Log Out</span>
            </button>
        </div>
    </div>
</div>

<script>
let _authCallback = null;
let _confirmAction = null;

function ensureAuthModalHidden() {
    const modal = document.getElementById('auth-modal');
    if (!modal) return;

    modal.classList.add('hidden');
    modal.style.display = 'none';
    document.body.classList.remove('overflow-y-hidden');
}

function openAuthModal(callback) {
    _authCallback = callback;
    const modal = document.getElementById('auth-modal');
    if (!modal) return;

    modal.classList.remove('hidden');
    modal.style.display = 'flex';
    document.body.classList.remove('overflow-y-hidden');
    document.getElementById('auth-email').value = '';
    document.getElementById('auth-password').value = '';
    document.getElementById('auth-reason').value = '';
    document.getElementById('auth-error').classList.add('hidden');
    setTimeout(() => document.getElementById('auth-email').focus(), 100);
}

function closeAuthModal() {
    const modal = document.getElementById('auth-modal');
    if (!modal) return;

    modal.classList.add('hidden');
    modal.style.display = 'none';
    document.body.classList.remove('overflow-y-hidden');
    _authCallback = null;
}

function closeConfirmModal() {
    const modal = document.getElementById('confirm-modal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.classList.remove('overflow-y-hidden');
    _confirmAction = null;
}

function openConfirmModal(message, title, action) {
    const modal = document.getElementById('confirm-modal');
    if (!modal) return;
    document.getElementById('confirm-title').textContent = title || 'Please confirm';
    document.getElementById('confirm-message').textContent = message || 'Are you sure you want to continue?';
    _confirmAction = action;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.classList.add('overflow-y-hidden');
    setTimeout(() => document.getElementById('confirm-cancel').focus(), 50);
}

document.addEventListener('submit', function (event) {
    const form = event.target.closest('form[data-confirm]');
    if (!form || form.dataset.confirmed === 'true') return;
    event.preventDefault();
    openConfirmModal(form.dataset.confirm, form.dataset.confirmTitle, function () {
        form.dataset.confirmed = 'true';
        form.requestSubmit();
    });
});

document.addEventListener('click', function (event) {
    const link = event.target.closest('a[data-confirm]');
    if (!link) return;
    event.preventDefault();
    openConfirmModal(link.dataset.confirm, link.dataset.confirmTitle, function () {
        window.location.href = link.href;
    });
});

document.getElementById('confirm-cancel')?.addEventListener('click', closeConfirmModal);
document.getElementById('confirm-accept')?.addEventListener('click', function () {
    const action = _confirmAction;
    closeConfirmModal();
    if (action) action();
});
document.getElementById('confirm-modal')?.addEventListener('click', function (event) {
    if (event.target === this) closeConfirmModal();
});
document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        closeConfirmModal();
        closeLogoutModal();
        closeAuthModal();
    }
});

async function submitAuth() {
    const email    = document.getElementById('auth-email').value.trim();
    const password = document.getElementById('auth-password').value;
    const reason   = document.getElementById('auth-reason').value.trim();
    const errorEl  = document.getElementById('auth-error');
    const btn      = document.getElementById('auth-submit');

    if (!email || !password || !reason) {
        errorEl.textContent = 'All fields are required.';
        errorEl.classList.remove('hidden');
        return;
    }

    btn.textContent = 'Verifying...';
    btn.disabled = true;
    errorEl.classList.add('hidden');

    try {
        const res = await fetch('{{ route("authorize") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ email, password }),
        });
        const data = await res.json();
        if (data.success) {
            closeAuthModal();
            if (_authCallback) _authCallback({ authorizer_email: email, authorizer_password: password, reason });
        } else {
            errorEl.textContent = data.message || 'Authorization failed.';
            errorEl.classList.remove('hidden');
        }
    } catch (e) {
        errorEl.textContent = 'Network error. Please try again.';
        errorEl.classList.remove('hidden');
    } finally {
        btn.textContent = 'Authorize';
        btn.disabled = false;
    }
}

const authModal = document.getElementById('auth-modal');
if (authModal) {
    authModal.addEventListener('click', function(e) {
        if (e.target === this) closeAuthModal();
    });
}

// ── Dedicated Logout Confirmation Handlers ─────────────────────────────────────
let _logoutTargetForm = null;

function openLogoutModal(targetForm) {
    _logoutTargetForm = targetForm || document.getElementById('global-logout-form') || document.getElementById('header-logout-form');
    const modal = document.getElementById('logout-modal');
    if (!modal) {
        if (confirm('Are you sure you want to log out?')) {
            if (_logoutTargetForm) _logoutTargetForm.submit();
        }
        return;
    }

    // Check for active POS cart items if on POS screen
    const posWarning = document.getElementById('logout-pos-warning');
    if (posWarning) {
        const hasPosItems = (typeof cart !== 'undefined' && Array.isArray(cart) && cart.length > 0);
        if (hasPosItems) {
            posWarning.classList.remove('hidden');
        } else {
            posWarning.classList.add('hidden');
        }
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.classList.add('overflow-y-hidden');

    // Focus cancel button for safe keyboard usage
    setTimeout(() => {
        const cancelBtn = document.getElementById('logout-cancel-btn');
        if (cancelBtn) cancelBtn.focus();
    }, 50);
}

function closeLogoutModal() {
    const modal = document.getElementById('logout-modal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.classList.remove('overflow-y-hidden');
    _logoutTargetForm = null;
}

function proceedLogout() {
    const btn = document.getElementById('logout-confirm-btn');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = `
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Logging out...</span>
        `;
    }

    const form = _logoutTargetForm || document.getElementById('global-logout-form') || document.getElementById('header-logout-form');
    if (form) {
        form.submit();
    } else {
        const fallbackForm = document.createElement('form');
        fallbackForm.method = 'POST';
        fallbackForm.action = '{{ route("logout") }}';
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        if (csrf) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = '_token';
            input.value = csrf;
            fallbackForm.appendChild(input);
        }
        document.body.appendChild(fallbackForm);
        fallbackForm.submit();
    }
}

function confirmLogout(event, form) {
    if (event) {
        if (typeof event.preventDefault === 'function') event.preventDefault();
        if (typeof event.stopPropagation === 'function') event.stopPropagation();
    }
    const target = form || (event && event.target ? event.target.closest('form') : null);
    openLogoutModal(target);
    return false;
}

window.confirmLogout = confirmLogout;
window.openLogoutModal = openLogoutModal;
window.closeLogoutModal = closeLogoutModal;
window.proceedLogout = proceedLogout;

document.getElementById('logout-modal')?.addEventListener('click', function (event) {
    if (event.target === this) closeLogoutModal();
});

if ('scrollRestoration' in history) {
    history.scrollRestoration = 'manual';
}

function resetMainScroll() {
    window.scrollTo(0, 0);
    const main = document.getElementById('main-content') || document.querySelector('main');
    if (main) main.scrollTop = 0;
}

window.addEventListener('pageshow', function () {
    ensureAuthModalHidden();
    closeLogoutModal();
    resetMainScroll();
});

window.addEventListener('DOMContentLoaded', function () {
    ensureAuthModalHidden();
    closeLogoutModal();
    resetMainScroll();
    requestAnimationFrame(resetMainScroll);
});

window.addEventListener('load', function () {
    resetMainScroll();
});

// Auto-dismiss flash messages after 4s
['flash-success','flash-error','flash-warning'].forEach(id => {
    const el = document.getElementById(id);
    if (el) setTimeout(() => el && el.remove(), 4000);
});

@if(request('print') === 'all')
window.addEventListener('load', function () {
    setTimeout(function () {
        window.print();
    }, 450);
});
@endif
</script>

@stack('scripts')
</body>
</html>
