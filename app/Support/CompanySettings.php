<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class CompanySettings
{
    /** @var array<string, string|null>|null */
    private static ?array $values = null;

    public function all(): array
    {
        return self::$values ??= Setting::query()->pluck('value', 'key')->all();
    }

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->all()[$key] ?? $default;
    }

    public function name(): string
    {
        return $this->get('company_name', 'Construir a tu Alcance') ?: 'Construir a tu Alcance';
    }

    public function legalName(): string
    {
        return $this->get('company_legal_name', $this->name()) ?: $this->name();
    }

    public function taxId(): string
    {
        return $this->get('company_tax_id', '') ?? '';
    }

    public function address(): string
    {
        return $this->get('company_address', '') ?? '';
    }

    public function phone(): string
    {
        return $this->get('company_phone', '') ?? '';
    }

    public function email(): string
    {
        return $this->get('company_email', '') ?? '';
    }

    public function currency(): string
    {
        return strtoupper($this->get('currency', 'BOB') ?: 'BOB');
    }

    public function currencySymbol(): string
    {
        return match ($this->currency()) {
            'BOB' => 'Bs',
            'USD' => '$',
            'EUR' => '€',
            'PEN' => 'S/',
            'ARS' => '$',
            'CLP' => '$',
            'BRL' => 'R$',
            default => $this->currency(),
        };
    }

    public function defaultMargin(): string
    {
        return $this->get('default_margin', '0.30') ?: '0.30';
    }

    public function saleFooter(): string
    {
        return $this->get('sale_footer', 'Gracias por su compra.') ?: 'Gracias por su compra.';
    }

    public function quotationFooter(): string
    {
        return $this->get('quotation_footer', 'Precios sujetos a disponibilidad.') ?: 'Precios sujetos a disponibilidad.';
    }

    public function logoPath(): ?string
    {
        $path = $this->get('company_logo_path');

        return filled($path) ? $path : null;
    }

    public function logoUrl(): ?string
    {
        return $this->logoPath() ? Storage::disk('public')->url($this->logoPath()) : null;
    }

    public function logoDataUri(): ?string
    {
        $path = $this->logoPath();
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $absolutePath = Storage::disk('public')->path($path);
        $mime = mime_content_type($absolutePath) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($absolutePath));
    }

    public function money(float|int|string|null $amount): string
    {
        return $this->currencySymbol().' '.number_format((float) $amount, 2, '.', ',');
    }

    public static function clear(): void
    {
        self::$values = null;
    }
}
