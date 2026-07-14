@props(['active' => false, 'href'])
<a href="{{ $href }}" @class(['block rounded-lg px-3 py-2 transition', 'bg-indigo-600 text-white' => $active, 'text-slate-300 hover:bg-slate-800 hover:text-white' => ! $active])>
    {{ $slot }}
</a>
