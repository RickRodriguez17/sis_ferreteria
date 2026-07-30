@props(['title', 'icon' => 'bi-grid-1x2', 'section' => 'Inicio', 'subtitle' => null])
<div class="mb-6 flex items-center justify-between gap-4">
    <div class="flex items-center gap-3">
        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-100 text-xl text-indigo-700"><i class="bi {{ $icon }}"></i></span>
        <div>
            <nav class="mb-1 text-xs text-slate-400" aria-label="Breadcrumb"><a href="{{ route('dashboard') }}" class="hover:text-indigo-600">Inicio</a><span class="mx-1">/</span><span>{{ $section }}</span></nav>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $title }}</h1>
            @if($subtitle)<p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>@endif
        </div>
    </div>
    @if(isset($actions) && trim((string) $actions))
        <div class="shrink-0">{{ $actions }}</div>
    @endif
</div>
