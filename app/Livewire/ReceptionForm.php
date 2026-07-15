<?php

namespace App\Livewire;

use App\Domain\Enums\PurchaseStatus;
use App\Domain\Enums\ReceptionDestination;
use App\Models\Location;
use App\Models\Purchase;
use App\Models\Reception;
use App\Services\ReceptionService;
use App\Services\Support\CodeGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithFileUploads;

class ReceptionForm extends Component
{
    use WithFileUploads;

    public Purchase $purchase;

    public ?int $locationId = null;

    public string $destination = 'tienda';

    public ?string $destinationReference = null;

    public string $receivedAt = '';

    public ?string $notes = null;

    public array $items = [];

    public array $attachments = [];

    public function mount(Purchase $purchase): void
    {
        Gate::authorize('create', Reception::class);
        $this->purchase = $purchase->load(['items.product']);
        abort_if(in_array(PurchaseStatus::from((string) $purchase->getRawOriginal('status'))->value, ['completed', 'cancelled'], true), 403);
        $this->receivedAt = now()->format('Y-m-d\TH:i');
        $this->locationId = Location::query()->where('is_default', true)->value('id');
        $this->items = $purchase->items->mapWithKeys(fn ($item): array => [$item->id => ['purchase_item_id' => $item->id, 'product_id' => $item->product_id, 'quantity' => bcsub((string) $item->quantity_ordered, (string) $item->quantity_received, 4), 'unit_cost' => (string) $item->unit_cost, 'pending' => bcsub((string) $item->quantity_ordered, (string) $item->quantity_received, 4), 'product_name' => $item->product->name]])->all();
    }

    public function save(ReceptionService $service): void
    {
        $this->validate(['locationId' => ['required', 'exists:locations,id'], 'destination' => ['required', 'in:tienda,obra'], 'destinationReference' => ['nullable', 'required_if:destination,obra', 'string', 'max:255'], 'receivedAt' => ['required', 'date'], 'notes' => ['nullable', 'string'], 'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120']]);
        $items = collect($this->items)->filter(fn (array $item): bool => (float) $item['quantity'] > 0)->map(fn (array $item): array => ['purchase_item_id' => $item['purchase_item_id'], 'product_id' => $item['product_id'], 'quantity' => $item['quantity'], 'unit_cost' => $item['unit_cost']])->values();
        if ($items->isEmpty()) {
            $this->addError('items', 'Ingresa al menos una cantidad recibida.');

            return;
        }
        try {
            $reception = DB::transaction(function () use ($service, $items): Reception {
                $reception = Reception::create(['code' => app(CodeGenerator::class)->document('reception'), 'purchase_id' => $this->purchase->id, 'location_id' => $this->locationId, 'destination' => $this->destination, 'destination_reference' => $this->destinationReference, 'received_at' => $this->receivedAt, 'notes' => $this->notes]);
                $reception->items()->createMany($items->all());

                return $service->post($reception);
            });
            foreach ($this->attachments as $attachment) {
                $path = $attachment->store('receptions/'.$reception->id, 'public');
                $reception->attachments()->create(['disk' => 'public', 'path' => $path, 'original_name' => $attachment->getClientOriginalName(), 'mime_type' => $attachment->getMimeType(), 'size' => $attachment->getSize()]);
            }
            session()->flash('success', 'Recepción registrada y stock actualizado.');
            $this->redirectRoute('purchases.show', $this->purchase, navigate: true);
        } catch (\InvalidArgumentException $exception) {
            $this->addError('items', $exception->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.reception-form', ['locations' => Location::query()->active()->orderBy('name')->get(['id', 'name']), 'destinations' => ReceptionDestination::cases()])->layout('layouts.app');
    }
}
