<div>
    <x-modal name="credit-payment" focusable>
        <form wire:submit="save" class="p-6">
            <h2 class="text-lg font-semibold text-slate-900">Registrar cobro</h2>
            @if($credit)<p class="mt-1 text-sm text-slate-500">Saldo disponible: {{ number_format((float) $credit->balance, 2) }}</p>@endif
            <div class="mt-5 grid gap-4 sm:grid-cols-2"><div><x-input-label for="payment-amount" value="Monto" /><x-text-input id="payment-amount" wire:model="amount" type="number" min="0.01" step="0.01" class="mt-1 block w-full" /><x-input-error :messages="$errors->get('amount')" class="mt-2" /></div><div><x-input-label for="payment-method" value="Forma de pago" /><select id="payment-method" wire:model="method" class="mt-1 block w-full rounded-md border-slate-300"><option value="cash">Efectivo</option><option value="qr">QR</option><option value="transfer">Transferencia</option></select><x-input-error :messages="$errors->get('method')" class="mt-2" /></div><div><x-input-label for="payment-paid-at" value="Fecha del cobro" /><x-text-input id="payment-paid-at" wire:model="paidAt" type="datetime-local" class="mt-1 block w-full" /><x-input-error :messages="$errors->get('paidAt')" class="mt-2" /></div><div><x-input-label for="payment-notes" value="Observaciones" /><x-text-input id="payment-notes" wire:model="notes" type="text" class="mt-1 block w-full" /><x-input-error :messages="$errors->get('notes')" class="mt-2" /></div></div>
            <div class="mt-6 flex justify-end gap-3"><x-secondary-button type="button" x-on:click="$dispatch('close')">Cancelar</x-secondary-button><x-primary-button>Guardar cobro</x-primary-button></div>
        </form>
    </x-modal>
</div>
