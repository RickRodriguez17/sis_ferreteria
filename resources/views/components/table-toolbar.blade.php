<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div class="relative flex-1">
        <input {{ $attributes->merge(['class' => 'w-full rounded-lg border-slate-300 pl-10 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500']) }} placeholder="Buscar...">
        <span class="pointer-events-none absolute left-3 top-2.5 text-slate-400">⌕</span>
    </div>
    {{ $slot }}
</div>
