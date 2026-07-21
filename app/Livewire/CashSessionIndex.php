<?php

namespace App\Livewire;

use App\Domain\Enums\CashSessionStatus;
use App\Livewire\Traits\WithTableState;
use App\Models\CashSession;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class CashSessionIndex extends Component
{
    use WithTableState;

    public string $status = '';

    public string $from = '';

    public string $to = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', CashSession::class);
    }

    public function render()
    {
        $sessions = CashSession::query()->with(['register', 'opener', 'closer'])
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->when($this->from !== '', fn ($query) => $query->whereDate('opened_at', '>=', $this->from))
            ->when($this->to !== '', fn ($query) => $query->whereDate('opened_at', '<=', $this->to))
            ->latest('opened_at')->paginate($this->perPage);

        return view('livewire.cash-session-index', ['sessions' => $sessions, 'statuses' => CashSessionStatus::cases()])->layout('layouts.app');
    }
}
