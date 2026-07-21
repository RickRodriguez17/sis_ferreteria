<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

        <title>{{ config('app.name', 'Construir a tu Alcance') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-slate-100">
            <div class="flex min-h-screen">
                <aside class="hidden w-64 shrink-0 bg-slate-900 text-white lg:block">
                    <div class="border-b border-slate-800 px-6 py-5">
                        <a href="{{ route('dashboard') }}" class="text-lg font-bold tracking-tight">Construir a tu Alcance</a>
                        <p class="mt-1 text-xs text-slate-400">ERP ferretero</p>
                    </div>
                    <nav class="space-y-6 px-3 py-6 text-sm">
                        <x-sidebar-section title="Catálogo">
                            @can('viewAny', \App\Models\Product::class)<x-sidebar-link :href="route('products.index')" :active="request()->routeIs('products.*')">Productos</x-sidebar-link>@endcan
                            @can('viewAny', \App\Models\Category::class)<x-sidebar-link :href="route('catalog.categories')" :active="request()->routeIs('catalog.categories')">Categorías</x-sidebar-link>@endcan
                            @can('viewAny', \App\Models\Brand::class)<x-sidebar-link :href="route('catalog.brands')" :active="request()->routeIs('catalog.brands')">Marcas</x-sidebar-link>@endcan
                            @can('viewAny', \App\Models\Unit::class)<x-sidebar-link :href="route('catalog.units')" :active="request()->routeIs('catalog.units')">Unidades</x-sidebar-link>@endcan
                            @can('viewAny', \App\Models\Attribute::class)<x-sidebar-link :href="route('catalog.attributes')" :active="request()->routeIs('catalog.attributes')">Atributos</x-sidebar-link>@endcan
                        </x-sidebar-section>
                        <x-sidebar-section title="Inventario">
                            @can('inventory.view')<x-sidebar-link :href="route('inventory.index')" :active="request()->routeIs('inventory.index')">Existencias</x-sidebar-link><x-sidebar-link :href="route('inventory.kardex')" :active="request()->routeIs('inventory.kardex')">Kardex</x-sidebar-link><x-sidebar-link :href="route('inventory.movements')" :active="request()->routeIs('inventory.movements')">Movimientos</x-sidebar-link>@endcan
                            @can('inventory.transfer')<x-sidebar-link :href="route('inventory.transfer')" :active="request()->routeIs('inventory.transfer')">Transferencias</x-sidebar-link>@endcan
                            @can('inventory.adjust')<x-sidebar-link :href="route('inventory.adjust')" :active="request()->routeIs('inventory.adjust')">Ajustes</x-sidebar-link>@endcan
                            @can('prices.update')<x-sidebar-link :href="route('inventory.prices')" :active="request()->routeIs('inventory.prices')">Historial de precios</x-sidebar-link>@endcan
                        </x-sidebar-section>
                        <x-sidebar-section title="Compras">
                            @can('viewAny', \App\Models\Supplier::class)<x-sidebar-link :href="route('suppliers.index')" :active="request()->routeIs('suppliers.*')">Proveedores</x-sidebar-link>@endcan
                            @can('viewAny', \App\Models\Purchase::class)<x-sidebar-link :href="route('purchases.index')" :active="request()->routeIs('purchases.*')">Compras</x-sidebar-link>@endcan
                        </x-sidebar-section>
                        <x-sidebar-section title="Ventas">
                            @can('viewAny', \App\Models\Customer::class)<x-sidebar-link :href="route('customers.index')" :active="request()->routeIs('customers.*')">Clientes</x-sidebar-link>@endcan
                            @can('viewAny', \App\Models\Sale::class)<x-sidebar-link :href="route('sales.index')" :active="request()->routeIs('sales.*')">Ventas</x-sidebar-link>@endcan
                            @can('viewAny', \App\Models\Quotation::class)<x-sidebar-link :href="route('quotations.index')" :active="request()->routeIs('quotations.*')">Cotizaciones</x-sidebar-link>@endcan
                            @can('viewAny', \App\Models\Credit::class)<x-sidebar-link :href="route('credits.index')" :active="request()->routeIs('credits.*')">Créditos</x-sidebar-link>@endcan
                        </x-sidebar-section>
                        <x-sidebar-section title="Caja">
                            @can('viewAny', \App\Models\CashSession::class)<x-sidebar-link :href="route('cash.index')" :active="request()->routeIs('cash.index')">Caja</x-sidebar-link><x-sidebar-link :href="route('cash.sessions.index')" :active="request()->routeIs('cash.sessions.*')">Historial de caja</x-sidebar-link>@endcan
                            @can('viewAny', \App\Models\PaymentAccount::class)<x-sidebar-link :href="route('cash.payment-accounts.index')" :active="request()->routeIs('cash.payment-accounts.*')">Cuentas de cobro</x-sidebar-link>@endcan
                        </x-sidebar-section>
                        <x-sidebar-section title="Herramientas">
                            @can('create', \App\Models\Product::class)<x-sidebar-link :href="route('products.import')" :active="request()->routeIs('products.import')">Carga masiva</x-sidebar-link>@endcan
                            @can('reports.view')<x-sidebar-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">Reportes</x-sidebar-link>@endcan
                        </x-sidebar-section>
                    </nav>
                </aside>
                <div class="min-w-0 flex-1">
                    <livewire:layout.navigation />
                    @if (isset($header))
                        <header class="border-b border-slate-200 bg-white">
                            <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">{{ $header }}</div>
                        </header>
                    @endif
                    <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">{{ $slot }}</main>
                </div>
            </div>
        </div>
    </body>
</html>
