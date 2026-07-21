<?php

namespace App\Livewire;

use App\Models\Location;
use App\Repositories\Contracts\InventoryRepository;
use Livewire\Component;

class InventoryIndex extends Component
{
    public string $locationId = '';

    public bool $belowMinimum = false;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('inventory.view'), 403);
    }

    public function render(InventoryRepository $repository)
    {
        $items = $this->belowMinimum ? $repository->belowMinimum() : $repository->byLocation($this->locationId !== '' ? (int) $this->locationId : null);

        return view('livewire.inventory-index', ['items' => $items, 'locations' => Location::query()->orderBy('name')->get(['id', 'name'])])->layout('layouts.app');
    }
}
