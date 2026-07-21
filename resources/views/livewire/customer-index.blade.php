<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Clientes</h1>
            <p class="text-sm text-slate-500">Administra clientes registrados, ocasionales y de crédito.</p>
        </div>
        @can('create', \App\Models\Customer::class)
            <button wire:click="create" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Agregar cliente</button>
        @endcan
    </div>
    @if(session('success'))<div class="mb-4 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-700">{{ session('success') }}</div>@endif
    <div class="rounded-xl bg-white p-5 shadow-sm">
        <x-table-toolbar wire:model.live.debounce.300ms="search">
            <select wire:model.live="type" class="rounded-lg border-slate-300 text-sm">
                <option value="">Todos los tipos</option>
                @foreach($types as $item)<option value="{{ $item->value }}">{{ $item->value === 'registered' ? 'Registrados' : 'Ocasionales' }}</option>@endforeach
            </select>
            <select wire:model.live="credit" class="rounded-lg border-slate-300 text-sm">
                <option value="">Todos</option>
                <option value="yes">Con crédito</option>
                <option value="no">Sin crédito</option>
            </select>
            <select wire:model.live="status" class="rounded-lg border-slate-300 text-sm">
                <option value="">Todos los estados</option>
                <option value="active">Activos</option>
                <option value="inactive">Inactivos</option>
            </select>
        </x-table-toolbar>
        <div class="mt-5 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b text-xs uppercase text-slate-500"><tr><th class="px-3 py-3">Cliente</th><th class="px-3 py-3">Documento</th><th class="px-3 py-3">Contacto</th><th class="px-3 py-3">Tipo</th><th class="px-3 py-3">Límite de crédito</th><th class="px-3 py-3">Estado</th><th class="px-3 py-3 text-right">Acciones</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($customers as $customer)
                        <tr>
                            <td class="px-3 py-3"><a href="{{ route('customers.show', $customer) }}" class="font-medium text-indigo-600">{{ $customer->name }}</a></td>
                            <td class="px-3 py-3">{{ $customer->document_number ?: 'Sin documento' }}</td>
                            <td class="px-3 py-3">{{ $customer->phone ?: $customer->email ?: '—' }}</td>
                            <td class="px-3 py-3">{{ $customer->type->value === 'registered' ? 'Registrado' : 'Ocasional' }}</td>
                            <td class="px-3 py-3">{{ number_format((float) ($customer->credit_limit ?? 0), 2) }}</td>
                            <td class="px-3 py-3"><span class="rounded-full px-2 py-1 text-xs {{ $customer->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $customer->is_active ? 'Activo' : 'Inactivo' }}</span></td>
                            <td class="px-3 py-3 text-right">
                                <a href="{{ route('customers.show', $customer) }}" class="text-slate-600">Ver</a>
                                @can('update', $customer)<button wire:click="edit({{ $customer->id }})" class="ml-3 text-indigo-600">Editar</button>@endcan
                                @can('update', $customer)<button wire:click="toggle({{ $customer->id }})" class="ml-3 text-amber-600">{{ $customer->is_active ? 'Desactivar' : 'Activar' }}</button>@endcan
                                @can('delete', $customer)<button wire:click="delete({{ $customer->id }})" wire:confirm="¿Eliminar este cliente?" class="ml-3 text-rose-600">Eliminar</button>@endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-3 py-12 text-center text-slate-500">No hay clientes para los filtros seleccionados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $customers->links() }}</div>
    </div>
    @can('create', \App\Models\Customer::class)
        <livewire:customer-form :in-modal="true" />
    @endcan
</div>
