<?php

namespace App\Livewire;

use App\Models\Location;
use App\Models\Product;
use App\Repositories\Contracts\KardexRepository;
use Livewire\Component;

class KardexIndex extends Component
{
    public ?int $productId = null;

    public string $locationId = '';

    public string $from = '';

    public string $to = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('inventory.view'), 403);
    }

    public function render(KardexRepository $repository)
    {
        $product = $this->productId ? Product::find($this->productId) : null;
        $movements = $product ? $repository->forProduct($product, $this->locationId !== '' ? (int) $this->locationId : null, $this->from ?: null, $this->to ?: null) : collect();

        return view('livewire.kardex-index', ['movements' => $movements, 'products' => Product::query()->active()->orderBy('name')->get(['id', 'name', 'code']), 'locations' => Location::query()->orderBy('name')->get(['id', 'name'])])->layout('layouts.app');
    }
}
