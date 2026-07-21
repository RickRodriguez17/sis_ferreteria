<div>
    <div class="mb-6 flex items-start justify-between">
        <div>
            <a href="{{ route('customers.index') }}" class="text-sm text-indigo-600">← Clientes</a>
            <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ $customer->name }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $customer->document_number ?: 'Sin documento' }} · {{ $customer->phone ?: 'Sin teléfono' }}</p>
        </div>
        @can('update', $customer)
            <a href="{{ route('customers.edit', $customer) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700">Editar cliente</a>
        @endcan
    </div>
    @if(session('success'))<div class="mb-4 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-700">{{ session('success') }}</div>@endif
    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-xl bg-white p-5 shadow-sm"><p class="text-xs uppercase text-slate-500">Tipo</p><p class="mt-2 font-semibold">{{ $customer->type->value === 'registered' ? 'Registrado' : 'Ocasional' }}</p></div>
        <div class="rounded-xl bg-white p-5 shadow-sm"><p class="text-xs uppercase text-slate-500">Límite de crédito</p><p class="mt-2 font-semibold">{{ number_format((float) ($customer->credit_limit ?? 0), 2) }}</p></div>
        <div class="rounded-xl bg-white p-5 shadow-sm"><p class="text-xs uppercase text-slate-500">Saldo pendiente</p><p class="mt-2 font-semibold text-rose-600">{{ number_format((float) $outstandingBalance, 2) }}</p></div>
        <div class="rounded-xl bg-white p-5 shadow-sm"><p class="text-xs uppercase text-slate-500">Estado</p><p class="mt-2 font-semibold">{{ $customer->is_active ? 'Activo' : 'Inactivo' }}</p></div>
    </div>
    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl bg-white p-6 shadow-sm">
            <h2 class="font-semibold">Historial de ventas</h2>
            <div class="mt-4 overflow-x-auto"><table class="w-full text-left text-sm"><thead class="border-b text-xs uppercase text-slate-500"><tr><th class="px-2 py-2">Código</th><th class="px-2 py-2">Fecha</th><th class="px-2 py-2">Total</th><th class="px-2 py-2">Estado</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($customer->sales as $sale)<tr><td class="px-2 py-2">{{ $sale->code }}</td><td class="px-2 py-2">{{ $sale->created_at?->format('d/m/Y') }}</td><td class="px-2 py-2">{{ number_format((float) $sale->total, 2) }}</td><td class="px-2 py-2">{{ $sale->status->value === 'completed' ? 'Completada' : 'Cancelada' }}</td></tr>@empty<tr><td colspan="4" class="px-2 py-6 text-center text-slate-500">No hay ventas registradas.</td></tr>@endforelse</tbody></table></div>
        </section>
        <section class="rounded-xl bg-white p-6 shadow-sm">
            <h2 class="font-semibold">Cotizaciones</h2>
            <div class="mt-4 overflow-x-auto"><table class="w-full text-left text-sm"><thead class="border-b text-xs uppercase text-slate-500"><tr><th class="px-2 py-2">Código</th><th class="px-2 py-2">Vigencia</th><th class="px-2 py-2">Total</th><th class="px-2 py-2">Estado</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($customer->quotations as $quotation)<tr><td class="px-2 py-2">{{ $quotation->code }}</td><td class="px-2 py-2">{{ $quotation->valid_until?->format('d/m/Y') }}</td><td class="px-2 py-2">{{ number_format((float) $quotation->total, 2) }}</td><td class="px-2 py-2">{{ ['open' => 'Abierta', 'converted' => 'Convertida', 'expired' => 'Vencida', 'cancelled' => 'Cancelada'][$quotation->status->value] ?? $quotation->status->value }}</td></tr>@empty<tr><td colspan="4" class="px-2 py-6 text-center text-slate-500">No hay cotizaciones registradas.</td></tr>@endforelse</tbody></table></div>
        </section>
    </div>
    <section class="mt-6 rounded-xl bg-white p-6 shadow-sm">
        <h2 class="font-semibold">Créditos y estado de cuenta</h2>
        <div class="mt-4 overflow-x-auto"><table class="w-full text-left text-sm"><thead class="border-b text-xs uppercase text-slate-500"><tr><th class="px-2 py-2">Venta</th><th class="px-2 py-2">Monto original</th><th class="px-2 py-2">Pagado</th><th class="px-2 py-2">Saldo</th><th class="px-2 py-2">Vencimiento</th><th class="px-2 py-2">Estado</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($customer->credits as $credit)<tr><td class="px-2 py-2">{{ $credit->sale?->code ?: '—' }}</td><td class="px-2 py-2">{{ number_format((float) $credit->original_amount, 2) }}</td><td class="px-2 py-2">{{ number_format((float) $credit->paid_amount, 2) }}</td><td class="px-2 py-2 font-semibold">{{ number_format((float) $credit->balance, 2) }}</td><td class="px-2 py-2">{{ $credit->due_date?->format('d/m/Y') ?: '—' }}</td><td class="px-2 py-2">{{ ['open' => 'Abierto', 'partial' => 'Parcial', 'paid' => 'Pagado', 'overdue' => 'Vencido', 'cancelled' => 'Anulado'][$credit->status->value] ?? $credit->status->value }}</td></tr>@empty<tr><td colspan="6" class="px-2 py-6 text-center text-slate-500">No hay créditos registrados.</td></tr>@endforelse</tbody></table></div>
        <h3 class="mt-8 font-semibold">Cobros</h3>
        <div class="mt-4 overflow-x-auto"><table class="w-full text-left text-sm"><thead class="border-b text-xs uppercase text-slate-500"><tr><th class="px-2 py-2">Fecha</th><th class="px-2 py-2">Monto</th><th class="px-2 py-2">Método</th><th class="px-2 py-2">Crédito</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($payments as $payment)<tr><td class="px-2 py-2">{{ $payment->paid_at?->format('d/m/Y H:i') }}</td><td class="px-2 py-2">{{ number_format((float) $payment->amount, 2) }}</td><td class="px-2 py-2">{{ ['cash' => 'Efectivo', 'qr' => 'QR', 'transfer' => 'Transferencia'][$payment->method->value] ?? $payment->method->value }}</td><td class="px-2 py-2">{{ $payment->credit?->sale?->code ?: '—' }}</td></tr>@empty<tr><td colspan="4" class="px-2 py-6 text-center text-slate-500">No hay cobros registrados.</td></tr>@endforelse</tbody></table></div>
    </section>
</div>
