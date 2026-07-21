@if($inModal)
    <x-modal name="customer-record">
        <div class="p-6">
            <h2 class="text-lg font-semibold">{{ $customer ? 'Editar cliente' : 'Agregar cliente' }}</h2>
            <div class="mt-5">@include('livewire.partials.customer-form-fields')</div>
        </div>
    </x-modal>
@else
    <div>
        <div class="mb-6">
            <a href="{{ route('customers.index') }}" class="text-sm text-indigo-600">← Clientes</a>
            <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ $customer ? 'Editar cliente' : 'Nuevo cliente' }}</h1>
        </div>
        <div class="rounded-xl bg-white p-6 shadow-sm">
            @include('livewire.partials.customer-form-fields')
        </div>
    </div>
@endif
