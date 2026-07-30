@props(['title', 'icon', 'active' => false])
<div x-data="{ open: {{ $active ? 'true' : 'false' }} }" class="pt-2">
    <button type="button" @click="open = ! open" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-xs font-semibold uppercase tracking-wider text-slate-400 transition hover:bg-slate-800 hover:text-white">
        <i class="bi {{ $icon }} w-5 text-center text-sm"></i><span>{{ $title }}</span><i class="bi bi-chevron-down ml-auto text-xs transition-transform" :class="{ 'rotate-180': open }"></i>
    </button>
    <div x-cloak x-show="open" x-transition class="mt-1 space-y-1 border-l border-slate-700 pl-2">{{ $slot }}</div>
</div>
