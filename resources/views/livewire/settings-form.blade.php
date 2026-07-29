<div>
    <x-pagetitle title="Configuración de la empresa" icon="bi-building-gear" section="Administración" subtitle="Personaliza la identidad y los datos de tus documentos." />

    <form wire:submit="save" class="space-y-6">
        <div class="rounded-xl bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Datos de la empresa</h2>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div><x-input-label value="Nombre comercial"/><x-text-input wire:model="companyName" class="mt-1 w-full"/><x-input-error :messages="$errors->get('companyName')"/></div>
                <div><x-input-label value="Razón social"/><x-text-input wire:model="companyLegalName" class="mt-1 w-full"/><x-input-error :messages="$errors->get('companyLegalName')"/></div>
                <div><x-input-label value="NIT / identificación tributaria"/><x-text-input wire:model="companyTaxId" class="mt-1 w-full"/><x-input-error :messages="$errors->get('companyTaxId')"/></div>
                <div><x-input-label value="Teléfono"/><x-text-input wire:model="companyPhone" class="mt-1 w-full"/><x-input-error :messages="$errors->get('companyPhone')"/></div>
                <div><x-input-label value="Correo electrónico"/><x-text-input type="email" wire:model="companyEmail" class="mt-1 w-full"/><x-input-error :messages="$errors->get('companyEmail')"/></div>
                <div><x-input-label value="Moneda (código)"/><x-text-input wire:model="currency" maxlength="3" class="mt-1 w-full uppercase"/><x-input-error :messages="$errors->get('currency')"/></div>
                <div class="sm:col-span-2"><x-input-label value="Dirección"/><textarea wire:model="companyAddress" rows="2" class="mt-1 w-full rounded-lg border-slate-300"></textarea><x-input-error :messages="$errors->get('companyAddress')"/></div>
            </div>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Logo y documentos</h2>
            <div class="mt-5 grid gap-6 md:grid-cols-2">
                <div>
                    <x-input-label value="Logo de empresa"/>
                    <input type="file" wire:model="logo" accept="image/*" class="mt-1 block w-full rounded-lg border border-slate-300 p-2 text-sm">
                    <p class="mt-1 text-xs text-slate-500">Imagen JPG, PNG, SVG o WebP de hasta 2 MB.</p>
                    <x-input-error :messages="$errors->get('logo')"/>
                    <div wire:loading wire:target="logo" class="mt-2 text-sm text-indigo-600">Cargando logo...</div>
                </div>
                <div class="flex min-h-32 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4">
                    @if($logo)
                        <img src="{{ $logo->temporaryUrl() }}" alt="Vista previa del logo" class="max-h-28 max-w-full">
                    @elseif($currentLogoUrl)
                        <img src="{{ $currentLogoUrl }}" alt="Logo actual" class="max-h-28 max-w-full">
                    @else
                        <span class="text-sm text-slate-500">No hay logo configurado.</span>
                    @endif
                </div>
            </div>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div><x-input-label value="Margen predeterminado"/><x-text-input type="number" step="0.01" min="0" max="10" wire:model="defaultMargin" class="mt-1 w-full"/><x-input-error :messages="$errors->get('defaultMargin')"/></div>
                <div><x-input-label value="Pie de nota de venta"/><textarea wire:model="saleFooter" rows="2" class="mt-1 w-full rounded-lg border-slate-300"></textarea><x-input-error :messages="$errors->get('saleFooter')"/></div>
                <div class="sm:col-span-2"><x-input-label value="Pie de cotización"/><textarea wire:model="quotationFooter" rows="2" class="mt-1 w-full rounded-lg border-slate-300"></textarea><x-input-error :messages="$errors->get('quotationFooter')"/></div>
            </div>
        </div>

        <div class="flex justify-end"><x-primary-button>Guardar configuración</x-primary-button></div>
    </form>
</div>
