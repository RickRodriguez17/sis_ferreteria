<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <a href="{{ route('purchases.index') }}" class="text-sm text-indigo-600">← Compras</a>
            <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ $purchase->code }}</h1>
            <p class="text-sm text-slate-500">{{ $purchase->supplier->name }} · {{ $purchase->created_at?->format('d/m/Y') }}</p>
        </div>
        <div class="flex gap-2">
            @if(in_array($purchase->status->value, ['pending', 'partial'], true))
                @can('create', \App\Models\Reception::class)<a href="{{ route('receptions.create', $purchase) }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Registrar recepción</a>@endcan
                @if($purchase->status->value === 'pending')
                    @can('update', $purchase)<button wire:click="cancel" wire:confirm="¿Cancelar esta compra?" class="rounded-lg border border-rose-300 px-4 py-2 text-sm text-rose-600">Cancelar</button>@endcan
                @endif
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if($errors->has('cancel') || $errors->has('price'))
        <div class="mb-4 rounded-lg bg-rose-50 p-3 text-sm text-rose-700">{{ $errors->first('cancel') ?: $errors->first('price') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="rounded-xl bg-white p-6 shadow-sm lg:col-span-2">
            <h2 class="font-semibold">Detalle de productos</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b text-xs uppercase text-slate-500"><tr><th class="px-3 py-3">Producto</th><th class="px-3 py-3">Pedido</th><th class="px-3 py-3">Recibido</th><th class="px-3 py-3">Pendiente</th><th class="px-3 py-3">Costo</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($purchase->items as $item)
                            <tr><td class="px-3 py-3 font-medium">{{ $item->product->name }}</td><td class="px-3 py-3">{{ $item->quantity_ordered }}</td><td class="px-3 py-3">{{ $item->quantity_received }}</td><td class="px-3 py-3">{{ bcsub((string) $item->quantity_ordered, (string) $item->quantity_received, 4) }}</td><td class="px-3 py-3">{{ $item->unit_cost }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        <aside class="rounded-xl bg-white p-6 shadow-sm">
            <h2 class="font-semibold">Resumen</h2>
            <p class="mt-4 text-sm">Estado: <span class="rounded-full bg-indigo-100 px-2 py-1 text-xs text-indigo-700">{{ $purchase->status->name }}</span></p>
            <p class="mt-4 text-2xl font-bold">{{ $purchase->total }}</p>
            <p class="text-sm text-slate-500">{{ $purchase->payment_type->name }}</p>
        </aside>
    </div>

    @if($this->receivedProducts()->isNotEmpty())
        <section class="mt-6 rounded-xl bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div><h2 class="font-semibold">Sugerir/actualizar precios de venta</h2><p class="mt-1 text-sm text-slate-500">Las sugerencias usan el costo actual y no modifican precios hasta que confirmes.</p></div>
                <div class="w-40"><x-input-label value="Margen global"/><x-text-input type="number" step="0.01" min="0" max="10" wire:model.live="margin" class="mt-1 w-full"/><p class="mt-1 text-xs text-slate-500">Ejemplo: 0.30 = 30%</p></div>
            </div>
            <div class="mt-5 space-y-6">
                @foreach($this->receivedProducts() as $product)
                    <div wire:key="received-product-{{ $product->id }}" class="rounded-lg border border-slate-200 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3"><div><h3 class="font-medium">{{ $product->name }}</h3><p class="text-xs text-slate-500">Costo actual: {{ number_format((float) $product->cost, 4) }}</p></div><div class="w-40"><x-input-label value="Margen del producto"/><x-text-input type="number" step="0.01" min="0" max="10" wire:model.live="productMargins.{{ $product->id }}" class="mt-1 w-full"/></div></div>
                        <div class="mt-4 overflow-x-auto"><table class="w-full text-left text-sm"><thead class="border-b text-xs uppercase text-slate-500"><tr><th class="px-3 py-2">Presentación</th><th class="px-3 py-2">Sin factura</th><th class="px-3 py-2">Con factura</th><th class="px-3 py-2">Sugerido</th><th class="px-3 py-2">Aplicar</th></tr></thead><tbody class="divide-y divide-slate-100">
                            @foreach($product->presentations as $presentation)
                                <tr><td class="px-3 py-3 font-medium">{{ $presentation->name }}</td><td class="px-3 py-3">{{ $presentation->price_without_invoice }}</td><td class="px-3 py-3">{{ $presentation->price_with_invoice }}</td><td class="px-3 py-3 font-semibold text-indigo-700">{{ $this->suggestedPrice($product, $product->id) }}</td><td class="px-3 py-3">@if($this->canApplyPrices())<div class="flex flex-wrap gap-2"><button wire:click="applySuggestedPrice({{ $presentation->id }}, 'price_without_invoice', {{ $product->id }})" class="rounded bg-indigo-600 px-2 py-1 text-xs text-white">Sin factura</button><button wire:click="applySuggestedPrice({{ $presentation->id }}, 'price_with_invoice', {{ $product->id }})" class="rounded bg-indigo-600 px-2 py-1 text-xs text-white">Con factura</button></div>@else<span class="text-xs text-slate-400">Solo Admin/Gerente</span>@endif</td></tr>
                            @endforeach
                        </tbody></table></div>
                    </div>
                @endforeach
            </div>
            @if($this->canApplyPrices())<div class="mt-5 max-w-xl"><x-input-label value="Motivo del cambio"/><x-text-input wire:model="priceReason" class="mt-1 w-full"/><x-input-error :messages="$errors->get('priceReason')"/></div>@endif
        </section>
    @endif

    <div class="mt-6 rounded-xl bg-white p-6 shadow-sm"><h2 class="font-semibold">Recepciones</h2><div class="mt-4 space-y-3">@forelse($purchase->receptions as $reception)<div class="rounded-lg bg-slate-50 p-4"><div class="flex justify-between"><span class="font-medium">{{ $reception->code }}</span><span>{{ $reception->destination->name }} · {{ $reception->location->name }}</span></div><p class="mt-1 text-sm text-slate-500">{{ $reception->received_at?->format('d/m/Y H:i') }} · {{ $reception->attachments->count() }} adjunto(s)</p></div>@empty<p class="py-6 text-sm text-slate-500">No hay recepciones todavía.</p>@endforelse</div></div>
</div>
