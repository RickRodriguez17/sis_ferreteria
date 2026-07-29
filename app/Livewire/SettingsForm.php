<?php

namespace App\Livewire;

use App\Models\Setting;
use App\Support\CompanySettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class SettingsForm extends Component
{
    use WithFileUploads;

    public string $companyName = '';

    public string $companyLegalName = '';

    public string $companyTaxId = '';

    public string $companyAddress = '';

    public string $companyPhone = '';

    public string $companyEmail = '';

    public string $currency = 'BOB';

    public string $saleFooter = '';

    public string $quotationFooter = '';

    public string $defaultMargin = '0.30';

    public $logo;

    public ?string $currentLogoUrl = null;

    public function mount(CompanySettings $settings): void
    {
        Gate::authorize('update', Setting::class);
        $this->companyName = $settings->name();
        $this->companyLegalName = $settings->legalName();
        $this->companyTaxId = $settings->taxId();
        $this->companyAddress = $settings->address();
        $this->companyPhone = $settings->phone();
        $this->companyEmail = $settings->email();
        $this->currency = $settings->currency();
        $this->saleFooter = $settings->saleFooter();
        $this->quotationFooter = $settings->quotationFooter();
        $this->defaultMargin = $settings->defaultMargin();
        $this->currentLogoUrl = $settings->logoUrl();
    }

    public function save(): void
    {
        Gate::authorize('update', Setting::class);
        $this->validate([
            'companyName' => ['required', 'string', 'max:255'],
            'companyLegalName' => ['nullable', 'string', 'max:255'],
            'companyTaxId' => ['nullable', 'string', 'max:100'],
            'companyAddress' => ['nullable', 'string', 'max:500'],
            'companyPhone' => ['nullable', 'string', 'max:100'],
            'companyEmail' => ['nullable', 'email', 'max:255'],
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Za-z]{3}$/'],
            'saleFooter' => ['nullable', 'string', 'max:500'],
            'quotationFooter' => ['nullable', 'string', 'max:500'],
            'defaultMargin' => ['required', 'numeric', 'min:0', 'max:10'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ], [], [
            'companyName' => 'nombre comercial',
            'companyLegalName' => 'razón social',
            'companyTaxId' => 'NIT/identificación tributaria',
            'companyAddress' => 'dirección',
            'companyPhone' => 'teléfono',
            'companyEmail' => 'correo electrónico',
            'currency' => 'moneda',
            'saleFooter' => 'pie de nota de venta',
            'quotationFooter' => 'pie de cotización',
            'defaultMargin' => 'margen predeterminado',
            'logo' => 'logo',
        ]);

        DB::transaction(function (): void {
            $values = [
                'company_name' => $this->companyName,
                'company_legal_name' => $this->companyLegalName,
                'company_tax_id' => $this->companyTaxId,
                'company_address' => $this->companyAddress,
                'company_phone' => $this->companyPhone,
                'company_email' => $this->companyEmail,
                'currency' => strtoupper($this->currency),
                'sale_footer' => $this->saleFooter,
                'quotation_footer' => $this->quotationFooter,
                'default_margin' => $this->defaultMargin,
            ];
            foreach ($values as $key => $value) {
                Setting::updateOrCreate(['key' => $key], ['value' => $value, 'type' => in_array($key, ['default_margin'], true) ? 'decimal' : 'string']);
            }

            if ($this->logo) {
                $oldPath = Setting::query()->where('key', 'company_logo_path')->value('value');
                $path = $this->logo->store('company', 'public');
                Setting::updateOrCreate(['key' => 'company_logo_path'], ['value' => $path, 'type' => 'string']);
                if ($oldPath && $oldPath !== $path) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
        });

        CompanySettings::clear();
        $this->currentLogoUrl = app(CompanySettings::class)->logoUrl();
        $this->logo = null;
        $this->dispatch('toast', message: 'Configuración guardada.', type: 'success');
    }

    public function render()
    {
        return view('livewire.settings-form')->layout('layouts.app');
    }
}
