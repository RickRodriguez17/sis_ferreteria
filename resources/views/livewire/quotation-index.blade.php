<div>
    <div class="mb-6 flex items-center justify-between">
        <div><h1 class="text-2xl font-bold text-slate-900">Cotizaciones</h1><p class="text-sm text-slate-500">Propuestas comerciales e historial.</p></div>
        @can('create', \App\Models\Quotation::class)<a href="{{ route('quotations.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Nueva cotización</a>@endcan
    </div>
    @if(session('success'))<div class="mb-4 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-700">{{ session('success') }}</div>@endif
    <div class="rounded-xl bg-white p-5 shadow-sm">
        <x-table-toolbar wire:model.live.debounce.300ms="search">
            <select wire:model.live="status" class="rounded-lg border-slate-300 text-sm"><option value="">Estados</option>@foreach($statuses as $item)<option value="{{ $item->value }}">{{ ['open' => 'Abierta', 'converted' => 'Convertida', 'expired' => 'Vencida', 'cancelled' => 'Cancelada'][$item->value] }}</option>@endforeach</select>
            <select wire:model.live="customerId" class="rounded-lg border-slate-300 text-sm"><option value="">Clientes</option>@foreach($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->name }}</option>@endforeach</select>
            <input type="date" wire:model.live="from" class="rounded-lg border-slate-300 text-sm"><input type="date" wire:model.live="to" class="rounded-lg border-slate-300 text-sm">
        </x-table-toolbar>
        <div class="mt-5 overflow-x-auto"><table class="w-full text-left text-sm">
            <thead class="border-b text-xs uppercase text-slate-500"><tr><th class="px-3 py-3">Cotización</th><th class="px-3 py-3">Cliente</th><th class="px-3 py-3">Total</th><th class="px-3 py-3">Estado</th><th class="px-3 py-3 text-right">Acciones</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($quotations as $quotation)
                    <tr><td class="px-3 py-3"><div class="font-medium">{{ $quotation->code }}</div><div class="text-xs text-slate-500">{{ $quotation->created_at?->format('d/m/Y') }}</div></td><td class="px-3 py-3">{{ $quotation->customer?->name ?: 'Cliente ocasional' }}</td><td class="px-3 py-3">{{ number_format((float) $quotation->total, 2) }}</td><td class="px-3 py-3">{{ ['open' => 'Abierta', 'converted' => 'Convertida', 'expired' => 'Vencida', 'cancelled' => 'Cancelada'][$quotation->status->value] }}</td><td class="px-3 py-3 text-right"><a href="{{ route('quotations.show', $quotation) }}" class="text-indigo-600">Ver</a>@can('update', $quotation) @if($quotation->status->value === 'open')<a href="{{ route('quotations.edit', $quotation) }}" class="ml-3 text-slate-600">Editar</a>@endif @endcan @can('create', \App\Models\Quotation::class)<button wire:click="duplicate({{ $quotation->id }})" class="ml-3 text-slate-600">Duplicar</button>@endcan</td></tr>
                @empty
                    <tr><td colspan="5" class="px-3 py-12 text-center text-slate-500">No hay cotizaciones.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        <div class="mt-4">{{ $quotations->links() }}</div>
    </div>
</div>
