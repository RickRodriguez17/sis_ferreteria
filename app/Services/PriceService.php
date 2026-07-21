<?php

namespace App\Services;

use App\Domain\Enums\PriceField;
use App\Events\PriceChanged;
use App\Exceptions\PriceChangeNotAllowedException;
use App\Models\Presentation;
use App\Models\PriceHistory;
use Illuminate\Support\Facades\DB;

class PriceService
{
    public function changePrice(Presentation $presentation, PriceField|string $field, float|int|string $newValue, ?string $reason = null): PriceHistory
    {
        if (! auth()->user()?->can('prices.update')) {
            throw new PriceChangeNotAllowedException('El usuario actual no puede modificar precios.');
        }
        $field = $field instanceof PriceField ? $field : PriceField::from($field);
        $history = DB::transaction(function () use ($presentation, $field, $newValue, $reason): PriceHistory {
            if (! in_array($field, [PriceField::PriceWithInvoice, PriceField::PriceWithoutInvoice], true)) {
                throw new PriceChangeNotAllowedException('Los costos deben gestionarse mediante la recalculación del costo de inventario.');
            }
            $oldValue = $presentation->{$field->value};
            $presentation->update([$field->value => $newValue]);

            return PriceHistory::create(['priceable_type' => $presentation->getMorphClass(), 'priceable_id' => $presentation->id, 'field' => $field, 'old_value' => $oldValue, 'new_value' => $newValue, 'reason' => $reason, 'changed_by' => auth()->id()]);
        });
        PriceChanged::dispatch($history);

        return $history;
    }
}
