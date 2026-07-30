<?php

namespace Tests\Feature;

use App\Domain\Enums\CreditStatus;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Sale;
use App\Models\User;
use App\Services\CreditAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CreditAlertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->actingAs(User::where('email', 'admin@construir.local')->firstOrFail());
    }

    public function test_credits_are_classified_as_upcoming_today_and_overdue(): void
    {
        $upcoming = $this->credit(Carbon::today()->addDays(3));
        $today = $this->credit(Carbon::today());
        $overdue = $this->credit(Carbon::today()->subDays(5));

        $buckets = app(CreditAlertService::class)->buckets();

        $this->assertTrue($buckets['upcoming']->contains('id', $upcoming->id));
        $this->assertTrue($buckets['today']->contains('id', $today->id));
        $this->assertTrue($buckets['overdue']->contains('id', $overdue->id));
        $this->assertSame(5, $overdue->fresh()->days_overdue);
    }

    public function test_alerts_ignore_paid_cancelled_and_far_future_credits(): void
    {
        $paid = $this->credit(Carbon::today()->subDay(), 0, CreditStatus::Paid);
        $cancelled = $this->credit(Carbon::today()->subDay(), 50, CreditStatus::Cancelled);
        $far = $this->credit(Carbon::today()->addDays(20));
        $summary = app(CreditAlertService::class)->summary();
        $buckets = $summary['creditAlerts'];

        $this->assertFalse($buckets['overdue']->contains('id', $paid->id));
        $this->assertFalse($buckets['overdue']->contains('id', $cancelled->id));
        $this->assertFalse($buckets['upcoming']->contains('id', $far->id));
    }

    private function credit(Carbon $dueDate, float $balance = 100, CreditStatus $status = CreditStatus::Open): Credit
    {
        $customer = Customer::factory()->create();
        $location = Location::query()->where('is_default', true)->firstOrFail();
        $sale = Sale::create([
            'uuid' => (string) str()->uuid(),
            'code' => 'ALERT-'.str()->random(8),
            'customer_id' => $customer->id,
            'with_invoice' => false,
            'payment_type' => 'credit',
            'status' => 'completed',
            'subtotal' => $balance,
            'discount' => 0,
            'total' => $balance,
            'location_id' => $location->id,
            'created_by' => auth()->id(),
        ]);

        return Credit::create([
            'customer_id' => $customer->id,
            'sale_id' => $sale->id,
            'original_amount' => $balance,
            'paid_amount' => 0,
            'balance' => $balance,
            'status' => $status,
            'due_date' => $dueDate,
        ]);
    }
}
