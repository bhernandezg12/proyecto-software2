<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .container { max-width: 800px; margin: 20px auto; background: white; padding: 30px; }
        .header { text-align: center; font-size: 28px; font-weight: bold; margin-bottom: 40px; color: #333; }
        .kpi-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 30px 0; }
        .kpi { padding: 20px; background: #f9f9f9; border-left: 4px solid #2196F3; }
        .kpi-label { font-size: 12px; color: #666; text-transform: uppercase; }
        .kpi-value { font-size: 32px; font-weight: bold; color: #2196F3; margin-top: 10px; }
        .footer { text-align: center; margin-top: 40px; color: #999; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">📊 DASHBOARD EJECUTIVO</div>
        
        <div class="kpi-grid">
            <div class="kpi">
                <div class="kpi-label">Total Ventas</div>
                <div class="kpi-value">${{ number_format($total_ventas, 2) }}</div>
            </div>
            
            <div class="kpi">
                <div class="kpi-label">Total Órdenes</div>
                <div class="kpi-value">{{ $total_ordenes }}</div>
            </div>
            
            <div class="kpi">
                <div class="kpi-label">Total Usuarios</div>
                <div class="kpi-value">{{ $total_usuarios }}</div>
            </div>
            
            <div class="kpi">
                <div class="kpi-label">Generado</div>
                <div class="kpi-value">{{ $fecha }}</div>
            </div>
        </div>

        <div class="footer">
            Reporte generado automáticamente por el Sistema de Facturación
        </div>
    </div>
</body>
</html>
