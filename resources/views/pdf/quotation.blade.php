<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><title>Cotización {{ $quotation->code }}</title><style>body{font-family:DejaVu Sans,sans-serif;font-size:12px;color:#1e293b}h1{font-size:20px;margin:0}h2{font-size:14px;margin-top:24px}table{width:100%;border-collapse:collapse;margin-top:12px}th,td{border-bottom:1px solid #cbd5e1;padding:7px;text-align:left}th{background:#e2e8f0}.right{text-align:right}.muted{color:#64748b}</style></head>
<body>
    @php($company = app(\App\Support\CompanySettings::class))
    @if($company->logoDataUri())<img src="{{ $company->logoDataUri() }}" alt="{{ $company->name() }}" style="max-height:60px;max-width:180px">@endif
    <h1>{{ $company->name() }}</h1>
    <p class="muted">{{ $company->legalName() }} @if($company->taxId()) · NIT: {{ $company->taxId() }} @endif<br>{{ $company->address() }} @if($company->phone()) · {{ $company->phone() }} @endif @if($company->email()) · {{ $company->email() }} @endif</p>
    <p class="muted">Cotización {{ $quotation->code }} · {{ $quotation->with_invoice ? 'Con factura' : 'Sin factura' }}</p>
    <h2>Cliente</h2>
    <p>{{ $quotation->customer?->name ?: 'Cliente ocasional' }}<br>{{ $quotation->customer?->document_number ?: 'Sin documento' }}</p>
    <table><thead><tr><th>Producto</th><th>Presentación</th><th>Cantidad</th><th>Precio</th><th class="right">Subtotal</th></tr></thead><tbody>
    @foreach($quotation->items as $item)<tr><td>{{ $item->product->name }}</td><td>{{ $item->presentation?->name ?: 'Unidad' }}</td><td>{{ $item->quantity }}</td><td>{{ $company->money($item->unit_price) }}</td><td class="right">{{ $company->money($item->subtotal) }}</td></tr>@endforeach
    </tbody></table>
    <p class="right">Subtotal: {{ $company->money($quotation->subtotal) }}<br><strong>Total: {{ $company->money($quotation->total) }}</strong></p>
    <p class="muted">{{ $company->quotationFooter() }}</p>
</body>
</html>
