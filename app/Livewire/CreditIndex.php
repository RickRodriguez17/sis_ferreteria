<?php

namespace App\Livewire;

use App\Domain\Enums\CreditStatus;
use App\Livewire\Traits\WithTableState;
use App\Models\Credit;
use App\Models\CreditPayment;
use App\Services\CreditService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class CreditIndex extends Component
{
    use WithTableState;

    public string $status = '';

    public string $dueFilter = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Credit::class);
    }

    public function pay(int $id): void
    {
        Gate::authorize('create', CreditPayment::class);
        $this->dispatch('credit-payment-open', creditId: $id);
    }

    public function cancel(int $id, CreditService $service): void
    {
        $credit = Credit::findOrFail($id);
        Gate::authorize('cancel', $credit);

        try {
            $service->cancel($credit);
            session()->flash('success', 'Crédito anulado correctamente.');
        } catch (Throwable $exception) {
            $this->addError('cancel', $exception->getMessage());
        }
    }

    #[On('payment-registered')]
    public function refreshList(): void {}

    public function render()
    {
        $today = now()->startOfDay();
        $soon = now()->addDays(7)->endOfDay();
        $credits = Credit::query()
            ->with(['customer:id,name,document_number', 'sale:id,code'])
            ->when($this->search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->whereHas('customer', fn ($customer) => $customer->where('name', 'like', '%'.$this->search.'%')->orWhere('document_number', 'like', '%'.$this->search.'%'))
                ->orWhereHas('sale', fn ($sale) => $sale->where('code', 'like', '%'.$this->search.'%'))))
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->when($this->dueFilter === 'overdue', fn ($query) => $query->where('balance', '>', 0)->whereDate('due_date', '<', $today)->where('status', '!=', CreditStatus::Cancelled))
            ->when($this->dueFilter === 'soon', fn ($query) => $query->where('balance', '>', 0)->whereBetween('due_date', [$today, $soon])->where('status', '!=', CreditStatus::Cancelled))
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.credit-index', [
            'credits' => $credits,
            'statuses' => CreditStatus::cases(),
        ])->layout('layouts.app');
    }
}
