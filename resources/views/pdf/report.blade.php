<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><title>{{ $title }}</title><style>body{font-family:DejaVu Sans,sans-serif;font-size:10px;color:#1e293b}h1{font-size:18px;margin:0 0 4px}p{color:#64748b}table{width:100%;border-collapse:collapse;margin-top:16px}th,td{border-bottom:1px solid #cbd5e1;padding:5px;text-align:left}th{background:#e2e8f0}</style></head>
<body><h1>Construir a tu Alcance</h1><h2>{{ $title }}</h2><p>Generado el {{ now()->format('d/m/Y H:i') }}</p><table><thead><tr>@foreach($headings as $heading)<th>{{ $heading }}</th>@endforeach</tr></thead><tbody>@foreach($rows as $row)<tr>@foreach($row as $value)<td>{{ $value }}</td>@endforeach</tr>@endforeach</tbody></table></body>
</html>
