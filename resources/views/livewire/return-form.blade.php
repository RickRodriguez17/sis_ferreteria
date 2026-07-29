<div>
    <x-pagetitle :title="$type === 'customer' ? 'Devolución de cliente' : 'Devolución a proveedor'" icon="bi-arrow-return-left" section="Devoluciones" subtitle="Selecciona el documento y las cantidades que deseas devolver." />
    @error('form')<div class="mb-4 rounded-lg bg-rose-50 p-3 text-sm text-rose-700">{{ $message }}</div>@enderror
    @error('returnItems')<div class="mb-4 rounded-lg bg-rose-50 p-3 text-sm text-rose-700">{{ $message }}</div>@enderror
    <div class="rounded-xl bg-white p-5 shadow-sm">
        <div class="grid gap-4 sm:grid-cols-2">
            <div><x-input-label :value="$type === 'customer' ? 'Venta' : 'Recepción'"/><select wire:model.live="sourceId" class="mt-1 w-full rounded-lg border-slate-300"><option value="">Seleccionar documento</option>@foreach($sources as $source)<option value="{{ $source->id }}">{{ $source->code }} — {{ $type === 'customer' ? ($source->customer?->name ?: 'Cliente ocasional') : ($source->purchase?->supplier?->name ?: 'Proveedor') }}</option>@endforeach</select><x-input-error :messages="$errors->get('sourceId')"/></div>
            <div><x-input-label value="Notas"/><textarea wire:model="notes" rows="2" class="mt-1 w-full rounded-lg border-slate-300"></textarea><x-input-error :messages="$errors->get('notes')"/></div>
        </div>
        @if($returnItems !== [])
            <div class="mt-6 overflow-x-auto"><table class="w-full text-left text-sm"><thead class="border-b text-xs uppercase text-slate-500"><tr><th class="px-3 py-3">Producto</th><th class="px-3 py-3">Disponible</th><th class="px-3 py-3">Precio/costo</th><th class="px-3 py-3">Cantidad a devolver</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach($returnItems as $index => $item)<tr><td class="px-3 py-3">{{ $item['product'] }}</td><td class="px-3 py-3">{{ number_format($item['available'], 4) }}</td><td class="px-3 py-3">{{ number_format($item['unit_price'], 4) }}</td><td class="px-3 py-3"><input type="number" min="0" max="{{ $item['available'] }}" step="0.0001" wire:model="returnItems.{{ $index }}.quantity" class="w-40 rounded-lg border-slate-300"><x-input-error :messages="$errors->get('returnItems.'.$index.'.quantity')"/></td></tr>@endforeach</tbody></table></div>
        @endif
        <div class="mt-6 flex justify-end gap-3"><a href="{{ route('returns.index', ['type' => $type]) }}" class="rounded-lg border px-4 py-2">Cancelar</a><button wire:click="save" wire:confirm="¿Confirmar esta devolución?" class="rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white">Registrar devolución</button></div>
    </div>
</div>
