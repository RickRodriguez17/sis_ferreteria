<?php

namespace Tests\Feature;

use App\Domain\Enums\CreditStatus;
use App\Domain\Enums\PaymentMethod;
use App\Livewire\CreditIndex;
use App\Livewire\CreditShow;
use App\Models\Credit;
use App\Models\CreditPayment;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Sale;
use App\Models\User;
use App\Services\CreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class CreditModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->actingAs(User::where('email', 'admin@construir.local')->firstOrFail());
    }

    public function test_partial_payment_updates_balance_and_status(): void
    {
        $credit = $this->credit(100);

        app(CreditService::class)->registerPayment($credit, 40, PaymentMethod::Cash, null, 'Abono inicial');

        $credit = $credit->fresh();
        $this->assertSame('60.00', $credit->balance);
        $this->assertSame(CreditStatus::Partial, $credit->status);
        $this->assertDatabaseHas('credit_payments', ['credit_id' => $credit->id, 'notes' => 'Abono inicial']);
    }

    public function test_multiple_payments_liquidate_credit(): void
    {
        $credit = $this->credit(100);
        $service = app(CreditService::class);
        $paymentsBefore = CreditPayment::count();

        $service->registerPayment($credit, 40, PaymentMethod::Qr);
        $service->registerPayment($credit, 60, PaymentMethod::Transfer);

        $credit = $credit->fresh();
        $this->assertSame('0.00', $credit->balance);
        $this->assertSame(CreditStatus::Paid, $credit->status);
        $this->assertDatabaseCount('credit_payments', $paymentsBefore + 2);
    }

    public function test_payment_greater_than_balance_fails(): void
    {
        $credit = $this->credit(100);

        $this->expectException(\InvalidArgumentException::class);
        app(CreditService::class)->registerPayment($credit, 101, PaymentMethod::Cash);
    }

    public function test_credit_without_payments_can_be_cancelled(): void
    {
        $credit = $this->credit(100);

        app(CreditService::class)->cancel($credit);

        $this->assertSame(CreditStatus::Cancelled, $credit->fresh()->status);
    }

    public function test_credit_with_payments_cannot_be_cancelled(): void
    {
        $credit = $this->credit(100);
        app(CreditService::class)->registerPayment($credit, 10, PaymentMethod::Cash);

        $this->expectException(\InvalidArgumentException::class);
        app(CreditService::class)->cancel($credit);
    }

    public function test_mark_overdue_command_marks_expired_credits(): void
    {
        $credit = $this->credit(100, Carbon::yesterday());

        $this->artisan('credits:mark-overdue')
            ->expectsOutput('Se marcaron 1 crédito(s) como vencido(s).')
            ->assertSuccessful();

        $this->assertSame(CreditStatus::Overdue, $credit->fresh()->status);
    }

    public function test_credit_pages_render_with_account_history(): void
    {
        $credit = $this->credit(100);

        Livewire::test(CreditIndex::class)->assertStatus(200);
        Livewire::test(CreditShow::class, ['credit' => $credit])
            ->assertStatus(200)
            ->assertSee('Estado de cuenta');
    }

    private function credit(float $amount, ?Carbon $dueDate = null): Credit
    {
        $customer = Customer::factory()->create();
        $location = Location::query()->where('is_default', true)->firstOrFail();
        $sale = Sale::create([
            'uuid' => (string) str()->uuid(),
            'code' => 'TEST-'.str()->random(8),
            'customer_id' => $customer->id,
            'with_invoice' => false,
            'payment_type' => 'credit',
            'status' => 'completed',
            'subtotal' => $amount,
            'discount' => 0,
            'total' => $amount,
            'location_id' => $location->id,
            'created_by' => auth()->id(),
        ]);

        return Credit::create([
            'customer_id' => $customer->id,
            'sale_id' => $sale->id,
            'original_amount' => $amount,
            'paid_amount' => 0,
            'balance' => $amount,
            'status' => CreditStatus::Open,
            'due_date' => $dueDate,
        ]);
    }
}
