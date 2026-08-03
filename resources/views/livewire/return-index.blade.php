<div>
    <x-pagetitle title="Devoluciones" icon="bi-arrow-return-left" section="Operaciones" subtitle="Consulta devoluciones de clientes y proveedores.">
        <x-slot:actions>
            @can('create', \App\Models\CustomerReturn::class)<a href="{{ route('returns.create', 'customer') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Devolución de cliente</a>@endcan
            @can('create', \App\Models\SupplierReturn::class)<a href="{{ route('returns.create', 'supplier') }}" class="ml-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Devolución a proveedor</a>@endcan
        </x-slot:actions>
    </x-pagetitle>
    <div class="rounded-xl bg-white p-5 shadow-sm">
        <x-table-toolbar wire:model.live.debounce.300ms="search">
            <select wire:model.live="type" class="rounded-lg border-slate-300 text-sm"><option value="">Clientes</option><option value="supplier">Proveedores</option></select>
            <input type="date" wire:model.live="from" class="rounded-lg border-slate-300 text-sm">
            <input type="date" wire:model.live="to" class="rounded-lg border-slate-300 text-sm">
        </x-table-toolbar>
        <div class="mt-5 overflow-x-auto"><table class="w-full text-left text-sm"><thead class="border-b text-xs uppercase text-slate-500"><tr><th class="px-3 py-3">Documento</th><th class="px-3 py-3">Origen</th><th class="px-3 py-3">Tercero</th><th class="px-3 py-3">Fecha</th><th class="px-3 py-3 text-right">Total</th></tr></thead><tbody class="divide-y divide-slate-100">
            @forelse($returns as $return)
                <tr><td class="px-3 py-3 font-medium">{{ $return->code }}</td><td class="px-3 py-3">{{ $type === 'supplier' ? $return->reception?->code : $return->sale?->code }}</td><td class="px-3 py-3">{{ $type === 'supplier' ? $return->supplier?->name : ($return->customer?->name ?: 'Cliente ocasional') }}</td><td class="px-3 py-3">{{ $return->returned_at?->format('d/m/Y H:i') }}</td><td class="px-3 py-3 text-right">{{ money((float) $return->total) }}</td></tr>
            @empty
                <tr><td colspan="5" class="px-3 py-12 text-center text-slate-500">No hay devoluciones para los filtros seleccionados.</td></tr>
            @endforelse
        </tbody></table></div><div class="mt-4">{{ $returns->links() }}</div>
    </div>
</div>
