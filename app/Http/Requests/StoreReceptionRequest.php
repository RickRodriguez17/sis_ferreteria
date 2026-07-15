<?php

namespace App\Http\Requests;

use App\Domain\Enums\ReceptionDestination;
use App\Models\Reception;
use Illuminate\Validation\Rule;

class StoreReceptionRequest extends BaseCrudRequest
{
    protected string $modelClass = Reception::class;

    public function rules(): array
    {
        return ['purchase_id' => ['required', 'exists:purchases,id'], 'location_id' => ['required', 'exists:locations,id'], 'destination' => ['required', Rule::enum(ReceptionDestination::class)], 'destination_reference' => ['nullable', 'string', 'required_if:destination,'.ReceptionDestination::Work->value], 'received_at' => ['required', 'date'], 'notes' => ['nullable', 'string'], 'items' => ['required', 'array', 'min:1'], 'items.*.purchase_item_id' => ['required', 'exists:purchase_items,id'], 'items.*.product_id' => ['required', 'exists:products,id'], 'items.*.quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,4'], 'items.*.unit_cost' => ['required', 'numeric', 'decimal:0,4']];
    }
}
