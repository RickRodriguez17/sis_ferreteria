<?php

namespace Tests\Feature;

use App\Livewire\SettingsForm;
use App\Models\Setting;
use App\Models\User;
use App\Support\CompanySettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CompanySettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_user_without_settings_permission_receives_forbidden(): void
    {
        $this->actingAs(User::where('email', 'cajera@construir.local')->firstOrFail())
            ->get(route('settings.edit'))
            ->assertForbidden();
    }

    public function test_administrator_can_save_company_settings(): void
    {
        $this->actingAs(User::where('email', 'admin@construir.local')->firstOrFail());

        Livewire::test(SettingsForm::class)
            ->set('companyName', 'Ferretería Central')
            ->set('companyLegalName', 'Ferretería Central S.R.L.')
            ->set('companyTaxId', '123456789')
            ->set('companyAddress', 'Av. Principal 123')
            ->set('companyPhone', '70000000')
            ->set('companyEmail', 'contacto@central.test')
            ->set('currency', 'BOB')
            ->set('saleFooter', 'Gracias por su compra.')
            ->set('quotationFooter', 'Cotización válida por 15 días.')
            ->set('defaultMargin', '0.35')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('settings', ['key' => 'company_name', 'value' => 'Ferretería Central']);
        $this->assertDatabaseHas('settings', ['key' => 'company_tax_id', 'value' => '123456789']);
        $this->assertDatabaseHas('settings', ['key' => 'default_margin', 'value' => '0.35']);
        $this->assertDatabaseHas('settings', ['key' => 'quotation_footer', 'value' => 'Cotización válida por 15 días.']);
    }

    public function test_company_settings_returns_saved_values_and_defaults(): void
    {
        Setting::query()->where('key', 'company_name')->update(['value' => 'Empresa guardada']);
        Setting::query()->where('key', 'currency')->delete();
        CompanySettings::clear();
        $settings = app(CompanySettings::class);

        $this->assertSame('Empresa guardada', $settings->name());
        $this->assertSame('BOB', $settings->currency());
        $this->assertSame('Bs', $settings->currencySymbol());
        $this->assertSame('Gracias por su compra.', $settings->saleFooter());
    }
}
