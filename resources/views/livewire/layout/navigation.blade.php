<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }
}; ?>

<header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="flex h-20 items-center justify-between px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3">
            <button type="button" @click="sidebarOpen = ! sidebarOpen" class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-indigo-600 lg:hidden" aria-label="Abrir menú"><i class="bi bi-list text-2xl"></i></button>
            <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2 text-lg font-bold tracking-tight text-slate-800 lg:hidden"><i class="bi bi-shop text-indigo-600"></i>Construir a tu Alcance</a>
            <div class="hidden items-center gap-2 text-sm text-slate-500 lg:flex"><i class="bi bi-house-door text-indigo-600"></i><span>Panel de gestión</span></div>
        </div>
        <div class="flex items-center gap-3" x-data="{ profileOpen: false }">
            <div class="relative">
                <button type="button" @click="profileOpen = ! profileOpen" class="flex items-center gap-2 rounded-lg px-2 py-2 text-left transition hover:bg-slate-100">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-100 text-indigo-700"><i class="bi bi-person"></i></span>
                    <span class="hidden sm:block"><span class="block text-sm font-semibold text-slate-700">{{ auth()->user()->name }}</span><span class="block text-xs text-slate-500">{{ auth()->user()->getRoleNames()->first() ?? 'Usuario' }}</span></span>
                    <i class="bi bi-chevron-down text-xs text-slate-400"></i>
                </button>
                <div x-cloak x-show="profileOpen" @click.outside="profileOpen = false" x-transition class="absolute right-0 mt-2 w-56 rounded-xl border border-slate-200 bg-white p-2 shadow-lg">
                    <div class="border-b border-slate-100 px-3 py-2"><p class="text-sm font-semibold text-slate-700">{{ auth()->user()->name }}</p><p class="text-xs text-slate-500">{{ auth()->user()->email }}</p></div>
                    <a href="{{ route('profile') }}" wire:navigate class="mt-1 flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50"><i class="bi bi-person-gear text-indigo-600"></i>Perfil</a>
                    <button wire:click="logout" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-rose-600 hover:bg-rose-50"><i class="bi bi-box-arrow-right"></i>Cerrar sesión</button>
                </div>
            </div>
        </div>
    </div>
</header>
