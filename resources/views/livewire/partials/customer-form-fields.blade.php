<form wire:submit="save" class="space-y-5">
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="customer-type" value="Tipo de cliente" />
            <select id="customer-type" wire:model="type" class="mt-1 w-full rounded-lg border-slate-300">
                @foreach($types as $item)
                    <option value="{{ $item->value }}">{{ $item->value === 'registered' ? 'Registrado' : 'Ocasional' }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('type')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="customer-name" value="Nombre" />
            <x-text-input id="customer-name" wire:model="name" class="mt-1 w-full" />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="customer-document-type" value="Tipo de documento" />
            <x-text-input id="customer-document-type" wire:model="documentType" class="mt-1 w-full" placeholder="CI, NIT, RUC..." />
            <x-input-error :messages="$errors->get('documentType')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="customer-document-number" value="Número de documento" />
            <x-text-input id="customer-document-number" wire:model="documentNumber" class="mt-1 w-full" />
            <x-input-error :messages="$errors->get('documentNumber')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="customer-phone" value="Teléfono" />
            <x-text-input id="customer-phone" wire:model="phone" class="mt-1 w-full" />
            <x-input-error :messages="$errors->get('phone')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="customer-email" value="Correo electrónico" />
            <x-text-input id="customer-email" wire:model="email" type="email" class="mt-1 w-full" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>
        <div class="sm:col-span-2">
            <x-input-label for="customer-address" value="Dirección" />
            <x-text-input id="customer-address" wire:model="address" class="mt-1 w-full" />
            <x-input-error :messages="$errors->get('address')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="customer-credit-limit" value="Límite de crédito" />
            <x-text-input id="customer-credit-limit" wire:model="creditLimit" type="number" min="0" step="0.01" class="mt-1 w-full" />
            <p class="mt-1 text-xs text-slate-500">Un valor mayor que cero identifica al cliente como cliente de crédito.</p>
            <x-input-error :messages="$errors->get('creditLimit')" class="mt-1" />
        </div>
        <label class="flex items-center gap-2 self-end text-sm">
            <input type="checkbox" wire:model="isActive" class="rounded border-slate-300 text-indigo-600">
            Cliente activo
        </label>
    </div>
    <div class="flex justify-end gap-3">
        <button type="button" wire:click="cancel" class="rounded-lg border px-4 py-2 text-sm">Cancelar</button>
        <x-primary-button>Guardar cliente</x-primary-button>
    </div>
</form>
