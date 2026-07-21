<?php

namespace Tests\Feature;

use App\Domain\Enums\CashMovementType;
use App\Domain\Enums\CashSessionStatus;
use App\Domain\Enums\PaymentMethod;
use App\Exceptions\CashSessionClosedException;
use App\Livewire\CashRegisterPanel;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\User;
use App\Services\CashService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CashModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->actingAs(User::where('email', 'admin@construir.local')->firstOrFail());
    }

    public function test_opening_creates_session(): void
    {
        $service = app(CashService::class);
        $session = CashSession::query()->open()->firstOrFail();
        $service->close($session, $service->expectedAmount($session));

        $opened = $service->open(CashRegister::query()->firstOrFail(), 125);

        $this->assertSame(CashSessionStatus::Open, $opened->status);
        $this->assertSame('125.00', $opened->opening_amount);
    }

    public function test_income_and_expense_register_movements(): void
    {
        $session = CashSession::query()->open()->firstOrFail();
        $service = app(CashService::class);

        $service->income($session, 30, PaymentMethod::Cash, 'Ingreso de prueba');
        $service->expense($session, 10, PaymentMethod::Cash, 'Egreso de prueba');

        $this->assertDatabaseHas('cash_movements', ['cash_session_id' => $session->id, 'type' => CashMovementType::Income->value, 'amount' => '30.00']);
        $this->assertDatabaseHas('cash_movements', ['cash_session_id' => $session->id, 'type' => CashMovementType::Expense->value, 'amount' => '10.00']);
    }

    public function test_closing_calculates_difference_correctly(): void
    {
        $session = CashSession::query()->open()->firstOrFail();
        $service = app(CashService::class);
        $expected = (float) $service->expectedAmount($session);

        $closed = $service->close($session, $expected + 5);

        $this->assertSame(CashSessionStatus::Closed, $closed->status);
        $this->assertSame('5.00', $closed->difference);
        $this->assertSame(number_format($expected, 2, '.', ''), $closed->closing_amount);
    }

    public function test_closed_session_cannot_register_movement(): void
    {
        $session = CashSession::query()->open()->firstOrFail();
        $service = app(CashService::class);
        $service->close($session, $service->expectedAmount($session));

        $this->expectException(CashSessionClosedException::class);
        $service->income($session->fresh(), 10, PaymentMethod::Cash);
    }

    public function test_two_sessions_cannot_be_opened_for_same_register(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        app(CashService::class)->open(CashRegister::query()->firstOrFail(), 100);
    }

    public function test_qr_movement_requires_payment_account_in_ui(): void
    {
        Livewire::test(CashRegisterPanel::class)
            ->set('incomeAmount', '20')
            ->set('incomeMethod', 'qr')
            ->call('registerIncome')
            ->assertHasErrors('incomeAccountId');
    }
}
