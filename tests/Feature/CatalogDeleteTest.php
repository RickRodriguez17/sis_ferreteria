<?php

namespace Tests\Feature;

use App\Livewire\CatalogManager;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->actingAs(User::where('email', 'admin@construir.local')->firstOrFail());
    }

    public function test_attribute_with_values_is_not_deleted_and_dispatches_friendly_error(): void
    {
        $attribute = Attribute::factory()->create(['name' => 'Color eliminable']);
        AttributeValue::factory()->create(['attribute_id' => $attribute->id, 'value' => 'Rojo']);

        Livewire::test(CatalogManager::class, ['type' => 'attributes'])
            ->call('delete', $attribute->id)
            ->assertDispatched('toast', message: 'No se puede eliminar «Color eliminable»: tiene registros asociados.', type: 'error');

        $this->assertDatabaseHas('attributes', ['id' => $attribute->id]);
    }

    public function test_attribute_without_values_is_deleted(): void
    {
        $attribute = Attribute::factory()->create(['name' => 'Material eliminable']);

        Livewire::test(CatalogManager::class, ['type' => 'attributes'])
            ->call('delete', $attribute->id)
            ->assertDispatched('toast', message: 'Registro eliminado.');

        $this->assertDatabaseMissing('attributes', ['id' => $attribute->id]);
    }
}
