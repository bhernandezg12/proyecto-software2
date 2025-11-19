<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { text-align: center; font-size: 20px; font-weight: bold; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        .summary { margin-top: 20px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="header">REPORTE DE VENTAS</div>
    
    <div class="summary">
        <p><strong>Período:</strong> {{ $fecha_inicio }} al {{ $fecha_fin }}</p>
        <p><strong>Total Ingresos:</strong> ${{ number_format($total_ingresos, 2) }}</p>
        <p><strong>Total Facturas:</strong> {{ $total_facturas }}</p>
        <p><strong>Cliente Top:</strong> {{ $cliente_top }}</p>
        <p><strong>Promedio Factura:</strong> ${{ number_format($promedio_factura, 2) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Número</th>
                <th>Cliente</th>
                <th>Total</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($facturas as $factura)
            <tr>
                <td>{{ $factura['numero_factura'] ?? 'N/A' }}</td>
                <td>{{ $factura['cliente_nombre'] ?? 'N/A' }}</td>
                <td>${{ number_format($factura['total'] ?? 0, 2) }}</td>
                <td>{{ $factura['estado'] ?? 'N/A' }}</td>
            </tr>
            @endforeach
              @empty
        <tr><td colspan="4">Sin facturas</td></tr>
    @endforelse
        </tbody>
    </table>

    <p style="text-align: right; margin-top: 30px; color: #666;">
        Generado: {{ date('d/m/Y H:i') }}
    </p>
</body>
</html>
