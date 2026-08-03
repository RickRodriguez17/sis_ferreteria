<div>
    <div class="mb-6 flex items-center justify-between"><div><h1 class="text-2xl font-bold text-slate-900">Créditos</h1><p class="text-sm text-slate-500">Saldos pendientes y estado de cuenta de clientes.</p></div></div>
    @if(session('success'))<div class="mb-4 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-700">{{ session('success') }}</div>@endif
    @error('cancel')<div class="mb-4 rounded-lg bg-rose-50 p-3 text-sm text-rose-700">{{ $message }}</div>@enderror
    <div class="mb-4 grid gap-3 md:grid-cols-3"><div class="rounded-xl bg-amber-50 p-4 text-amber-800"><div class="text-xs uppercase">Próximos a vencer</div><div class="mt-1 text-xl font-bold">{{ $alertSummary['upcomingCount'] }} · {{ money($alertSummary['upcomingAmount']) }}</div></div><div class="rounded-xl bg-orange-50 p-4 text-orange-800"><div class="text-xs uppercase">Vencen hoy</div><div class="mt-1 text-xl font-bold">{{ $alertSummary['dueTodayCount'] }} · {{ money($alertSummary['dueTodayAmount']) }}</div></div><div class="rounded-xl bg-rose-50 p-4 text-rose-800"><div class="text-xs uppercase">Vencidos</div><div class="mt-1 text-xl font-bold">{{ $alertSummary['overdueCount'] }} · {{ money($alertSummary['overdueAmount']) }}</div></div></div>
    <div class="rounded-xl bg-white p-5 shadow-sm">
        <x-table-toolbar wire:model.live.debounce.300ms="search">
            <select wire:model.live="status" class="rounded-lg border-slate-300 text-sm"><option value="">Todos los estados</option>@foreach($statuses as $item)<option value="{{ $item->value }}">{{ $item->label() }}</option>@endforeach</select>
            <select wire:model.live="dueFilter" class="rounded-lg border-slate-300 text-sm"><option value="">Vencimiento</option><option value="overdue">Vencidos</option><option value="today">Vencen hoy</option><option value="soon">Por vencer ({{ $alertDays }} días)</option></select>
        </x-table-toolbar>
        <div class="mt-5 overflow-x-auto"><table class="w-full text-left text-sm">
            <thead class="border-b text-xs uppercase text-slate-500"><tr><th class="px-3 py-3">Cliente / venta</th><th class="px-3 py-3">Original</th><th class="px-3 py-3">Pagado</th><th class="px-3 py-3">Saldo</th><th class="px-3 py-3">Vencimiento</th><th class="px-3 py-3">Estado</th><th class="px-3 py-3 text-right">Acciones</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($credits as $credit)
                    @php($statusClass = ['open' => 'bg-amber-100 text-amber-800', 'partial' => 'bg-blue-100 text-blue-800', 'paid' => 'bg-emerald-100 text-emerald-800', 'overdue' => 'bg-rose-100 text-rose-800', 'cancelled' => 'bg-slate-200 text-slate-700'][$credit->status->value] ?? 'bg-slate-100 text-slate-700')
                    <tr><td class="px-3 py-3"><div class="font-medium">{{ $credit->customer?->name }}</div><div class="text-xs text-slate-500">Venta: {{ $credit->sale?->code ?? 'Sin venta' }}</div>@if($credit->hasPendingPriceItems())<span class="mt-1 inline-block rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800">Precios por definir</span>@endif</td><td class="px-3 py-3">{{ money((float) $credit->original_amount) }}</td><td class="px-3 py-3">{{ money((float) $credit->paid_amount) }}</td><td class="px-3 py-3 font-semibold">{{ money((float) $credit->balance) }}</td><td class="px-3 py-3">{{ $credit->due_date?->format('d/m/Y') ?? 'Sin fecha' }} @if($credit->days_overdue > 0)<span class="block text-xs font-medium text-rose-600">{{ $credit->days_overdue }} días de atraso</span>@elseif($credit->isOverdue)<span class="block text-xs font-medium text-rose-600">Alerta: vencido</span>@elseif($credit->due_date?->between(now(), now()->addDays(7)))<span class="block text-xs font-medium text-amber-600">Alerta: por vencer</span>@endif</td><td class="px-3 py-3"><span class="rounded-full px-2 py-1 text-xs font-medium {{ $statusClass }}">{{ $credit->status->label() }}</span></td><td class="px-3 py-3 text-right"><a href="{{ route('credits.show', $credit) }}" class="text-indigo-600">Ver</a>@can('create', \App\Models\CreditPayment::class) @if((float) $credit->balance > 0 && $credit->status->value !== 'cancelled')<button wire:click="pay({{ $credit->id }})" class="ml-3 text-emerald-700">Cobrar</button>@endif @endcan @can('cancel', $credit) @if(! $credit->payments()->exists() && $credit->status->value !== 'cancelled')<button wire:click="cancel({{ $credit->id }})" wire:confirm="¿Anular este crédito?" class="ml-3 text-rose-600">Anular</button>@endif @endcan</td></tr>
                @empty
                    <tr><td colspan="7" class="px-3 py-12 text-center text-slate-500">No hay créditos para los filtros seleccionados.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        <div class="mt-4">{{ $credits->links() }}</div>
    </div>
    <livewire:payment-form />
</div>
