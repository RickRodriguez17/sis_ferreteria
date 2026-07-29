<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <title>{{ config('app.name', 'Construir a tu Alcance') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div x-data="{ sidebarOpen: false }" class="min-h-screen bg-slate-100">
            <div x-cloak x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden" @click="sidebarOpen = false"></div>
            <aside class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col bg-slate-900 text-white shadow-xl transition-transform duration-300 lg:translate-x-0" :class="{ 'translate-x-0': sidebarOpen }" aria-label="Navegación principal">
                <div class="flex h-20 shrink-0 items-center justify-between border-b border-slate-800 px-6">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3" wire:navigate>
                        <span class="erp-icon bg-indigo-600 text-white"><i class="bi bi-shop"></i></span>
                        <span><span class="block text-base font-bold tracking-tight">Construir a tu Alcance</span><span class="block text-xs text-slate-400">ERP ferretero</span></span>
                    </a>
                    <button class="rounded-lg p-2 text-slate-400 hover:bg-slate-800 hover:text-white lg:hidden" @click="sidebarOpen = false" aria-label="Cerrar menú"><i class="bi bi-x-lg"></i></button>
                </div>
                <nav class="flex-1 space-y-2 overflow-y-auto px-3 py-5 text-sm">
                    <a href="{{ route('dashboard') }}" wire:navigate @class(['flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition', 'bg-indigo-600 text-white' => request()->routeIs('dashboard'), 'text-slate-300 hover:bg-slate-800 hover:text-white' => ! request()->routeIs('dashboard')])><i class="bi bi-grid-1x2-fill w-5 text-center"></i><span>Panel</span></a>
                    <x-sidebar-group title="Catálogo" icon="bi-box-seam" :active="request()->routeIs('products.*', 'catalog.*')">
                        @can('viewAny', \App\Models\Product::class)<x-sidebar-link :href="route('products.index')" :active="request()->routeIs('products.*')" wire:navigate><i class="bi bi-box-seam w-5 text-center"></i>Productos</x-sidebar-link>@endcan
                        @can('viewAny', \App\Models\Category::class)<x-sidebar-link :href="route('catalog.categories')" :active="request()->routeIs('catalog.categories')" wire:navigate><i class="bi bi-tags w-5 text-center"></i>Categorías</x-sidebar-link>@endcan
                        @can('viewAny', \App\Models\Brand::class)<x-sidebar-link :href="route('catalog.brands')" :active="request()->routeIs('catalog.brands')" wire:navigate><i class="bi bi-award w-5 text-center"></i>Marcas</x-sidebar-link>@endcan
                        @can('viewAny', \App\Models\Unit::class)<x-sidebar-link :href="route('catalog.units')" :active="request()->routeIs('catalog.units')" wire:navigate><i class="bi bi-rulers w-5 text-center"></i>Unidades</x-sidebar-link>@endcan
                        @can('viewAny', \App\Models\Attribute::class)<x-sidebar-link :href="route('catalog.attributes')" :active="request()->routeIs('catalog.attributes')" wire:navigate><i class="bi bi-list-stars w-5 text-center"></i>Atributos</x-sidebar-link>@endcan
                    </x-sidebar-group>
                    <x-sidebar-group title="Inventario" icon="bi-boxes" :active="request()->routeIs('inventory.*')">
                        @can('inventory.view')<x-sidebar-link :href="route('inventory.index')" :active="request()->routeIs('inventory.index')" wire:navigate><i class="bi bi-boxes w-5 text-center"></i>Existencias</x-sidebar-link><x-sidebar-link :href="route('inventory.kardex')" :active="request()->routeIs('inventory.kardex')" wire:navigate><i class="bi bi-journal-text w-5 text-center"></i>Kardex</x-sidebar-link><x-sidebar-link :href="route('inventory.movements')" :active="request()->routeIs('inventory.movements')" wire:navigate><i class="bi bi-arrow-left-right w-5 text-center"></i>Movimientos</x-sidebar-link>@endcan
                        @can('inventory.transfer')<x-sidebar-link :href="route('inventory.transfer')" :active="request()->routeIs('inventory.transfer')" wire:navigate><i class="bi bi-arrow-left-right w-5 text-center"></i>Transferencias</x-sidebar-link>@endcan
                        @can('inventory.adjust')<x-sidebar-link :href="route('inventory.adjust')" :active="request()->routeIs('inventory.adjust')" wire:navigate><i class="bi bi-sliders w-5 text-center"></i>Ajustes</x-sidebar-link>@endcan
                        @can('prices.update')<x-sidebar-link :href="route('inventory.prices')" :active="request()->routeIs('inventory.prices')" wire:navigate><i class="bi bi-tags-fill w-5 text-center"></i>Historial de precios</x-sidebar-link>@endcan
                    </x-sidebar-group>
                    <x-sidebar-group title="Compras" icon="bi-bag-check" :active="request()->routeIs('suppliers.*', 'purchases.*', 'receptions.*')">
                        @can('viewAny', \App\Models\Supplier::class)<x-sidebar-link :href="route('suppliers.index')" :active="request()->routeIs('suppliers.*')" wire:navigate><i class="bi bi-truck w-5 text-center"></i>Proveedores</x-sidebar-link>@endcan
                        @can('viewAny', \App\Models\Purchase::class)<x-sidebar-link :href="route('purchases.index')" :active="request()->routeIs('purchases.*', 'receptions.*')" wire:navigate><i class="bi bi-bag-check w-5 text-center"></i>Compras</x-sidebar-link>@endcan
                    </x-sidebar-group>
                    <x-sidebar-group title="Ventas" icon="bi-cart-check" :active="request()->routeIs('customers.*', 'sales.*', 'quotations.*', 'credits.*')">
                        @can('viewAny', \App\Models\Customer::class)<x-sidebar-link :href="route('customers.index')" :active="request()->routeIs('customers.*')" wire:navigate><i class="bi bi-people w-5 text-center"></i>Clientes</x-sidebar-link>@endcan
                        @can('viewAny', \App\Models\Sale::class)<x-sidebar-link :href="route('sales.index')" :active="request()->routeIs('sales.*')" wire:navigate><i class="bi bi-cart-check w-5 text-center"></i>Ventas</x-sidebar-link>@endcan
                        @can('viewAny', \App\Models\Quotation::class)<x-sidebar-link :href="route('quotations.index')" :active="request()->routeIs('quotations.*')" wire:navigate><i class="bi bi-file-earmark-text w-5 text-center"></i>Cotizaciones</x-sidebar-link>@endcan
                        @can('viewAny', \App\Models\Credit::class)<x-sidebar-link :href="route('credits.index')" :active="request()->routeIs('credits.*')" wire:navigate><i class="bi bi-credit-card w-5 text-center"></i>Créditos</x-sidebar-link>@endcan
                    </x-sidebar-group>
                    <x-sidebar-group title="Caja" icon="bi-cash-stack" :active="request()->routeIs('cash.*')">
                        @can('viewAny', \App\Models\CashSession::class)<x-sidebar-link :href="route('cash.index')" :active="request()->routeIs('cash.index')" wire:navigate><i class="bi bi-cash-stack w-5 text-center"></i>Caja</x-sidebar-link><x-sidebar-link :href="route('cash.sessions.index')" :active="request()->routeIs('cash.sessions.*')" wire:navigate><i class="bi bi-clock-history w-5 text-center"></i>Historial de caja</x-sidebar-link>@endcan
                        @can('viewAny', \App\Models\PaymentAccount::class)<x-sidebar-link :href="route('cash.payment-accounts.index')" :active="request()->routeIs('cash.payment-accounts.*')" wire:navigate><i class="bi bi-wallet2 w-5 text-center"></i>Cuentas de cobro</x-sidebar-link>@endcan
                    </x-sidebar-group>
                    <x-sidebar-group title="Herramientas" icon="bi-tools" :active="request()->routeIs('products.import', 'reports.*')">
                        @can('create', \App\Models\Product::class)<x-sidebar-link :href="route('products.import')" :active="request()->routeIs('products.import')" wire:navigate><i class="bi bi-file-earmark-arrow-up w-5 text-center"></i>Carga masiva</x-sidebar-link>@endcan
                        @can('reports.view')<x-sidebar-link :href="route('reports.index')" :active="request()->routeIs('reports.*')" wire:navigate><i class="bi bi-file-earmark-bar-graph w-5 text-center"></i>Reportes</x-sidebar-link>@endcan
                    </x-sidebar-group>
                    @can('viewAny', \App\Models\User::class)
                        <x-sidebar-group title="Administración" icon="bi-shield-lock" :active="request()->routeIs('users.*')">
                            <x-sidebar-link :href="route('users.index')" :active="request()->routeIs('users.*')" wire:navigate><i class="bi bi-people w-5 text-center"></i>Usuarios</x-sidebar-link>
                        </x-sidebar-group>
                    @endcan
                </nav>
            </aside>
            <div class="min-w-0 lg:pl-72">
                <livewire:layout.navigation />
                @if (isset($header))<header class="border-b border-slate-200 bg-white"><div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">{{ $header }}</div></header>@endif
                <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">{{ $slot }}</main>
            </div>
        </div>
        @if (session('success'))
            <script>window.erpToast(@js(session('success')), 'success');</script>
        @endif
        @if (session('error'))
            <script>window.erpAlert({ icon: 'error', title: 'No fue posible completar la operación', text: @js(session('error')) });</script>
        @endif
        @if (session('status'))
            <script>window.erpToast(@js(session('status')), 'info');</script>
        @endif
    </body>
</html>
