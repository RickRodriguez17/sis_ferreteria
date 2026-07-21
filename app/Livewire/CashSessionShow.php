<?php

namespace App\Livewire;

use App\Domain\Enums\CashMovementType;
use App\Domain\Enums\PaymentMethod;
use App\Models\CashSession;
use App\Services\CashService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class CashSessionShow extends Component
{
    public CashSession $session;

    public string $type = '';

    public string $method = '';

    public string $date = '';

    public function mount(CashSession $session): void
    {
        Gate::authorize('view', $session);
        $this->session = $session->load(['register', 'opener', 'closer']);
    }

    public function render()
    {
        $movements = $this->session->movements()->with(['creator', 'paymentAccount', 'reference'])
            ->when($this->type !== '', fn ($query) => $query->where('type', $this->type))
            ->when($this->method !== '', fn ($query) => $query->where('method', $this->method))
            ->when($this->date !== '', fn ($query) => $query->whereDate('created_at', $this->date))
            ->latest()->get();

        $totalsByType = $this->session->movements()->selectRaw('type, SUM(amount) as total')->groupBy('type')->pluck('total', 'type');
        $totalsByMethod = $this->session->movements()->selectRaw('method, SUM(amount) as total')->groupBy('method')->pluck('total', 'method');

        return view('livewire.cash-session-show', [
            'movements' => $movements,
            'totalsByType' => $totalsByType,
            'totalsByMethod' => $totalsByMethod,
            'expected' => app(CashService::class)->expectedAmount($this->session),
            'types' => CashMovementType::cases(),
            'methods' => PaymentMethod::cases(),
        ])->layout('layouts.app');
    }
}
