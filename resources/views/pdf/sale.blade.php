<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><title>Venta {{ $sale->code }}</title><style>body{font-family:DejaVu Sans,sans-serif;font-size:12px;color:#1e293b}h1{font-size:20px;margin:0}h2{font-size:14px;margin-top:24px}table{width:100%;border-collapse:collapse;margin-top:12px}th,td{border-bottom:1px solid #cbd5e1;padding:7px;text-align:left}th{background:#e2e8f0}.right{text-align:right}.muted{color:#64748b}</style></head>
<body>
    <h1>Construir a tu Alcance</h1>
    <p class="muted">Nota de venta {{ $sale->code }} · {{ $sale->with_invoice ? 'Con factura' : 'Sin factura' }}</p>
    <h2>Cliente</h2>
    <p>{{ $sale->customer?->name ?: 'Cliente ocasional' }}<br>{{ $sale->customer?->document_number ?: 'Sin documento' }}</p>
    <table><thead><tr><th>Producto</th><th>Presentación</th><th>Cantidad</th><th>Precio</th><th class="right">Subtotal</th></tr></thead><tbody>
    @foreach($sale->items as $item)<tr><td>{{ $item->product->name }}</td><td>{{ $item->presentation?->name ?: 'Unidad' }}</td><td>{{ $item->quantity }}</td><td>{{ number_format((float) $item->unit_price, 2) }}</td><td class="right">{{ number_format((float) $item->subtotal, 2) }}</td></tr>@endforeach
    </tbody></table>
    <p class="right">Subtotal: {{ number_format((float) $sale->subtotal, 2) }}<br>Descuento: {{ number_format((float) $sale->discount, 2) }}<br><strong>Total: {{ number_format((float) $sale->total, 2) }}</strong></p>
</body>
</html>
