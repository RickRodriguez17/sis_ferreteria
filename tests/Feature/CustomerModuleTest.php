<?php

namespace Tests\Feature;

use App\Livewire\CustomerForm;
use App\Livewire\CustomerIndex;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->actingAs(User::where('email', 'admin@construir.local')->firstOrFail());
    }

    public function test_customer_can_be_created_and_edited(): void
    {
        Livewire::test(CustomerForm::class)
            ->set('type', 'registered')
            ->set('name', 'Cliente de Prueba')
            ->set('documentType', 'CI')
            ->set('documentNumber', 'CI-TEST-001')
            ->set('creditLimit', '2500')
            ->call('save')
            ->assertRedirect();

        $customer = Customer::query()->where('document_number', 'CI-TEST-001')->firstOrFail();
        $this->assertSame('Cliente de Prueba', $customer->name);
        $this->assertSame('2500.00', (string) $customer->credit_limit);

        Livewire::test(CustomerForm::class, ['customer' => $customer])
            ->set('name', 'Cliente Actualizado')
            ->call('save')
            ->assertRedirect();

        $this->assertSame('Cliente Actualizado', $customer->fresh()->name);
    }

    public function test_customer_list_can_search_by_document_name_and_phone(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Cliente Buscable',
            'document_number' => 'BUSCAR-001',
            'phone' => '70000001',
        ]);

        Livewire::test(CustomerIndex::class)
            ->set('search', 'BUSCAR-001')
            ->assertSee('Cliente Buscable')
            ->set('search', '70000001')
            ->assertSee('Cliente Buscable');
    }

    public function test_cashier_cannot_access_customers(): void
    {
        $cashier = User::where('email', 'cajera@construir.local')->firstOrFail();

        $this->actingAs($cashier)
            ->get(route('customers.index'))
            ->assertForbidden();
    }

    public function test_customer_statement_page_can_be_rendered(): void
    {
        $customer = Customer::query()->firstOrFail();

        $this->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee($customer->name)
            ->assertSee('Créditos y estado de cuenta');
    }
}
